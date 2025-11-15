<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 50 customers with various visit patterns
        Customer::factory()
            ->count(30)
            ->recentVisit()
            ->create();

        Customer::factory()
            ->count(15)
            ->neverVisited()
            ->create();

        Customer::factory()
            ->count(5)
            ->create([
                'name' => 'John Doe',
                'phone_number' => '+1234567890',
            ]);
    }
}

