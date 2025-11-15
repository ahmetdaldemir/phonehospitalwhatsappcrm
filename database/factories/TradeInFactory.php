<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\TradeIn;
use Illuminate\Database\Eloquent\Factories\Factory;

class TradeInFactory extends Factory
{
    protected $model = TradeIn::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'brand' => $this->faker->randomElement(['Apple', 'Samsung', 'Xiaomi']),
            'model' => $this->faker->randomElement(['iPhone 14', 'Galaxy S23', 'Mi 13']),
            'storage' => $this->faker->randomElement(['64GB', '128GB', '256GB', '512GB']),
            'color' => $this->faker->colorName(),
            'condition' => $this->faker->randomElement(['A', 'B', 'C']),
            'battery_health' => $this->faker->numberBetween(50, 100),
            'photos' => null,
            'offer_min' => $this->faker->numberBetween(10000, 20000),
            'offer_max' => $this->faker->numberBetween(20000, 30000),
            'final_price' => null,
            'status' => $this->faker->randomElement(['new', 'waiting_device', 'completed', 'canceled']),
        ];
    }
}

