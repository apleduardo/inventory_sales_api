<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'sku' => strtoupper(Str::random(8)),
            'name' => $this->faker->word(),
            'cost_price' => $this->faker->randomFloat(2, 1, 100),
            'sale_price' => $this->faker->randomFloat(2, 2, 200),
        ];
    }
}
