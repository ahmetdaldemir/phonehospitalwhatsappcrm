<?php

namespace Database\Seeders;

use App\Models\PriceMatrix;
use Illuminate\Database\Seeder;

class PriceMatrixSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Common phone brands and models
        $devices = [
            // Apple
            ['brand' => 'Apple', 'model' => 'iPhone 14', 'problem_type' => 'Screen Repair', 'price_min' => 1500, 'price_max' => 3000],
            ['brand' => 'Apple', 'model' => 'iPhone 14', 'problem_type' => 'Battery Replacement', 'price_min' => 800, 'price_max' => 1500],
            ['brand' => 'Apple', 'model' => 'iPhone 14', 'problem_type' => 'Charging Port', 'price_min' => 1000, 'price_max' => 2000],
            ['brand' => 'Apple', 'model' => 'iPhone 13', 'problem_type' => 'Screen Repair', 'price_min' => 1200, 'price_max' => 2500],
            ['brand' => 'Apple', 'model' => 'iPhone 13', 'problem_type' => 'Battery Replacement', 'price_min' => 700, 'price_max' => 1300],
            ['brand' => 'Apple', 'model' => 'iPhone 12', 'problem_type' => 'Screen Repair', 'price_min' => 1000, 'price_max' => 2000],
            
            // Samsung
            ['brand' => 'Samsung', 'model' => 'Galaxy S23', 'problem_type' => 'Screen Repair', 'price_min' => 1800, 'price_max' => 3500],
            ['brand' => 'Samsung', 'model' => 'Galaxy S23', 'problem_type' => 'Battery Replacement', 'price_min' => 900, 'price_max' => 1600],
            ['brand' => 'Samsung', 'model' => 'Galaxy S22', 'problem_type' => 'Screen Repair', 'price_min' => 1500, 'price_max' => 3000],
            ['brand' => 'Samsung', 'model' => 'Galaxy S22', 'problem_type' => 'Camera Repair', 'price_min' => 1200, 'price_max' => 2500],
            
            // Xiaomi
            ['brand' => 'Xiaomi', 'model' => 'Redmi Note 12', 'problem_type' => 'Screen Repair', 'price_min' => 800, 'price_max' => 1500],
            ['brand' => 'Xiaomi', 'model' => 'Redmi Note 12', 'problem_type' => 'Battery Replacement', 'price_min' => 500, 'price_max' => 1000],
            ['brand' => 'Xiaomi', 'model' => 'Mi 13', 'problem_type' => 'Screen Repair', 'price_min' => 1200, 'price_max' => 2200],
            
            // Huawei
            ['brand' => 'Huawei', 'model' => 'P50 Pro', 'problem_type' => 'Screen Repair', 'price_min' => 1400, 'price_max' => 2800],
            ['brand' => 'Huawei', 'model' => 'P50 Pro', 'problem_type' => 'Battery Replacement', 'price_min' => 800, 'price_max' => 1400],
            
            // OnePlus
            ['brand' => 'OnePlus', 'model' => '10 Pro', 'problem_type' => 'Screen Repair', 'price_min' => 1600, 'price_max' => 3200],
            ['brand' => 'OnePlus', 'model' => '10 Pro', 'problem_type' => 'Charging Port', 'price_min' => 900, 'price_max' => 1800],
            
            // Google
            ['brand' => 'Google', 'model' => 'Pixel 7', 'problem_type' => 'Screen Repair', 'price_min' => 1300, 'price_max' => 2600],
            ['brand' => 'Google', 'model' => 'Pixel 7', 'problem_type' => 'Battery Replacement', 'price_min' => 750, 'price_max' => 1400],
        ];

        // Common problem types for any device
        $commonProblems = [
            'Water Damage' => ['price_min' => 1500, 'price_max' => 4000],
            'Software Issue' => ['price_min' => 500, 'price_max' => 1500],
            'Speaker Repair' => ['price_min' => 600, 'price_max' => 1200],
            'Button Repair' => ['price_min' => 400, 'price_max' => 1000],
        ];

        // Create specific device entries
        foreach ($devices as $device) {
            PriceMatrix::create($device);
        }

        // Add common problems for popular devices
        $popularDevices = [
            ['Apple', 'iPhone 14'],
            ['Apple', 'iPhone 13'],
            ['Samsung', 'Galaxy S23'],
            ['Samsung', 'Galaxy S22'],
        ];

        foreach ($popularDevices as $device) {
            foreach ($commonProblems as $problem => $prices) {
                PriceMatrix::create([
                    'brand' => $device[0],
                    'model' => $device[1],
                    'problem_type' => $problem,
                    'price_min' => $prices['price_min'],
                    'price_max' => $prices['price_max'],
                ]);
            }
        }

        // Create some random entries using factory
        PriceMatrix::factory()
            ->count(20)
            ->create();
    }
}

