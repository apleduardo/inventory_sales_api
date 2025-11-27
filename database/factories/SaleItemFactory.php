<?php

namespace Database\Factories;

use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    public function definition()
    {
        return [
            'product_id' => Product::inRandomOrder()->first()->id ?? 1,
            'quantity' => $this->faker->numberBetween(1, 5),
            'unit_price' => $this->faker->randomFloat(2, 2, 200),
            'cost_price' => $this->faker->randomFloat(2, 1, 100),
            'profit' => $this->faker->randomFloat(2, 1, 100),
        ];
    }
}
