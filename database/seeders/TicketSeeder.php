<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Store;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();
        $stores = Store::all();

        if ($customers->isEmpty() || $stores->isEmpty()) {
            $this->command->warn('Please run CustomerSeeder and StoreSeeder first!');
            return;
        }

        // Create new tickets
        Ticket::factory()
            ->count(20)
            ->new()
            ->create();

        // Create directed tickets
        Ticket::factory()
            ->count(15)
            ->directed()
            ->create();

        // Create completed tickets
        Ticket::factory()
            ->count(30)
            ->completed()
            ->create();

        // Create canceled tickets
        Ticket::factory()
            ->count(5)
            ->create([
                'status' => 'canceled',
            ]);

        // Create tickets with photos
        Ticket::factory()
            ->count(10)
            ->create([
                'photos' => [
                    'uploads/tickets/photo1.jpg',
                    'uploads/tickets/photo2.jpg',
                    'uploads/tickets/photo3.jpg',
                ],
            ]);

        // Create tickets for specific customers
        $johnDoe = Customer::where('phone_number', '+1234567890')->first();
        if ($johnDoe) {
            Ticket::factory()
                ->count(3)
                ->create([
                    'customer_id' => $johnDoe->id,
                    'status' => 'completed',
                ]);
        }
    }
}

