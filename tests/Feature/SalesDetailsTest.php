<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;
use App\Models\InventoryLevel;
use App\Models\Sale;
use App\Models\SaleItem;

class SalesDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $product = Product::factory()->create(['name' => 'Produto Teste', 'cost_price' => 10, 'sale_price' => 20]);
        InventoryLevel::create(['product_id' => $product->id, 'quantity' => 100]);
        $sale = Sale::create([
            'customer_name' => 'Cliente Teste',
            'transaction_hash' => 'hash-teste-123',
            'total_amount' => 40,
            'total_profit' => 20,
            'status' => 'COMPLETED',
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 20.00,
            'cost_price' => 10.00,
            'profit' => 20.00,
        ]);
    }

    public function test_get_sale_details_returns_full_data()
    {
        $sale = Sale::first();
        $response = $this->getJson('/api/v1/sales/' . $sale->id);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'sale_id', 'customer_name', 'transaction_hash', 'total_amount', 'total_profit', 'status', 'created_at', 'items' => [
                    ['product_id', 'product_name', 'quantity', 'unit_price', 'cost_price', 'profit']
                ]
            ])
            ->assertJsonFragment(['sale_id' => $sale->id, 'customer_name' => 'Cliente Teste']);
    }

    public function test_get_sale_details_not_found()
    {
        $response = $this->getJson('/api/v1/sales/9999');
        $response->assertStatus(404)
            ->assertJson(['message' => 'Venda não encontrada.']);
    }
}
