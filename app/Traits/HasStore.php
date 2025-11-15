<?php

namespace App\Traits;

use App\Models\Store;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasStore
{
    /**
     * Get the store that owns the model.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Scope a query to only include records for a specific store.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $storeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }
}

