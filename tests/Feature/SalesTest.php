<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;
use App\Models\InventoryLevel;
use App\Models\Sale;
use Illuminate\Support\Facades\Queue;

class SalesTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        // Cria produto e estoque
        $product = Product::factory()->create(['cost_price' => 10, 'sale_price' => 20]);
        InventoryLevel::create(['product_id' => $product->id, 'quantity' => 100]);
    }

    public function test_cadastra_venda_valida()
    {
        Queue::fake();
        $product = Product::first();
        $payload = [
            'customer_name' => 'Cliente Teste',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 20.00
                ]
            ]
        ];
        $response = $this->postJson('/api/v1/sales', $payload);
        $response->assertStatus(202)
            ->assertJsonStructure(['message', 'sale_id', 'transaction_hash']);
        $this->assertDatabaseHas('sales', [
            'id' => $response['sale_id'],
            'status' => 'PENDING'
        ]);
        Queue::assertPushed(\App\Jobs\ProcessSaleJob::class);
    }

    public function test_venda_com_estoque_insuficiente()
    {
        $product = Product::first();
        $payload = [
            'customer_name' => 'Cliente Teste',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 9999,
                    'unit_price' => 20.00
                ]
            ]
        ];
        $response = $this->postJson('/api/v1/sales', $payload);
        $response->assertStatus(202);
        // Simula processamento do job
        $sale = Sale::find($response['sale_id']);
        \App\Jobs\ProcessSaleJob::dispatchSync($sale->id, $payload);
        $sale->refresh();
        $this->assertEquals('FAILED', $sale->status);
    }

    public function test_venda_payload_invalido()
    {
        $payload = [
            'customer_name' => 'Cliente Teste',
            'items' => []
        ];
        $response = $this->postJson('/api/v1/sales', $payload);
        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_venda_idempotente_com_hash_do_payload()
    {
        $product = Product::first();
        $payload = [
            'customer_name' => 'Cliente Teste',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 20.00
                ]
            ],
            'transaction_hash' => 'hash-teste-123'
        ];
        $response1 = $this->postJson('/api/v1/sales', $payload);
        $response2 = $this->postJson('/api/v1/sales', $payload);
        $response1->assertStatus(202);
        $response2->assertStatus(200);
        $this->assertEquals($response1['sale_id'], $response2['sale_id']);
        $this->assertEquals($response1['transaction_hash'], $response2['transaction_hash']);
    }
}
