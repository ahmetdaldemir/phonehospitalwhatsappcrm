<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeIn extends BaseModel
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'brand',
        'model',
        'storage',
        'color',
        'condition',
        'battery_health',
        'photos',
        'offer_min',
        'offer_max',
        'final_price',
        'payment_option',
        'store_id',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'battery_health' => 'integer',
        'photos' => 'array',
        'offer_min' => 'integer',
        'offer_max' => 'integer',
        'final_price' => 'integer',
    ];

    /**
     * Get the customer that owns the trade-in.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the store that owns the trade-in.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Scope a query to filter by status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get condition label.
     *
     * @return string
     */
    public function getConditionLabelAttribute(): string
    {
        return match($this->condition) {
            'A' => 'Mükemmel',
            'B' => 'İyi',
            'C' => 'Orta',
            default => 'Bilinmiyor',
        };
    }

    /**
     * Get status label.
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'new' => 'Yeni',
            'waiting_device' => 'Cihaz Bekleniyor',
            'completed' => 'Tamamlandı',
            'canceled' => 'İptal Edildi',
            default => 'Bilinmiyor',
        };
    }
}

