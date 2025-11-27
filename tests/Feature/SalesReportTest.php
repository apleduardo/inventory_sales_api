<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Log;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected $token;

    public function setUp(): void
    {
        parent::setUp();
        date_default_timezone_set('UTC');
        $this->token = $this->getAuthToken();
        $product = Product::factory()->create(['name' => 'Produto Teste', 'cost_price' => 10, 'sale_price' => 20]);
        $sale1 = Sale::create([
            'customer_name' => 'Cliente A',
            'transaction_hash' => 'hash-1',
            'total_amount' => 40,
            'total_profit' => 20,
            'status' => \App\Models\Sale::STATUS_COMPLETED,
        ]);
        $sale1->forceFill(['created_at' => now()->subDays(2)->format('Y-m-d 12:00:00')])->save();
        $sale2 = Sale::create([
            'customer_name' => 'Cliente B',
            'transaction_hash' => 'hash-2',
            'total_amount' => 60,
            'total_profit' => 30,
            'status' => \App\Models\Sale::STATUS_FAILED,
        ]);
        $sale2->forceFill(['created_at' => now()->subDay()->format('Y-m-d 12:00:00')])->save();
        \Illuminate\Support\Facades\Log::info('[SalesReportTest] Sale1 created_at: ' . $sale1->created_at);
        \Illuminate\Support\Facades\Log::info('[SalesReportTest] Sale2 created_at: ' . $sale2->created_at);
        SaleItem::create([
            'sale_id' => $sale1->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 20.00,
            'cost_price' => 10.00,
            'profit' => 20.00,
        ]);
        SaleItem::create([
            'sale_id' => $sale2->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 20.00,
            'cost_price' => 10.00,
            'profit' => 30.00,
        ]);
    }

    public function test_report_returns_all_sales()
    {
        $response = $this->getJson('/api/v1/reports/sales', [
            'Authorization' => 'Bearer ' . $this->token
        ]);
        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['customer_name' => 'Cliente A'])
            ->assertJsonFragment(['customer_name' => 'Cliente B']);
    }

    public function test_report_filters_by_status()
    {
        $response = $this->getJson('/api/v1/reports/sales?status=COMPLETED', [
            'Authorization' => 'Bearer ' . $this->token
        ]);
        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['customer_name' => 'Cliente A']);
    }

    public function test_report_filters_by_customer_name()
    {
        $response = $this->getJson('/api/v1/reports/sales?customer_name=Cliente B', [
            'Authorization' => 'Bearer ' . $this->token
        ]);
        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['customer_name' => 'Cliente B']);
    }

    public function test_report_filters_by_date_range()
    {
        $start = now()->subDays(3)->format('Y-m-d'); // 2025-11-24
        $end = now()->subDays(2)->format('Y-m-d');   // 2025-11-25
        $response = $this->getJson("/api/v1/reports/sales?start_date=$start&end_date=$end", [
            'Authorization' => 'Bearer ' . $this->token
        ]);
        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['customer_name' => 'Cliente A']);
    }
}
