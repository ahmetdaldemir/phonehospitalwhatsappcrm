<?php

namespace Database\Factories;

use App\Models\BotSession;
use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BotSession>
 */
class BotSessionFactory extends Factory
{
    protected $model = BotSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Uuid::uuid4(),
            'phone_number' => fake()->phoneNumber(),
            'current_state' => 'start',
            'data' => [],
            'customer_id' => null,
            'ticket_id' => null,
            'last_interaction_at' => now(),
        ];
    }

    /**
     * Indicate that the session has a customer.
     */
    public function withCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => Customer::factory(),
        ]);
    }

    /**
     * Indicate that the session has a ticket.
     */
    public function withTicket(): static
    {
        return $this->state(fn (array $attributes) => [
            'ticket_id' => Ticket::factory(),
            'customer_id' => Customer::factory(),
        ]);
    }

    /**
     * Set a specific state.
     */
    public function inState(string $state): static
    {
        return $this->state(fn (array $attributes) => [
            'current_state' => $state,
        ]);
    }
}

