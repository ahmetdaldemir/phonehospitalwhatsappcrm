<?php

namespace Tests\Unit\Services;

use App\Models\Store;
use App\Models\Ticket;
use App\Models\Customer;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TicketService $ticketService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ticketService = new TicketService();
    }

    /**
     * Test matching nearest store with a single store.
     */
    public function test_match_nearest_store_with_single_store(): void
    {
        // Create a store with coordinates (New York)
        $store = Store::create([
            'name' => 'Store NYC',
            'code' => 'STORE-NYC',
            'address' => 'New York',
            'location_lat' => 40.7128,
            'location_lng' => -74.0060,
            'is_active' => true,
        ]);

        // Create a customer and ticket
        $customer = Customer::create([
            'phone_number' => '+1234567890',
            'name' => 'Test Customer',
        ]);

        $ticket = Ticket::create([
            'customer_id' => $customer->id,
            'brand' => 'Apple',
            'model' => 'iPhone 14',
            'problem_type' => 'Screen Repair',
            'status' => 'new',
        ]);

        // Test coordinates near the store (Brooklyn)
        $lat = 40.6782;
        $lng = -73.9442;

        $matchedStore = $this->ticketService->matchNearestStore($ticket, $lat, $lng);

        $this->assertNotNull($matchedStore);
        $this->assertEquals($store->id, $matchedStore->id);
        $this->assertEquals($store->id, $ticket->fresh()->store_id);
    }

    /**
     * Test matching nearest store with multiple stores.
     */
    public function test_match_nearest_store_with_multiple_stores(): void
    {
        // Create multiple stores
        $storeNYC = Store::create([
            'name' => 'Store NYC',
            'code' => 'STORE-NYC',
            'address' => 'New York',
            'location_lat' => 40.7128,
            'location_lng' => -74.0060,
            'is_active' => true,
        ]);

        $storeLA = Store::create([
            'name' => 'Store LA',
            'code' => 'STORE-LA',
            'address' => 'Los Angeles',
            'location_lat' => 34.0522,
            'location_lng' => -118.2437,
            'is_active' => true,
        ]);

        $storeChicago = Store::create([
            'name' => 'Store Chicago',
            'code' => 'STORE-CHI',
            'address' => 'Chicago',
            'location_lat' => 41.8781,
            'location_lng' => -87.6298,
            'is_active' => true,
        ]);

        // Create a customer and ticket
        $customer = Customer::create([
            'phone_number' => '+1234567890',
            'name' => 'Test Customer',
        ]);

        $ticket = Ticket::create([
            'customer_id' => $customer->id,
            'brand' => 'Apple',
            'model' => 'iPhone 14',
            'problem_type' => 'Screen Repair',
            'status' => 'new',
        ]);

        // Test coordinates closer to NYC (Brooklyn)
        $lat = 40.6782;
        $lng = -73.9442;

        $matchedStore = $this->ticketService->matchNearestStore($ticket, $lat, $lng);

        $this->assertNotNull($matchedStore);
        // Should match NYC store as it's the closest
        $this->assertEquals($storeNYC->id, $matchedStore->id);
        $this->assertEquals($storeNYC->id, $ticket->fresh()->store_id);
    }

    /**
     * Test matching nearest store when no stores exist.
     */
    public function test_match_nearest_store_with_no_stores(): void
    {
        // Create a customer and ticket
        $customer = Customer::create([
            'phone_number' => '+1234567890',
            'name' => 'Test Customer',
        ]);

        $ticket = Ticket::create([
            'customer_id' => $customer->id,
            'brand' => 'Apple',
            'model' => 'iPhone 14',
            'problem_type' => 'Screen Repair',
            'status' => 'new',
        ]);

        $lat = 40.7128;
        $lng = -74.0060;

        $matchedStore = $this->ticketService->matchNearestStore($ticket, $lat, $lng);

        $this->assertNull($matchedStore);
        $this->assertNull($ticket->fresh()->store_id);
    }

    /**
     * Test matching nearest store with stores but no coordinates.
     */
    public function test_match_nearest_store_with_stores_without_coordinates(): void
    {
        // Create a store without coordinates
        Store::create([
            'name' => 'Store No Location',
            'code' => 'STORE-NO-LOC',
            'address' => 'Somewhere',
            'location_lat' => null,
            'location_lng' => null,
            'is_active' => true,
        ]);

        // Create a customer and ticket
        $customer = Customer::create([
            'phone_number' => '+1234567890',
            'name' => 'Test Customer',
        ]);

        $ticket = Ticket::create([
            'customer_id' => $customer->id,
            'brand' => 'Apple',
            'model' => 'iPhone 14',
            'problem_type' => 'Screen Repair',
            'status' => 'new',
        ]);

        $lat = 40.7128;
        $lng = -74.0060;

        $matchedStore = $this->ticketService->matchNearestStore($ticket, $lat, $lng);

        $this->assertNull($matchedStore);
        $this->assertNull($ticket->fresh()->store_id);
    }

    /**
     * Test matching nearest store with inactive stores.
     */
    public function test_match_nearest_store_ignores_inactive_stores(): void
    {
        // Create an inactive store
        Store::create([
            'name' => 'Store Inactive',
            'code' => 'STORE-INACTIVE',
            'address' => 'Somewhere',
            'location_lat' => 40.7128,
            'location_lng' => -74.0060,
            'is_active' => false,
        ]);

        // Create a customer and ticket
        $customer = Customer::create([
            'phone_number' => '+1234567890',
            'name' => 'Test Customer',
        ]);

        $ticket = Ticket::create([
            'customer_id' => $customer->id,
            'brand' => 'Apple',
            'model' => 'iPhone 14',
            'problem_type' => 'Screen Repair',
            'status' => 'new',
        ]);

        $lat = 40.7128;
        $lng = -74.0060;

        $matchedStore = $this->ticketService->matchNearestStore($ticket, $lat, $lng);

        $this->assertNull($matchedStore);
        $this->assertNull($ticket->fresh()->store_id);
    }

    /**
     * Test that the nearest store is correctly selected from multiple options.
     */
    public function test_match_nearest_store_selects_correct_store_from_multiple(): void
    {
        // Create stores at different distances
        $storeClose = Store::create([
            'name' => 'Store Close',
            'code' => 'STORE-CLOSE',
            'address' => 'Close Location',
            'location_lat' => 40.7128,
            'location_lng' => -74.0060,
            'is_active' => true,
        ]);

        $storeFar = Store::create([
            'name' => 'Store Far',
            'code' => 'STORE-FAR',
            'address' => 'Far Location',
            'location_lat' => 34.0522, // Los Angeles
            'location_lng' => -118.2437,
            'is_active' => true,
        ]);

        // Create a customer and ticket
        $customer = Customer::create([
            'phone_number' => '+1234567890',
            'name' => 'Test Customer',
        ]);

        $ticket = Ticket::create([
            'customer_id' => $customer->id,
            'brand' => 'Apple',
            'model' => 'iPhone 14',
            'problem_type' => 'Screen Repair',
            'status' => 'new',
        ]);

        // Test coordinates very close to the first store (Manhattan)
        $lat = 40.7580;
        $lng = -73.9855;

        $matchedStore = $this->ticketService->matchNearestStore($ticket, $lat, $lng);

        $this->assertNotNull($matchedStore);
        // Should match the closer store (NYC)
        $this->assertEquals($storeClose->id, $matchedStore->id);
        $this->assertEquals($storeClose->id, $ticket->fresh()->store_id);
    }

    /**
     * Test that ticket store_id is updated correctly.
     */
    public function test_match_nearest_store_updates_ticket_store_id(): void
    {
        $store = Store::create([
            'name' => 'Store NYC',
            'code' => 'STORE-NYC',
            'address' => 'New York',
            'location_lat' => 40.7128,
            'location_lng' => -74.0060,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'phone_number' => '+1234567890',
            'name' => 'Test Customer',
        ]);

        $ticket = Ticket::create([
            'customer_id' => $customer->id,
            'brand' => 'Apple',
            'model' => 'iPhone 14',
            'problem_type' => 'Screen Repair',
            'status' => 'new',
            'store_id' => null, // Initially no store
        ]);

        $this->assertNull($ticket->store_id);

        $lat = 40.6782;
        $lng = -73.9442;

        $matchedStore = $this->ticketService->matchNearestStore($ticket, $lat, $lng);

        $this->assertNotNull($matchedStore);
        $ticket->refresh();
        $this->assertEquals($store->id, $ticket->store_id);
    }
}

