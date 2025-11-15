<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TradeInPriceEngine
{
    /**
     * Base prices for common phone models (in TL).
     * In production, this should come from a database table.
     *
     * @var array
     */
    protected array $basePrices = [
        'Apple' => [
            'iPhone 15 Pro Max' => 45000,
            'iPhone 15 Pro' => 40000,
            'iPhone 15' => 35000,
            'iPhone 14 Pro Max' => 38000,
            'iPhone 14 Pro' => 33000,
            'iPhone 14' => 28000,
            'iPhone 13 Pro Max' => 30000,
            'iPhone 13 Pro' => 26000,
            'iPhone 13' => 22000,
            'iPhone 12 Pro Max' => 25000,
            'iPhone 12 Pro' => 21000,
            'iPhone 12' => 18000,
            'iPhone 11 Pro Max' => 20000,
            'iPhone 11 Pro' => 17000,
            'iPhone 11' => 14000,
        ],
        'Samsung' => [
            'Galaxy S24 Ultra' => 40000,
            'Galaxy S24+' => 35000,
            'Galaxy S24' => 30000,
            'Galaxy S23 Ultra' => 35000,
            'Galaxy S23+' => 30000,
            'Galaxy S23' => 25000,
            'Galaxy S22 Ultra' => 28000,
            'Galaxy S22+' => 24000,
            'Galaxy S22' => 20000,
        ],
        'Xiaomi' => [
            'Mi 13 Ultra' => 25000,
            'Mi 13 Pro' => 20000,
            'Mi 13' => 15000,
            'Redmi Note 12 Pro' => 8000,
            'Redmi Note 12' => 6000,
        ],
    ];

    /**
     * Condition multipliers.
     *
     * @var array
     */
    protected array $conditionMultipliers = [
        'A' => 1.0,   // Perfect condition - 100%
        'B' => 0.85,  // Good condition - 85%
        'C' => 0.70,  // Fair condition - 70%
    ];

    /**
     * Battery health multipliers.
     *
     * @var array
     */
    protected array $batteryMultipliers = [
        100 => 1.0,
        95 => 0.95,
        90 => 0.90,
        85 => 0.85,
        80 => 0.80,
        75 => 0.75,
        70 => 0.70,
        65 => 0.65,
        60 => 0.60,
        50 => 0.50,
    ];

    /**
     * Storage multipliers.
     *
     * @var array
     */
    protected array $storageMultipliers = [
        '64GB' => 1.0,
        '128GB' => 1.1,
        '256GB' => 1.2,
        '512GB' => 1.3,
        '1TB' => 1.4,
    ];

    /**
     * Calculate trade-in price range.
     *
     * @param  string  $brand
     * @param  string  $model
     * @param  string  $condition
     * @param  int|null  $batteryHealth
     * @param  string|null  $storage
     * @return array{min: int, max: int, base_price: int|null}
     */
    public function calculatePrice(
        string $brand,
        string $model,
        string $condition = 'B',
        ?int $batteryHealth = null,
        ?string $storage = null
    ): array {
        // Get base price
        $basePrice = $this->getBasePrice($brand, $model);

        if (!$basePrice) {
            Log::warning('Trade-in price calculation: Model not found', [
                'brand' => $brand,
                'model' => $model,
            ]);

            return [
                'min' => 0,
                'max' => 0,
                'base_price' => null,
            ];
        }

        // Apply condition multiplier
        $conditionMultiplier = $this->conditionMultipliers[$condition] ?? 0.75;
        $adjustedPrice = $basePrice * $conditionMultiplier;

        // Apply storage multiplier if provided
        if ($storage) {
            $storageMultiplier = $this->storageMultipliers[$storage] ?? 1.0;
            $adjustedPrice = $adjustedPrice * $storageMultiplier;
        }

        // Apply battery health multiplier if provided
        if ($batteryHealth !== null) {
            $batteryMultiplier = $this->getBatteryMultiplier($batteryHealth);
            $adjustedPrice = $adjustedPrice * $batteryMultiplier;
        }

        // Calculate min and max (10% range)
        $min = (int) ($adjustedPrice * 0.90);
        $max = (int) ($adjustedPrice * 1.10);

        Log::info('Trade-in price calculated', [
            'brand' => $brand,
            'model' => $model,
            'condition' => $condition,
            'battery_health' => $batteryHealth,
            'storage' => $storage,
            'base_price' => $basePrice,
            'min' => $min,
            'max' => $max,
        ]);

        return [
            'min' => $min,
            'max' => $max,
            'base_price' => $basePrice,
        ];
    }

    /**
     * Get base price for model.
     *
     * @param  string  $brand
     * @param  string  $model
     * @return int|null
     */
    protected function getBasePrice(string $brand, string $model): ?int
    {
        $brand = ucfirst(trim($brand));
        $model = trim($model);

        // Try exact match first
        if (isset($this->basePrices[$brand][$model])) {
            return $this->basePrices[$brand][$model];
        }

        // Try case-insensitive match
        foreach ($this->basePrices[$brand] ?? [] as $key => $price) {
            if (strcasecmp($key, $model) === 0) {
                return $price;
            }
        }

        // Try partial match
        foreach ($this->basePrices[$brand] ?? [] as $key => $price) {
            if (stripos($key, $model) !== false || stripos($model, $key) !== false) {
                return $price;
            }
        }

        return null;
    }

    /**
     * Get battery health multiplier.
     *
     * @param  int  $batteryHealth
     * @return float
     */
    protected function getBatteryMultiplier(int $batteryHealth): float
    {
        // Round to nearest 5
        $rounded = round($batteryHealth / 5) * 5;

        // Get exact match or closest lower value
        if (isset($this->batteryMultipliers[$rounded])) {
            return $this->batteryMultipliers[$rounded];
        }

        // Find closest lower value
        $closest = 100;
        foreach ($this->batteryMultipliers as $health => $multiplier) {
            if ($health <= $batteryHealth && $health < $closest) {
                $closest = $health;
            }
        }

        return $this->batteryMultipliers[$closest] ?? 0.50;
    }

    /**
     * Check if model is supported.
     *
     * @param  string  $brand
     * @param  string  $model
     * @return bool
     */
    public function isModelSupported(string $brand, string $model): bool
    {
        return $this->getBasePrice($brand, $model) !== null;
    }
}

