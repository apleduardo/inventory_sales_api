<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\InventoryLevel;
use App\Models\InventoryMovement;
use App\Models\User;
use App\Models\Product;

class InventoryEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_entry_success()
    {
        $product = Product::factory()->create();
        $payload = [
            'product_id' => $product->id,
            'quantity' => 10,
            'cost_price' => 5.50,
        ];

        $response = $this->postJson('/api/v1/inventory', $payload);
        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'movement_id']);

        $this->assertDatabaseHas('inventory_levels', [
            'product_id' => $product->id,
            'quantity' => 10,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'movement_type' => 'IN',
            'quantity' => 10,
            'cost_price' => 5.50,
        ]);
    }

    public function test_inventory_entry_negative_quantity_fails()
    {
        $product = Product::factory()->create();
        $payload = [
            'product_id' => $product->id,
            'quantity' => -5,
            'cost_price' => 5.50,
        ];

        $response = $this->postJson('/api/v1/inventory', $payload);
        $response->assertStatus(422);
    }

    public function test_inventory_entry_invalid_product_fails()
    {
        $payload = [
            'product_id' => 999999,
            'quantity' => 5,
            'cost_price' => 5.50,
        ];

        $response = $this->postJson('/api/v1/inventory', $payload);
        $response->assertStatus(422);
    }
}
