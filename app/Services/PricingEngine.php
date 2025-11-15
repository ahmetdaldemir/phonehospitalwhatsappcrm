<?php

namespace App\Services;

use App\Models\PriceMatrix;

class PricingEngine
{
    /**
     * Get pricing for a device and problem type.
     *
     * @param  string  $brand
     * @param  string  $model
     * @param  string  $problemType
     * @return array{price_min: int|null, price_max: int|null, needs_manual_quote: bool}
     */
    public function getPricing(string $brand, string $model, string $problemType): array
    {
        // Normalize inputs (trim and case-insensitive search)
        $brand = $this->normalizeString($brand);
        $model = $this->normalizeString($model);
        $problemType = $this->normalizeString($problemType);

        // Search for exact match
        $priceMatrix = PriceMatrix::whereRaw('LOWER(TRIM(brand)) = ?', [strtolower($brand)])
            ->whereRaw('LOWER(TRIM(model)) = ?', [strtolower($model)])
            ->whereRaw('LOWER(TRIM(problem_type)) = ?', [strtolower($problemType)])
            ->first();

        if (!$priceMatrix) {
            return [
                'price_min' => null,
                'price_max' => null,
                'needs_manual_quote' => true,
            ];
        }

        return [
            'price_min' => $priceMatrix->price_min,
            'price_max' => $priceMatrix->price_max,
            'needs_manual_quote' => false,
        ];
    }

    /**
     * Check if pricing exists for a device and problem type.
     *
     * @param  string  $brand
     * @param  string  $model
     * @param  string  $problemType
     * @return bool
     */
    public function hasPricing(string $brand, string $model, string $problemType): bool
    {
        $pricing = $this->getPricing($brand, $model, $problemType);
        return !$pricing['needs_manual_quote'];
    }

    /**
     * Normalize string for comparison.
     *
     * @param  string  $value
     * @return string
     */
    protected function normalizeString(string $value): string
    {
        return trim($value);
    }
}

