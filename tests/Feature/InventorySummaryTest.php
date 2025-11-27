<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;
use App\Models\InventoryLevel;

class InventorySummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_summary_endpoint_returns_correct_structure()
    {
        $product = Product::factory()->create([
            'cost_price' => 10,
            'sale_price' => 15,
        ]);
        InventoryLevel::create([
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $response = $this->getJson('/api/v1/inventory');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'inventory' => [
                    [
                        'product_id',
                        'sku',
                        'name',
                        'quantity',
                        'cost_price',
                        'sale_price',
                        'total_value',
                        'projected_profit',
                    ]
                ],
                'total_stock_value',
                'total_projected_profit',
            ]);
        $data = $response->json();
        $this->assertEquals(50, $data['total_stock_value']); // 5 * 10
        $this->assertEquals(25, $data['total_projected_profit']); // 5 * (15-10)
    }
}
