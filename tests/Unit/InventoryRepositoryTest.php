<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Repositories\InventoryRepository;
use App\Models\Product;
use App\Models\InventoryLevel;
use App\Models\InventoryMovement;

class InventoryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_stock_entry_creates_level_and_movement()
    {
        $product = Product::factory()->create();
        $repo = new InventoryRepository();
        $movement = $repo->updateStockEntry($product->id, 15, 7.25);

        $this->assertInstanceOf(InventoryMovement::class, $movement);
        $this->assertDatabaseHas('inventory_levels', [
            'product_id' => $product->id,
            'quantity' => 15,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'movement_type' => 'IN',
            'quantity' => 15,
            'cost_price' => 7.25,
        ]);
    }

    public function test_update_stock_entry_accumulates_quantity()
    {
        $product = Product::factory()->create();
        $repo = new InventoryRepository();
        $repo->updateStockEntry($product->id, 10, 5.00);
        sleep(1); // Garante que o created_at será diferente
        $repo->updateStockEntry($product->id, 5, 5.00);

        $this->assertDatabaseHas('inventory_levels', [
            'product_id' => $product->id,
            'quantity' => 15,
        ]);
    }
}
