<?php

namespace Database\Seeders;

use App\Models\Campaign;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create active campaigns
        Campaign::factory()
            ->active()
            ->percentDiscount(20)
            ->create([
                'name' => 'Summer Sale - 20% Off',
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(25),
                'usage_count' => 150,
            ]);

        Campaign::factory()
            ->active()
            ->freeItem(1)
            ->create([
                'name' => 'Buy 2 Get 1 Free Screen Protector',
                'start_date' => now()->subDays(10),
                'end_date' => now()->addDays(20),
                'usage_count' => 89,
            ]);

        Campaign::factory()
            ->active()
            ->percentDiscount(15)
            ->create([
                'name' => 'Weekend Special - 15% Off',
                'start_date' => now()->subDays(2),
                'end_date' => now()->addDays(2),
                'usage_count' => 45,
            ]);

        // Create upcoming campaigns
        Campaign::factory()
            ->upcoming()
            ->percentDiscount(30)
            ->create([
                'name' => 'Black Friday - 30% Off',
                'start_date' => now()->addDays(30),
                'end_date' => now()->addDays(33),
                'usage_count' => 0,
            ]);

        Campaign::factory()
            ->upcoming()
            ->freeItem(2)
            ->create([
                'name' => 'Holiday Special - Buy 1 Get 2 Free',
                'start_date' => now()->addDays(45),
                'end_date' => now()->addDays(60),
                'usage_count' => 0,
            ]);

        // Create expired campaigns
        Campaign::factory()
            ->expired()
            ->percentDiscount(25)
            ->create([
                'name' => 'Spring Sale - 25% Off',
                'start_date' => now()->subDays(60),
                'end_date' => now()->subDays(30),
                'usage_count' => 320,
            ]);

        // Create some random campaigns using factory
        Campaign::factory()
            ->count(5)
            ->create();
    }
}

