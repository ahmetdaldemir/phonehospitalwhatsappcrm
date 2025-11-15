<?php

namespace App\Models;

class TradeInBasePrice extends BaseModel
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'brand',
        'model',
        'storage',
        'base_price',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'base_price' => 'integer',
        'active' => 'boolean',
    ];

    /**
     * Scope a query to only include active base prices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to filter by brand, model, and storage.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $brand
     * @param  string  $model
     * @param  string  $storage
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDevice($query, string $brand, string $model, string $storage)
    {
        return $query->where('brand', $brand)
            ->where('model', $model)
            ->where('storage', $storage);
    }
}

