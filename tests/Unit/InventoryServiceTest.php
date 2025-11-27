<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\InventoryService;
use App\Models\Product;
use App\Models\InventoryLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_inventory_summary_returns_expected_data()
    {
        $product = Product::factory()->create([
            'cost_price' => 20,
            'sale_price' => 30,
        ]);
        InventoryLevel::create([
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $service = app(InventoryService::class);
        $summary = $service->getInventorySummary();

        $this->assertEquals(40, $summary['total_stock_value']); // 2 * 20
        $this->assertEquals(20, $summary['total_projected_profit']); // 2 * (30-20)
        $this->assertCount(1, $summary['inventory']);
        $this->assertEquals($product->id, $summary['inventory'][0]['product_id']);
    }
}
