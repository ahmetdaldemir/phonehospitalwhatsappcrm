<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductRecommendation;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get recommended products for a device model.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recommended(Request $request)
    {
        $request->validate([
            'model' => 'required|string|max:255',
        ]);

        $model = $request->input('model');

        // Get recommended products for this model
        $recommendations = ProductRecommendation::with('product')
            ->forModel($model)
            ->orderedByPriority()
            ->whereHas('product', function ($query) {
                $query->active()->inStock();
            })
            ->limit(3)
            ->get();

        // If we have recommendations, return them
        if ($recommendations->isNotEmpty()) {
            $products = $recommendations->map(function ($recommendation) {
                return $this->formatProduct($recommendation->product);
            });

            return response()->json([
                'products' => $products,
                'source' => 'recommendations',
            ]);
        }

        // Fallback: Get random active products in stock
        $randomProducts = Product::active()
            ->inStock()
            ->inRandomOrder()
            ->limit(3)
            ->get();

        $products = $randomProducts->map(function ($product) {
            return $this->formatProduct($product);
        });

        return response()->json([
            'products' => $products,
            'source' => 'random',
        ]);
    }

    /**
     * Format product for API response.
     *
     * @param  \App\Models\Product  $product
     * @return array
     */
    protected function formatProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'description' => $product->description,
            'brand' => $product->brand,
            'model' => $product->model,
            'price' => $product->price,
            'stock' => $product->stock,
            'first_image' => $product->first_image,
            'images' => $product->images,
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // Filter by brand
        if ($request->has('brand')) {
            $query->where('brand', $request->brand);
        }

        // Filter by model
        if ($request->has('model')) {
            $query->where('model', $request->model);
        }

        // Only active products
        $query->active();

        // Only in stock
        if ($request->boolean('in_stock_only', true)) {
            $query->inStock();
        }

        $products = $query->paginate(15);

        return response()->json($products);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $product = Product::findOrFail($id);

        return response()->json($this->formatProduct($product));
    }
}

