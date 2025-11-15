<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TradeIn;
use App\Services\TradeInPricingService;
use App\Services\TradeInPaymentRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TradeInController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = TradeIn::with(['customer', 'store']);

        // Filter by status
        if ($request->has('status')) {
            $query->status($request->status);
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by store
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Filter by brand
        if ($request->has('brand')) {
            $query->where('brand', $request->brand);
        }

        // Filter by model
        if ($request->has('model')) {
            $query->where('model', 'like', '%' . $request->model . '%');
        }

        $tradeIns = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($tradeIns);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $tradeIn = TradeIn::with(['customer', 'store'])->findOrFail($id);

        // Get recommended payment option
        $recommendationService = new TradeInPaymentRecommendationService();
        $recommendedOption = $recommendationService->getRecommendedOption($tradeIn);

        return response()->json([
            ...$tradeIn->toArray(),
            'recommended_payment_option' => $recommendedOption,
        ]);
    }

    /**
     * Update the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'status' => 'sometimes|in:new,waiting_device,completed,canceled',
            'offer_min' => 'sometimes|integer|min:0',
            'offer_max' => 'sometimes|integer|min:0|gte:offer_min',
            'store_id' => 'sometimes|nullable|uuid|exists:stores,id',
        ]);

        $tradeIn = TradeIn::findOrFail($id);
        
        $oldStatus = $tradeIn->status;
        $tradeIn->update($request->only(['status', 'offer_min', 'offer_max', 'store_id']));

        // Log status change
        if ($oldStatus !== $tradeIn->status) {
            Log::info('Trade-in status updated', [
                'tradein_id' => $tradeIn->id,
                'old_status' => $oldStatus,
                'new_status' => $tradeIn->status,
                'user_id' => $request->user()?->id,
            ]);
        }

        return response()->json($tradeIn->load(['customer', 'store']));
    }

    /**
     * Update status only.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:new,waiting_device,completed,canceled',
        ]);

        $tradeIn = TradeIn::findOrFail($id);
        $oldStatus = $tradeIn->status;
        $tradeIn->update(['status' => $request->status]);

        Log::info('Trade-in status updated', [
            'tradein_id' => $tradeIn->id,
            'old_status' => $oldStatus,
            'new_status' => $tradeIn->status,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json($tradeIn->load(['customer', 'store']));
    }

    /**
     * Update final price.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePrice(Request $request, string $id)
    {
        $request->validate([
            'offer_min' => 'required|integer|min:0',
            'offer_max' => 'required|integer|min:0|gte:offer_min',
        ]);

        $tradeIn = TradeIn::findOrFail($id);
        $tradeIn->update([
            'offer_min' => $request->offer_min,
            'offer_max' => $request->offer_max,
        ]);

        Log::info('Trade-in price updated', [
            'tradein_id' => $tradeIn->id,
            'offer_min' => $tradeIn->offer_min,
            'offer_max' => $tradeIn->offer_max,
            'user_id' => $request->user()?->id,
        ]);

        return response()->json($tradeIn->load(['customer', 'store']));
    }

    /**
     * Update final price (manual override).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateFinalPrice(Request $request, string $id)
    {
        $request->validate([
            'final_price' => 'nullable|integer|min:0',
        ]);

        $tradeIn = TradeIn::findOrFail($id);
        
        if ($request->final_price === null) {
            // Clear override
            $tradeIn->update(['final_price' => null]);
            Log::info('Trade-in final price override cleared', [
                'tradein_id' => $tradeIn->id,
                'user_id' => $request->user()?->id,
            ]);
        } else {
            // Apply override
            $pricingService = new TradeInPricingService();
            $tradeIn = $pricingService->applyManualOverride($id, $request->final_price);
        }

        return response()->json($tradeIn->load(['customer', 'store']));
    }

    /**
     * Calculate price preview for trade-in.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculatePricePreview(Request $request, string $id)
    {
        $tradeIn = TradeIn::findOrFail($id);

        $pricingService = new TradeInPricingService();
        $priceResult = $pricingService->calculateTradeInPrice(
            $tradeIn->brand,
            $tradeIn->model,
            $tradeIn->storage,
            $tradeIn->condition,
            $tradeIn->battery_health
        );

        return response()->json($priceResult);
    }

    /**
     * Get statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $stats = [
            'total' => TradeIn::count(),
            'new' => TradeIn::status('new')->count(),
            'waiting_device' => TradeIn::status('waiting_device')->count(),
            'completed' => TradeIn::status('completed')->count(),
            'canceled' => TradeIn::status('canceled')->count(),
            'by_condition' => [
                'A' => TradeIn::where('condition', 'A')->count(),
                'B' => TradeIn::where('condition', 'B')->count(),
                'C' => TradeIn::where('condition', 'C')->count(),
            ],
            'by_payment_option' => [
                'cash' => TradeIn::where('payment_option', 'cash')->count(),
                'voucher' => TradeIn::where('payment_option', 'voucher')->count(),
                'tradein' => TradeIn::where('payment_option', 'tradein')->count(),
                'null' => TradeIn::whereNull('payment_option')->count(),
            ],
            'total_offer_value' => TradeIn::whereNotNull('offer_max')
                ->sum('offer_max'),
            'revenue_by_payment_option' => [
                'cash' => TradeIn::where('payment_option', 'cash')
                    ->whereNotNull('final_price')
                    ->sum('final_price'),
                'voucher' => TradeIn::where('payment_option', 'voucher')
                    ->whereNotNull('final_price')
                    ->sum('final_price'),
                'tradein' => TradeIn::where('payment_option', 'tradein')
                    ->whereNotNull('final_price')
                    ->sum('final_price'),
            ],
            'conversion_by_payment_option' => [
                'cash' => $this->calculateConversionRate('cash'),
                'voucher' => $this->calculateConversionRate('voucher'),
                'tradein' => $this->calculateConversionRate('tradein'),
            ],
        ];

        return response()->json($stats);
    }

    /**
     * Calculate conversion rate for payment option.
     *
     * @param  string  $paymentOption
     * @return float
     */
    protected function calculateConversionRate(string $paymentOption): float
    {
        $total = TradeIn::where('payment_option', $paymentOption)->count();
        
        if ($total === 0) {
            return 0.0;
        }

        $completed = TradeIn::where('payment_option', $paymentOption)
            ->where('status', 'completed')
            ->count();

        return round(($completed / $total) * 100, 2);
    }
}

