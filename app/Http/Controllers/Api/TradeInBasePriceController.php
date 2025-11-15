<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TradeInBasePrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TradeInBasePriceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = TradeInBasePrice::query();

        // Filter by brand
        if ($request->has('brand')) {
            $query->where('brand', $request->brand);
        }

        // Filter by model
        if ($request->has('model')) {
            $query->where('model', 'like', '%' . $request->model . '%');
        }

        // Filter by storage
        if ($request->has('storage')) {
            $query->where('storage', $request->storage);
        }

        // Filter by active status
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        } else {
            // Show active by default
            $query->active();
        }

        $basePrices = $query->orderBy('brand')
            ->orderBy('model')
            ->orderBy('storage')
            ->paginate(15);

        return response()->json($basePrices);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'storage' => 'required|string|max:50',
            'base_price' => 'required|integer|min:0',
            'active' => 'sometimes|boolean',
        ]);

        $basePrice = TradeInBasePrice::create([
            'brand' => $request->brand,
            'model' => $request->model,
            'storage' => $request->storage,
            'base_price' => $request->base_price,
            'active' => $request->boolean('active', true),
        ]);

        Log::info('Trade-in base price created', [
            'id' => $basePrice->id,
            'brand' => $basePrice->brand,
            'model' => $basePrice->model,
            'storage' => $basePrice->storage,
            'base_price' => $basePrice->base_price,
        ]);

        return response()->json($basePrice, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $basePrice = TradeInBasePrice::findOrFail($id);

        return response()->json($basePrice);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'brand' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'storage' => 'sometimes|string|max:50',
            'base_price' => 'sometimes|integer|min:0',
            'active' => 'sometimes|boolean',
        ]);

        $basePrice = TradeInBasePrice::findOrFail($id);
        $oldPrice = $basePrice->base_price;
        
        $basePrice->update($request->only(['brand', 'model', 'storage', 'base_price', 'active']));

        Log::info('Trade-in base price updated', [
            'id' => $basePrice->id,
            'old_price' => $oldPrice,
            'new_price' => $basePrice->base_price,
        ]);

        return response()->json($basePrice);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $basePrice = TradeInBasePrice::findOrFail($id);
        $basePrice->delete();

        Log::info('Trade-in base price deleted', [
            'id' => $id,
        ]);

        return response()->json(['message' => 'Base price deleted successfully']);
    }
}

