<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\InventoryLevel;
use App\Models\Product;

class ProcessSaleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $saleId;
    protected $data;

    public function __construct($saleId, array $data)
    {
        $this->saleId = $saleId;
        $this->data = $data;
    }

    public function handle()
    {
        $sale = Sale::find($this->saleId);
        if (!$sale || $sale->status !== Sale::STATUS_PENDING) {
            Log::warning('ProcessSaleJob: Sale not found or already processed', ['sale_id' => $this->saleId]);
            return;
        }
        // Validação do payload
        if (empty($this->data['items']) || !is_array($this->data['items']) || count($this->data['items']) === 0) {
            $sale->status = Sale::STATUS_FAILED;
            $sale->save();
            Log::error('ProcessSaleJob: Sale failed - payload inválido', ['sale_id' => $sale->id]);
            return;
        }
        try {
            DB::transaction(function () use ($sale) {
                $totalAmount = 0;
                $totalProfit = 0;
                $items = [];
                foreach ($this->data['items'] as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $inventory = InventoryLevel::where('product_id', $item['product_id'])->lockForUpdate()->first();
                    if (!$inventory || $inventory->quantity < $item['quantity']) {
                        throw new \Exception('Estoque insuficiente para o produto: ' . $product->name);
                    }
                    $lineTotal = $item['quantity'] * $item['unit_price'];
                    $lineProfit = ($item['unit_price'] - $product->cost_price) * $item['quantity'];
                    $totalAmount += $lineTotal;
                    $totalProfit += $lineProfit;
                    $items[] = [
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'cost_price' => $product->cost_price,
                        'profit' => $lineProfit,
                    ];
                }
                foreach ($items as $item) {
                    SaleItem::create(array_merge($item, ['sale_id' => $sale->id]));
                    $inventory = InventoryLevel::where('product_id', $item['product_id'])->lockForUpdate()->first();
                    $inventory->quantity -= $item['quantity'];
                    $inventory->save();
                }
                $sale->total_amount = $totalAmount;
                $sale->total_profit = $totalProfit;
                $sale->status = Sale::STATUS_COMPLETED;
                $sale->save();
                event(new \App\Events\SaleRegistered($sale));
                Log::info('ProcessSaleJob: Sale processed', ['sale_id' => $sale->id]);
            });
        } catch (\Exception $e) {
            $sale->status = Sale::STATUS_FAILED;
            $sale->save();
            Log::error('ProcessSaleJob: Sale failed', ['sale_id' => $sale->id, 'error' => $e->getMessage()]);
        }
    }
}
