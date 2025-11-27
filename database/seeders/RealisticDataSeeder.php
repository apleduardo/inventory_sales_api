<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\InventoryLevel;
use Illuminate\Support\Facades\DB;

class RealisticDataSeeder extends Seeder
{
    public function run(): void
    {
        // Produtos
        Product::factory(100)->create();

        // Estoque inicial para cada produto
        foreach (Product::all() as $product) {
            InventoryLevel::updateOrCreate([
                'product_id' => $product->id
            ], [
                'quantity' => rand(100, 500)
            ]);
        }

        // Vendas históricas
        Sale::factory(10000)->create()->each(function ($sale) {
            $numItems = rand(2, 5);
            for ($i = 0; $i < $numItems; $i++) {
                $item = SaleItem::factory()->make([
                    'sale_id' => $sale->id,
                ]);
                $item->save();
            }
        });
    }
}
