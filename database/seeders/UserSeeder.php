<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@phonehospital.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'store_id' => null,
        ]);

        // Create store users for each store
        $stores = Store::all();

        if ($stores->isEmpty()) {
            $this->command->warn('No stores found. Please run StoreSeeder first!');
            return;
        }

        foreach ($stores as $index => $store) {
            User::create([
                'name' => "Store Manager {$store->name}",
                'email' => "store{$index + 1}@phonehospital.com",
                'password' => Hash::make('password'),
                'role' => 'store',
                'store_id' => $store->id,
            ]);

            // Create additional staff for first store
            if ($index === 0) {
                User::create([
                    'name' => "Staff Member {$store->name}",
                    'email' => "staff{$index + 1}@phonehospital.com",
                    'password' => Hash::make('password'),
                    'role' => 'store',
                    'store_id' => $store->id,
                ]);
            }
        }

        $this->command->info('Users created successfully!');
        $this->command->info('Admin: admin@phonehospital.com / password');
        $this->command->info('Store Users: store1@phonehospital.com / password');
    }
}

