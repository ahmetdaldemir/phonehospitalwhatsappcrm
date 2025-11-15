<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRecommendation extends BaseModel
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'model',
        'product_id',
        'priority',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'priority' => 'integer',
    ];

    /**
     * Get the product that owns the recommendation.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope a query to filter by model.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForModel($query, string $model)
    {
        return $query->where('model', $model);
    }

    /**
     * Scope a query to order by priority.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}

