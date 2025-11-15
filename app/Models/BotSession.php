<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotSession extends BaseModel
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone_number',
        'current_state',
        'data',
        'customer_id',
        'ticket_id',
        'last_interaction_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'last_interaction_at' => 'datetime',
    ];

    /**
     * Get the customer associated with the session.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the ticket associated with the session.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Update session state.
     *
     * @param  string  $state
     * @param  array  $data
     * @return void
     */
    public function updateState(string $state, array $data = []): void
    {
        $currentData = $this->data ?? [];
        $this->update([
            'current_state' => $state,
            'data' => array_merge($currentData, $data),
            'last_interaction_at' => now(),
        ]);
    }

    /**
     * Get or create session by phone number.
     *
     * @param  string  $phoneNumber
     * @return self
     */
    public static function getOrCreate(string $phoneNumber): self
    {
        return static::firstOrCreate(
            ['phone_number' => $phoneNumber],
            [
                'current_state' => 'start',
                'data' => [],
                'last_interaction_at' => now(),
            ]
        );
    }
}

