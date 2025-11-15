<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Uuid::uuid4(),
            'phone_number' => fake()->unique()->phoneNumber(),
            'name' => fake()->optional()->name(),
            'total_visits' => fake()->numberBetween(0, 50),
            'last_visit_at' => fake()->optional()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the customer has visited recently.
     */
    public function recentVisit(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_visit_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'total_visits' => fake()->numberBetween(5, 50),
        ]);
    }

    /**
     * Indicate that the customer has never visited.
     */
    public function neverVisited(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_visits' => 0,
            'last_visit_at' => null,
        ]);
    }
}

