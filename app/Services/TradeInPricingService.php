<?php

namespace App\Services;

use App\Models\TradeIn;
use App\Models\TradeInBasePrice;
use Illuminate\Support\Facades\Log;

class TradeInPricingService
{
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
     * Price margin percentage (±10%).
     *
     * @var float
     */
    protected float $priceMargin = 0.10;

    /**
     * Get base price for device.
     *
     * @param  string  $brand
     * @param  string  $model
     * @param  string|null  $storage
     * @return int|null
     */
    public function getBasePrice(string $brand, string $model, ?string $storage = null): ?int
    {
        $brand = trim($brand);
        $model = trim($model);
        $storage = $storage ? trim($storage) : null;

        // Try exact match first
        if ($storage) {
            $basePrice = TradeInBasePrice::active()
                ->forDevice($brand, $model, $storage)
                ->value('base_price');

            if ($basePrice) {
                Log::info('Base price found (exact match)', [
                    'brand' => $brand,
                    'model' => $model,
                    'storage' => $storage,
                    'base_price' => $basePrice,
                ]);
                return $basePrice;
            }
        }

        // Try without storage (fallback)
        $basePrice = TradeInBasePrice::active()
            ->where('brand', $brand)
            ->where('model', $model)
            ->where('storage', '')
            ->value('base_price');

        if ($basePrice) {
            Log::info('Base price found (without storage)', [
                'brand' => $brand,
                'model' => $model,
                'base_price' => $basePrice,
            ]);
            return $basePrice;
        }

        // Try case-insensitive match
        $basePrice = TradeInBasePrice::active()
            ->whereRaw('LOWER(brand) = ?', [strtolower($brand)])
            ->whereRaw('LOWER(model) = ?', [strtolower($model)])
            ->when($storage, function ($query) use ($storage) {
                return $query->where('storage', $storage);
            })
            ->value('base_price');

        if ($basePrice) {
            Log::info('Base price found (case-insensitive)', [
                'brand' => $brand,
                'model' => $model,
                'storage' => $storage,
                'base_price' => $basePrice,
            ]);
            return $basePrice;
        }

        Log::warning('Base price not found', [
            'brand' => $brand,
            'model' => $model,
            'storage' => $storage,
        ]);

        return null;
    }

    /**
     * Calculate price based on base price, condition, and battery health.
     *
     * @param  int  $basePrice
     * @param  string  $condition
     * @param  int|null  $batteryHealth
     * @return array{min: int, max: int, calculated: int}
     */
    public function calculatePrice(int $basePrice, string $condition = 'B', ?int $batteryHealth = null): array
    {
        // Apply condition multiplier
        $conditionMultiplier = $this->conditionMultipliers[$condition] ?? 0.85;
        $adjustedPrice = $basePrice * $conditionMultiplier;

        // Apply battery health multiplier if provided
        if ($batteryHealth !== null) {
            $batteryMultiplier = $this->getBatteryMultiplier($batteryHealth);
            $adjustedPrice = $adjustedPrice * $batteryMultiplier;
        }

        // Calculate min and max with margin
        $calculated = (int) round($adjustedPrice);
        $min = (int) round($calculated * (1 - $this->priceMargin));
        $max = (int) round($calculated * (1 + $this->priceMargin));

        Log::info('Price calculated', [
            'base_price' => $basePrice,
            'condition' => $condition,
            'battery_health' => $batteryHealth,
            'calculated' => $calculated,
            'min' => $min,
            'max' => $max,
        ]);

        return [
            'min' => $min,
            'max' => $max,
            'calculated' => $calculated,
        ];
    }

    /**
     * Apply manual override to trade-in final price.
     *
     * @param  string  $tradeInId
     * @param  int  $finalPrice
     * @return \App\Models\TradeIn
     */
    public function applyManualOverride(string $tradeInId, int $finalPrice): TradeIn
    {
        $tradeIn = TradeIn::findOrFail($tradeInId);

        $oldFinalPrice = $tradeIn->final_price;
        $tradeIn->update(['final_price' => $finalPrice]);

        Log::info('Manual price override applied', [
            'tradein_id' => $tradeInId,
            'old_final_price' => $oldFinalPrice,
            'new_final_price' => $finalPrice,
        ]);

        return $tradeIn->fresh();
    }

    /**
     * Calculate price for trade-in (full flow).
     *
     * @param  string  $brand
     * @param  string  $model
     * @param  string|null  $storage
     * @param  string  $condition
     * @param  int|null  $batteryHealth
     * @return array{min: int, max: int, calculated: int, base_price: int|null}
     */
    public function calculateTradeInPrice(
        string $brand,
        string $model,
        ?string $storage,
        string $condition = 'B',
        ?int $batteryHealth = null
    ): array {
        $basePrice = $this->getBasePrice($brand, $model, $storage);

        if (!$basePrice) {
            return [
                'min' => 0,
                'max' => 0,
                'calculated' => 0,
                'base_price' => null,
            ];
        }

        $priceResult = $this->calculatePrice($basePrice, $condition, $batteryHealth);

        return array_merge($priceResult, [
            'base_price' => $basePrice,
        ]);
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
}

