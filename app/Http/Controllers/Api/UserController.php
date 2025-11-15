<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     * Admin can see all users, store users can only see users from their store.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = User::with('store');

        // Store users can only see users from their store
        if ($user->role === 'store' && $user->store_id) {
            $query->where('store_id', $user->store_id);
        }

        $users = $query->paginate(15);

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     * Only admin can create users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['admin', 'store'])],
            'store_id' => 'nullable|uuid|exists:stores,id',
        ]);

        // If role is store, store_id is required
        if ($request->role === 'store' && !$request->store_id) {
            return response()->json([
                'message' => 'Store ID is required for store users.',
            ], 422);
        }

        // If role is admin, store_id should be null
        if ($request->role === 'admin' && $request->store_id) {
            return response()->json([
                'message' => 'Admin users cannot be assigned to a store.',
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'store_id' => $request->store_id,
        ]);

        return response()->json($user->load('store'), 201);
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
        $targetUser = User::with('store')->findOrFail($id);

        // Store users can only see users from their store
        if ($user->role === 'store' && $user->store_id) {
            if ($targetUser->store_id !== $user->store_id) {
                return response()->json([
                    'message' => 'Unauthorized. You can only view users from your store.',
                ], 403);
            }
        }

        return response()->json($targetUser);
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
        $targetUser = User::findOrFail($id);

        // Store users can only update users from their store
        if ($user->role === 'store' && $user->store_id) {
            if ($targetUser->store_id !== $user->store_id) {
                return response()->json([
                    'message' => 'Unauthorized. You can only update users from your store.',
                ], 403);
            }
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'password' => 'sometimes|string|min:8',
            'role' => ['sometimes', Rule::in(['admin', 'store'])],
            'store_id' => 'nullable|uuid|exists:stores,id',
        ]);

        // Only admin can change roles
        if ($request->has('role') && $user->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized. Only admins can change user roles.',
            ], 403);
        }

        // If role is store, store_id is required
        if ($request->has('role') && $request->role === 'store' && !$request->store_id) {
            return response()->json([
                'message' => 'Store ID is required for store users.',
            ], 422);
        }

        // If role is admin, store_id should be null
        if ($request->has('role') && $request->role === 'admin' && $request->store_id) {
            return response()->json([
                'message' => 'Admin users cannot be assigned to a store.',
            ], 422);
        }

        if ($request->has('password')) {
            $request->merge(['password' => Hash::make($request->password)]);
        }

        $targetUser->update($request->only(['name', 'email', 'password', 'role', 'store_id']));

        return response()->json($targetUser->load('store'));
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
        $targetUser = User::findOrFail($id);

        // Prevent self-deletion
        if ($targetUser->id === $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        // Store users can only delete users from their store
        if ($user->role === 'store' && $user->store_id) {
            if ($targetUser->store_id !== $user->store_id) {
                return response()->json([
                    'message' => 'Unauthorized. You can only delete users from your store.',
                ], 403);
            }
        }

        $targetUser->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => 'sometimes|string|min:8',
        ]);

        if ($request->has('password')) {
            $request->merge(['password' => Hash::make($request->password)]);
        }

        $user->update($request->only(['name', 'email', 'password']));

        return response()->json($user->load('store'));
    }
}

