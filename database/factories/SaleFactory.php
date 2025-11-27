<?php

namespace Database\Factories;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition()
    {
        return [
            'customer_name' => $this->faker->name(),
            'transaction_hash' => $this->faker->unique()->md5,
            'total_amount' => $this->faker->randomFloat(2, 10, 1000),
            'total_profit' => $this->faker->randomFloat(2, 1, 500),
            'status' => $this->faker->randomElement([Sale::STATUS_COMPLETED, Sale::STATUS_PENDING, Sale::STATUS_FAILED]),
            'created_at' => $this->faker->dateTimeBetween('-12 months', 'now'),
        ];
    }
}
