<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends BaseModel
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone_number',
        'name',
        'total_visits',
        'last_visit_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_visit_at' => 'datetime',
        'total_visits' => 'integer',
    ];

    /**
     * Get the tickets for the customer.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the orders for the customer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the trade-ins for the customer.
     */
    public function tradeIns(): HasMany
    {
        return $this->hasMany(TradeIn::class);
    }

    /**
     * Increment the visit count and update last visit.
     */
    public function recordVisit(): void
    {
        $this->increment('total_visits');
        $this->update(['last_visit_at' => now()]);
    }
}

