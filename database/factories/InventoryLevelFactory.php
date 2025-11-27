<?php

namespace Database\Factories;

use App\Models\InventoryLevel;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class InventoryLevelFactory extends Factory
{
    protected $model = InventoryLevel::class;

    public function definition()
    {
        return [
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 100),
            'archived' => false,
            'updated_at' => Carbon::now(),
        ];
    }
}
