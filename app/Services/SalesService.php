<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\InventoryLevel;
use App\Models\Product;

class SalesService
{
    public function registerSale(array $data)
    {
        // Validação básica
        if (empty($data['items']) || !is_array($data['items'])) {
            throw new \Exception('Itens da venda são obrigatórios.');
        }

        return DB::transaction(function () use ($data) {
            $totalAmount = 0;
            $totalProfit = 0;
            $items = [];

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                if ($product->cost_price === null || $product->sale_price === null) {
                    throw new \Exception('Produto sem preço definido.');
                }
                $inventory = InventoryLevel::find($item['product_id']);
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

            $sale = Sale::create([
                'customer_name' => $data['customer_name'] ?? null,
                'transaction_hash' => md5(json_encode($data) . microtime()),
                'total_amount' => $totalAmount,
                'total_profit' => $totalProfit,
                'status' => 'COMPLETED',
            ]);

            foreach ($items as $item) {
                SaleItem::create(array_merge($item, ['sale_id' => $sale->id]));
                // Atualiza estoque
                $inventory = InventoryLevel::find($item['product_id']);
                $inventory->quantity -= $item['quantity'];
                $inventory->save();
            }

            Log::info('SALE_COMPLETED', [
                'sale_id' => $sale->id,
                'total_amount' => $totalAmount,
                'total_profit' => $totalProfit,
            ]);

            return $sale;
        });
    }

    public function createPendingSale(array $data, string $transactionHash)
    {
        return Sale::create([
            'customer_name' => $data['customer_name'] ?? null,
            'transaction_hash' => $transactionHash,
            'total_amount' => 0,
            'total_profit' => 0,
            'status' => 'PENDING',
        ]);
    }
}
