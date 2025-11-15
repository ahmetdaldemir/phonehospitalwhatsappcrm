<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\Log;

class TradeInPaymentRecommendationService
{
    /**
     * Store margin threshold for recommending trade-in option.
     *
     * @var float
     */
    protected float $storeMarginThreshold = 0.30; // 30%

    /**
     * Accessory stock threshold for recommending voucher option.
     *
     * @var int
     */
    protected int $accessoryStockThreshold = 50;

    /**
     * Get recommended payment option for trade-in.
     *
     * @param  \App\Models\TradeIn  $tradeIn
     * @return string
     */
    public function getRecommendedOption($tradeIn): string
    {
        // Check store margin if store is assigned
        if ($tradeIn->store_id) {
            $storeMargin = $this->calculateStoreMargin($tradeIn->store_id);
            
            if ($storeMargin >= $this->storeMarginThreshold) {
                Log::info('Trade-in payment recommendation: tradein (high store margin)', [
                    'tradein_id' => $tradeIn->id,
                    'store_margin' => $storeMargin,
                ]);
                return 'tradein';
            }
        }

        // Check accessory stock
        $accessoryStock = $this->getAccessoryStockLevel();
        
        if ($accessoryStock >= $this->accessoryStockThreshold) {
            Log::info('Trade-in payment recommendation: voucher (high accessory stock)', [
                'tradein_id' => $tradeIn->id,
                'accessory_stock' => $accessoryStock,
            ]);
            return 'voucher';
        }

        // Default to cash
        Log::info('Trade-in payment recommendation: cash (default)', [
            'tradein_id' => $tradeIn->id,
        ]);
        return 'cash';
    }

    /**
     * Calculate store margin percentage.
     *
     * @param  string  $storeId
     * @return float
     */
    protected function calculateStoreMargin(string $storeId): float
    {
        // This is a simplified calculation
        // In production, you might want to calculate based on:
        // - Store's average profit margin
        // - Recent sales performance
        // - Inventory turnover
        
        $store = Store::find($storeId);
        if (!$store) {
            return 0.0;
        }

        // Example: Calculate based on store's ticket completion rate
        // Higher completion rate = higher margin assumption
        $completedTickets = $store->tickets()
            ->where('status', 'completed')
            ->count();
        
        $totalTickets = $store->tickets()->count();
        
        if ($totalTickets === 0) {
            return 0.0;
        }

        $completionRate = $completedTickets / $totalTickets;
        
        // Assume margin correlates with completion rate
        // Adjust this logic based on your business needs
        return min(0.50, $completionRate * 0.6); // Max 50% margin
    }

    /**
     * Get total accessory stock level.
     *
     * @return int
     */
    protected function getAccessoryStockLevel(): int
    {
        return Product::active()
            ->inStock()
            ->sum('stock');
    }

    /**
     * Get payment option label.
     *
     * @param  string|null  $option
     * @return string
     */
    public function getOptionLabel(?string $option): string
    {
        return match($option) {
            'cash' => 'Nakit',
            'voucher' => 'Aksesuar Hediye Çeki',
            'tradein' => 'Yeni cihazda indirim',
            default => 'Seçilmedi',
        };
    }

    /**
     * Get payment option description.
     *
     * @param  string|null  $option
     * @return string
     */
    public function getOptionDescription(?string $option): string
    {
        return match($option) {
            'cash' => 'Nakit ödeme',
            'voucher' => 'Aksesuar alışverişinde kullanabileceğiniz hediye çeki',
            'tradein' => 'Yeni telefon alırken indirim olarak kullanma',
            default => '',
        };
    }
}

