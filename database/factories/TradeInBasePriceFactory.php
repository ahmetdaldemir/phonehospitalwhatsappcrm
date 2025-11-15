<?php

namespace Database\Factories;

use App\Models\TradeInBasePrice;
use Illuminate\Database\Eloquent\Factories\Factory;

class TradeInBasePriceFactory extends Factory
{
    protected $model = TradeInBasePrice::class;

    public function definition(): array
    {
        return [
            'brand' => $this->faker->randomElement(['Apple', 'Samsung', 'Xiaomi']),
            'model' => $this->faker->randomElement(['iPhone 14', 'Galaxy S23', 'Mi 13']),
            'storage' => $this->faker->randomElement(['64GB', '128GB', '256GB', '512GB', '1TB']),
            'base_price' => $this->faker->numberBetween(20000, 50000),
            'active' => true,
        ];
    }
}

