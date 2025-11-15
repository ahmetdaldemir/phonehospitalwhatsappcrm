<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopeStoreData
{
    /**
     * Handle an incoming request.
     * Automatically scope data to user's store if they are a store user.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'store' && $user->store_id) {
            // Set store context for the request
            app()->instance('store_id', $user->store_id);
        }

        return $next($request);
    }
}

