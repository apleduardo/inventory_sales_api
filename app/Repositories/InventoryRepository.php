<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\InventoryLevel;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InventoryRepository
{
    public function updateStockEntry(int $productId, int $quantity, float $costPrice)
    {
        return DB::transaction(function () use ($productId, $quantity, $costPrice) {
            $level = InventoryLevel::where('product_id', $productId)->where('archived', false)->first();
            if ($level) {
                $level->quantity += $quantity;
                $level->save();
            } else {
                $level = InventoryLevel::create([
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ]);
            }
            $movement = InventoryMovement::create([
                'product_id' => $productId,
                'movement_type' => 'IN',
                'quantity' => $quantity,
                'cost_price' => $costPrice,
            ]);
            Cache::forget('inventory_summary_global');
            Log::info('INVENTORY_ENTRY_SUCCESS', [
                'event' => 'INVENTORY_ENTRY_SUCCESS',
                'movement_id' => $movement->id,
                'product_id' => $productId,
                'quantity_added' => $quantity,
                'final_level' => $level,
                'cost_price' => $costPrice,
            ]);
            return $movement;
        });
    }
}