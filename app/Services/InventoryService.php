<?php

namespace App\Services;

use App\Repositories\InventoryRepository;
use Illuminate\Support\Facades\Cache;

class InventoryService
{
    protected $inventoryRepository;

    public function __construct(InventoryRepository $inventoryRepository)
    {
        $this->inventoryRepository = $inventoryRepository;
    }

    public function registerEntry(int $productId, int $quantity, float $costPrice)
    {
        // Validações de negócio adicionais podem ir aqui

        // Delega ao Repository a atualização atômica do DB
        return $this->inventoryRepository->updateStockEntry(
            $productId, 
            $quantity, 
            $costPrice
        );
    }

    public function getInventorySummary()
    {
        // Cache global do inventário
        return Cache::remember('inventory_summary_global', 60, function () {
            $levels = \App\Models\InventoryLevel::with('product')->get();
            $summary = $levels->map(function ($level) {
                $profit = ($level->product->sale_price - $level->product->cost_price) * $level->quantity;
                return [
                    'product_id' => $level->product_id,
                    'sku' => $level->product->sku,
                    'name' => $level->product->name,
                    'quantity' => $level->quantity,
                    'cost_price' => $level->product->cost_price,
                    'sale_price' => $level->product->sale_price,
                    'total_value' => $level->quantity * $level->product->cost_price,
                    'projected_profit' => $profit,
                ];
            });
            return [
                'inventory' => $summary,
                'total_stock_value' => $summary->sum('total_value'),
                'total_projected_profit' => $summary->sum('projected_profit'),
            ];
        });
    }
}