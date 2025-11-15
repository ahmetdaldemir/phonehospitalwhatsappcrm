<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create example stores with locations
        Store::create([
            'name' => 'Phone Hospital Downtown',
            'code' => 'STORE-001',
            'address' => '123 Main Street, Downtown',
            'phone' => '+1-555-0101',
            'email' => 'downtown@phonehospital.com',
            'location_lat' => 40.7128,
            'location_lng' => -74.0060,
            'is_active' => true,
        ]);

        Store::create([
            'name' => 'Phone Hospital Uptown',
            'code' => 'STORE-002',
            'address' => '456 Park Avenue, Uptown',
            'phone' => '+1-555-0102',
            'email' => 'uptown@phonehospital.com',
            'location_lat' => 40.7580,
            'location_lng' => -73.9855,
            'is_active' => true,
        ]);

        Store::create([
            'name' => 'Phone Hospital Westside',
            'code' => 'STORE-003',
            'address' => '789 Broadway, Westside',
            'phone' => '+1-555-0103',
            'email' => 'westside@phonehospital.com',
            'location_lat' => 40.7505,
            'location_lng' => -73.9934,
            'is_active' => true,
        ]);

        // Create additional stores using factory
        Store::factory()
            ->count(7)
            ->create();
    }
}

