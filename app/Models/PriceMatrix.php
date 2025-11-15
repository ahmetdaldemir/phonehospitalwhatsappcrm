<?php

namespace App\Models;

class PriceMatrix extends BaseModel
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'price_matrix';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'brand',
        'model',
        'problem_type',
        'price_min',
        'price_max',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_min' => 'integer',
        'price_max' => 'integer',
    ];

    /**
     * Find price range for a specific brand, model, and problem type.
     *
     * @param  string  $brand
     * @param  string  $model
     * @param  string  $problemType
     * @return self|null
     */
    public static function findPrice(string $brand, string $model, string $problemType): ?self
    {
        return static::where('brand', $brand)
            ->where('model', $model)
            ->where('problem_type', $problemType)
            ->first();
    }
}

