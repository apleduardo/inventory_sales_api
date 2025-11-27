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
        // 1. Garante que as duas escritas ocorram juntas (ALL OR NOTHING)
        return DB::transaction(function () use ($productId, $quantity, $costPrice) {

            $level = InventoryLevel::updateOrCreate(
                ['product_id' => $productId], 
                [
                    'quantity' => DB::raw("quantity + $quantity")
                ]
            );
            
            // 1.2. Registra o histórico de movimentação (WORM)
            $movement = InventoryMovement::create([
                'product_id' => $productId,
                'movement_type' => 'IN',
                'quantity' => $quantity,
                'cost_price' => $costPrice,
            ]);

            // 1.3. INVALIDE o cache da situação atual de estoque (GET /api/inventory)
            Cache::forget('inventory_summary_global');

            Log::info('INVENTORY_ENTRY_SUCCESS', [
                'event' => 'INVENTORY_ENTRY_SUCCESS',
                'movement_id' => $movement->id,
                'product_id' => $productId,
                'quantity_added' => $quantity,
                'final_level' => $level, 
                'cost_price' => $costPrice,
            ]);

            return $movement; // Retorna o registro da movimentação para o Controller
        });
    }
}