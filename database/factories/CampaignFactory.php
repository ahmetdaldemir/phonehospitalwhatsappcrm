<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $discountType = fake()->randomElement(['percent', 'free_item']);
        $startDate = fake()->dateTimeBetween('-1 month', '+1 month');
        $endDate = fake()->dateTimeBetween($startDate, '+3 months');

        return [
            'id' => (string) Uuid::uuid4(),
            'name' => fake()->sentence(3),
            'discount_type' => $discountType,
            'value' => $discountType === 'percent' 
                ? fake()->numberBetween(5, 50) 
                : fake()->numberBetween(1, 5),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'usage_count' => fake()->numberBetween(0, 1000),
        ];
    }

    /**
     * Indicate that the campaign is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'end_date' => fake()->dateTimeBetween('now', '+1 month'),
        ]);
    }

    /**
     * Indicate that the campaign is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => fake()->dateTimeBetween('-3 months', '-2 months'),
            'end_date' => fake()->dateTimeBetween('-2 months', '-1 month'),
        ]);
    }

    /**
     * Indicate that the campaign is upcoming.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+2 months'),
        ]);
    }

    /**
     * Create a percent discount campaign.
     */
    public function percentDiscount(int $percent): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => 'percent',
            'value' => $percent,
        ]);
    }

    /**
     * Create a free item campaign.
     */
    public function freeItem(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => 'free_item',
            'value' => $quantity,
        ]);
    }
}

