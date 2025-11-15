<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     * Admin can see all tickets, store users can only see tickets from their store.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Ticket::with(['customer', 'store']);

        // Store users can only see tickets from their store
        if ($user->role === 'store' && $user->store_id) {
            $query->where('store_id', $user->store_id);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by customer_id if provided
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by store_id (admin only)
        if ($request->has('store_id') && $user->role === 'admin') {
            $query->where('store_id', $request->store_id);
        }

        // Search by brand or model
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('problem_type', 'like', "%{$search}%");
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($tickets);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'problem_type' => 'required|string|max:255',
            'price_min' => 'nullable|integer|min:0',
            'price_max' => 'nullable|integer|min:0|gte:price_min',
            'store_id' => 'nullable|uuid|exists:stores,id',
            'status' => 'nullable|in:new,directed,completed,canceled',
            'photos' => 'nullable|array',
            'photos.*' => 'string',
        ]);

        // If store user, automatically assign to their store
        if ($user->role === 'store' && $user->store_id) {
            $request->merge(['store_id' => $user->store_id]);
        }

        $ticket = Ticket::create($request->all());

        return response()->json($ticket->load(['customer', 'store']), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $ticket = Ticket::with(['customer', 'store'])->findOrFail($id);

        // Store users can only see tickets from their store
        if ($user->role === 'store' && $user->store_id) {
            if ($ticket->store_id !== $user->store_id) {
                return response()->json([
                    'message' => 'Unauthorized. You can only view tickets from your store.',
                ], 403);
            }
        }

        return response()->json($ticket);
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
        $user = $request->user();
        $ticket = Ticket::findOrFail($id);

        // Store users can only update tickets from their store
        if ($user->role === 'store' && $user->store_id) {
            if ($ticket->store_id !== $user->store_id) {
                return response()->json([
                    'message' => 'Unauthorized. You can only update tickets from your store.',
                ], 403);
            }
        }

        $request->validate([
            'customer_id' => 'sometimes|uuid|exists:customers,id',
            'brand' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'problem_type' => 'sometimes|string|max:255',
            'price_min' => 'nullable|integer|min:0',
            'price_max' => 'nullable|integer|min:0|gte:price_min',
            'store_id' => 'nullable|uuid|exists:stores,id',
            'status' => 'sometimes|in:new,directed,completed,canceled',
            'photos' => 'nullable|array',
            'photos.*' => 'string',
        ]);

        // Store users cannot change store_id
        if ($user->role === 'store' && $user->store_id) {
            $request->offsetUnset('store_id');
        }

        $ticket->update($request->all());

        return response()->json($ticket->load(['customer', 'store']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $ticket = Ticket::findOrFail($id);

        // Store users can only delete tickets from their store
        if ($user->role === 'store' && $user->store_id) {
            if ($ticket->store_id !== $user->store_id) {
                return response()->json([
                    'message' => 'Unauthorized. You can only delete tickets from your store.',
                ], 403);
            }
        }

        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted successfully']);
    }

    /**
     * Get statistics for tickets.
     * Admin can see all stats, store users see only their store stats.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

        $query = Ticket::query();

        // Store users can only see stats from their store
        if ($user->role === 'store' && $user->store_id) {
            $query->where('store_id', $user->store_id);
        }

        $stats = [
            'total' => $query->count(),
            'new' => (clone $query)->where('status', 'new')->count(),
            'directed' => (clone $query)->where('status', 'directed')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'canceled' => (clone $query)->where('status', 'canceled')->count(),
        ];

        return response()->json($stats);
    }
}

