<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends BaseModel
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ticket_id',
        'customer_id',
        'store_id',
        'total_price',
        'payment_status',
        'order_status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_price' => 'integer',
    ];

    /**
     * Get the ticket that owns the order.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the customer that owns the order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the store that owns the order.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the order items for the order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Calculate total price from order items.
     *
     * @return int
     */
    public function calculateTotal(): int
    {
        return $this->orderItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });
    }

    /**
     * Scope a query to filter by order status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('order_status', $status);
    }

    /**
     * Scope a query to filter by payment status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaymentStatus($query, string $status)
    {
        return $query->where('payment_status', $status);
    }
}

