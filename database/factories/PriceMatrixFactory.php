<?php

namespace Database\Factories;

use App\Models\PriceMatrix;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PriceMatrix>
 */
class PriceMatrixFactory extends Factory
{
    protected $model = PriceMatrix::class;

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

        $priceMin = fake()->numberBetween(500, 2000);
        $priceMax = fake()->numberBetween($priceMin + 500, $priceMin + 3000);

        return [
            'id' => (string) Uuid::uuid4(),
            'brand' => fake()->randomElement($brands),
            'model' => fake()->randomElement($models),
            'problem_type' => fake()->randomElement($problemTypes),
            'price_min' => $priceMin,
            'price_max' => $priceMax,
        ];
    }

    /**
     * Create a specific price matrix entry.
     */
    public function forDevice(string $brand, string $model, string $problemType, int $priceMin, int $priceMax): static
    {
        return $this->state(fn (array $attributes) => [
            'brand' => $brand,
            'model' => $model,
            'problem_type' => $problemType,
            'price_min' => $priceMin,
            'price_max' => $priceMax,
        ]);
    }
}

