<?php

namespace App\Services;

use App\Models\Store;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class TicketService
{
    /**
     * Match the nearest store to a ticket based on coordinates.
     * Uses Haversine formula to calculate distance.
     *
     * @param  \App\Models\Ticket  $ticket
     * @param  float  $lat
     * @param  float  $lng
     * @return \App\Models\Store|null
     */
    public function matchNearestStore(Ticket $ticket, float $lat, float $lng): ?Store
    {
        // Find nearest active store using Haversine formula
        $nearestStore = Store::select('stores.*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(location_lat)) * cos(radians(location_lng) - radians(?)) + sin(radians(?)) * sin(radians(location_lat)))) AS distance',
                [$lat, $lng, $lat]
            )
            ->whereNotNull('location_lat')
            ->whereNotNull('location_lng')
            ->where('is_active', true)
            ->orderBy('distance', 'asc')
            ->first();

        if (!$nearestStore) {
            return null;
        }

        // Update ticket with store_id
        $ticket->update([
            'store_id' => $nearestStore->id,
        ]);

        return $nearestStore;
    }

    /**
     * Alternative method using raw SQL for better performance.
     *
     * @param  \App\Models\Ticket  $ticket
     * @param  float  $lat
     * @param  float  $lng
     * @return \App\Models\Store|null
     */
    public function matchNearestStoreRaw(Ticket $ticket, float $lat, float $lng): ?Store
    {
        $nearestStoreId = DB::table('stores')
            ->select('id')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(location_lat)) * cos(radians(location_lng) - radians(?)) + sin(radians(?)) * sin(radians(location_lat)))) AS distance',
                [$lat, $lng, $lat]
            )
            ->whereNotNull('location_lat')
            ->whereNotNull('location_lng')
            ->where('is_active', true)
            ->orderBy('distance', 'asc')
            ->value('id');

        if (!$nearestStoreId) {
            return null;
        }

        $store = Store::find($nearestStoreId);

        if ($store) {
            $ticket->update([
                'store_id' => $store->id,
            ]);
        }

        return $store;
    }
}

