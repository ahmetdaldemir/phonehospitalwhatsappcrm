<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Store;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brands = ['Apple', 'Samsung', 'Xiaomi', 'Huawei', 'Oppo', 'Vivo', 'OnePlus', 'Google'];
        $models = ['iPhone 14', 'Galaxy S23', 'Redmi Note 12', 'P50 Pro', 'Find X5', 'V27', '10 Pro', 'Pixel 7'];
        $problemTypes = ['Screen Repair', 'Battery Replacement', 'Charging Port', 'Camera Repair', 'Water Damage', 'Software Issue', 'Speaker Repair', 'Button Repair'];

        $brand = fake()->randomElement($brands);
        $model = fake()->randomElement($models);
        $problemType = fake()->randomElement($problemTypes);

        $storeIds = Store::pluck('id')->toArray();
        
        return [
            'id' => (string) Uuid::uuid4(),
            'customer_id' => Customer::factory(),
            'brand' => $brand,
            'model' => $model,
            'problem_type' => $problemType,
            'price_min' => fake()->numberBetween(500, 2000),
            'price_max' => fake()->numberBetween(2000, 5000),
            'store_id' => !empty($storeIds) ? fake()->optional()->randomElement($storeIds) : null,
            'status' => fake()->randomElement(['new', 'directed', 'completed', 'canceled']),
            'photos' => fake()->optional()->randomElements([
                ['photo1.jpg', 'photo2.jpg'],
                ['photo1.jpg'],
                ['photo1.jpg', 'photo2.jpg', 'photo3.jpg'],
            ], 1)[0] ?? null,
        ];
    }

    /**
     * Indicate that the ticket is new.
     */
    public function new(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'new',
        ]);
    }

    /**
     * Indicate that the ticket is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the ticket is directed to a store.
     */
    public function directed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'directed',
            'store_id' => Store::factory(),
        ]);
    }
}

