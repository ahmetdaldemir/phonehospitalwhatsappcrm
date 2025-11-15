<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    protected TicketService $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * Create a draft order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createDraft(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'uuid|exists:products,id',
            'ticket_id' => 'nullable|uuid|exists:tickets,id',
            'store_id' => 'nullable|uuid|exists:stores,id',
        ]);

        try {
            DB::beginTransaction();

            $customer = Customer::findOrFail($request->customer_id);
            $products = Product::whereIn('id', $request->product_ids)
                ->active()
                ->inStock()
                ->get();

            if ($products->isEmpty()) {
                return response()->json([
                    'message' => 'No valid products found.',
                ], 422);
            }

            // Calculate total price
            $totalPrice = $products->sum('price');

            // Create order
            $order = Order::create([
                'customer_id' => $customer->id,
                'ticket_id' => $request->ticket_id,
                'store_id' => $request->store_id,
                'total_price' => $totalPrice,
                'payment_status' => 'pending',
                'order_status' => 'draft',
            ]);

            // Create order items
            foreach ($products as $product) {
                $order->orderItems()->create([
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => $product->price,
                ]);
            }

            // Recalculate total (in case of future discounts)
            $order->total_price = $order->calculateTotal();
            $order->save();

            DB::commit();

            return response()->json([
                'order_id' => $order->id,
                'total_price' => $order->total_price,
                'store' => $order->store ? [
                    'id' => $order->store->id,
                    'name' => $order->store->name,
                    'address' => $order->store->address,
                ] : null,
                'pickup_instructions' => $this->getPickupInstructions($order),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to create order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pickup instructions for order.
     *
     * @param  \App\Models\Order  $order
     * @return string
     */
    protected function getPickupInstructions(Order $order): string
    {
        if ($order->store) {
            return sprintf(
                "Siparişiniz hazır olduğunda %s mağazamızdan teslim alabilirsiniz.\n\nAdres: %s\n\nSipariş numaranız: %s",
                $order->store->name,
                $order->store->address ?? 'Adres bilgisi için mağazamızla iletişime geçin',
                substr($order->id, 0, 8)
            );
        }

        return sprintf(
            "Siparişiniz hazır olduğunda size bildirilecektir.\n\nSipariş numaranız: %s",
            substr($order->id, 0, 8)
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'store', 'orderItems.product']);

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by store
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Filter by status
        if ($request->has('order_status')) {
            $query->status($request->order_status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->paymentStatus($request->payment_status);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($orders);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $order = Order::with(['customer', 'store', 'orderItems.product', 'ticket'])
            ->findOrFail($id);

        return response()->json($order);
    }

    /**
     * Update order status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, string $id)
    {
        $request->validate([
            'order_status' => 'sometimes|in:draft,in_store,on_the_way,delivered',
            'payment_status' => 'sometimes|in:pending,paid,canceled',
        ]);

        $order = Order::findOrFail($id);
        $order->update($request->only(['order_status', 'payment_status']));

        return response()->json($order->load(['customer', 'store', 'orderItems.product']));
    }
}

