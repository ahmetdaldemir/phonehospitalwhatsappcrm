# CRM Database Structure

## Overview

This document describes the CRM database structure, models, relationships, and usage examples.

## Database Tables

### 1. customers
- `id` (UUID, primary key)
- `phone_number` (string, unique)
- `name` (string, nullable)
- `total_visits` (integer, default 0)
- `last_visit_at` (timestamp, nullable)
- `created_at`, `updated_at`, `deleted_at` (soft deletes)

### 2. tickets
- `id` (UUID, primary key)
- `customer_id` (UUID, foreign key → customers)
- `brand` (string)
- `model` (string)
- `problem_type` (string)
- `price_min` (integer, nullable)
- `price_max` (integer, nullable)
- `store_id` (UUID, nullable, foreign key → stores)
- `status` (enum: 'new', 'directed', 'completed', 'canceled')
- `photos` (JSON, nullable)
- `created_at`, `updated_at`, `deleted_at` (soft deletes)

### 3. stores
- `id` (UUID, primary key)
- `name` (string)
- `code` (string, unique)
- `address` (text, nullable)
- `phone` (string, nullable)
- `email` (string, nullable)
- `location_lat` (decimal 10,8, nullable)
- `location_lng` (decimal 11,8, nullable)
- `is_active` (boolean, default true)
- `created_at`, `updated_at`, `deleted_at` (soft deletes)

### 4. price_matrix
- `id` (UUID, primary key)
- `brand` (string)
- `model` (string)
- `problem_type` (string)
- `price_min` (integer)
- `price_max` (integer)
- `created_at`, `updated_at`, `deleted_at` (soft deletes)
- Index on: `brand`, `model`, `problem_type`

### 5. campaigns
- `id` (UUID, primary key)
- `name` (string)
- `discount_type` (string: 'percent' or 'free_item')
- `value` (integer)
- `start_date` (date)
- `end_date` (date)
- `usage_count` (integer, default 0)
- `created_at`, `updated_at`, `deleted_at` (soft deletes)

## Model Relationships

### Customer Model
```php
// Relationships
$customer->tickets() // HasMany Ticket

// Methods
$customer->recordVisit() // Increment visits and update last_visit_at
```

### Ticket Model
```php
// Relationships
$ticket->customer() // BelongsTo Customer
$ticket->store()    // BelongsTo Store

// Scopes
Ticket::status('new')->get()
Ticket::forStore($storeId)->get()
```

### Store Model
```php
// Relationships
$store->users()   // HasMany User
$store->tickets() // HasMany Ticket
```

### PriceMatrix Model
```php
// Methods
PriceMatrix::findPrice($brand, $model, $problemType)
```

### Campaign Model
```php
// Scopes
Campaign::active()->get()

// Methods
$campaign->isActive()
$campaign->incrementUsage()
```

## Usage Examples

### Creating Records

```php
// Create a customer
$customer = Customer::create([
    'phone_number' => '+1234567890',
    'name' => 'John Doe',
]);

// Create a ticket
$ticket = Ticket::create([
    'customer_id' => $customer->id,
    'brand' => 'Apple',
    'model' => 'iPhone 14',
    'problem_type' => 'Screen Repair',
    'price_min' => 1500,
    'price_max' => 3000,
    'status' => 'new',
]);

// Create a store
$store = Store::create([
    'name' => 'Phone Hospital Downtown',
    'code' => 'STORE-001',
    'address' => '123 Main Street',
    'location_lat' => 40.7128,
    'location_lng' => -74.0060,
    'is_active' => true,
]);

// Create price matrix entry
$priceMatrix = PriceMatrix::create([
    'brand' => 'Apple',
    'model' => 'iPhone 14',
    'problem_type' => 'Screen Repair',
    'price_min' => 1500,
    'price_max' => 3000,
]);

// Create a campaign
$campaign = Campaign::create([
    'name' => 'Summer Sale',
    'discount_type' => 'percent',
    'value' => 20,
    'start_date' => now(),
    'end_date' => now()->addMonth(),
]);
```

### Querying Relationships

```php
// Get customer with tickets
$customer = Customer::with('tickets')->find($id);

// Get ticket with customer and store
$ticket = Ticket::with(['customer', 'store'])->find($id);

// Get store with tickets
$store = Store::with('tickets')->find($id);

// Get all tickets for a customer
$tickets = $customer->tickets;

// Get all tickets for a store
$tickets = $store->tickets;
```

### Using Scopes

```php
// Get new tickets
$newTickets = Ticket::status('new')->get();

// Get tickets for a specific store
$storeTickets = Ticket::forStore($storeId)->get();

// Get active campaigns
$activeCampaigns = Campaign::active()->get();
```

### Using Factories

```php
// Create customers
Customer::factory()->count(10)->create();
Customer::factory()->recentVisit()->create();
Customer::factory()->neverVisited()->create();

// Create tickets
Ticket::factory()->count(20)->new()->create();
Ticket::factory()->completed()->create();
Ticket::factory()->directed()->create();

// Create campaigns
Campaign::factory()->active()->create();
Campaign::factory()->expired()->create();
Campaign::factory()->percentDiscount(25)->create();
Campaign::factory()->freeItem(2)->create();
```

### Running Seeders

```bash
# Seed all data
php artisan db:seed

# Seed specific seeder
php artisan db:seed --class=CustomerSeeder
php artisan db:seed --class=TicketSeeder
php artisan db:seed --class=PriceMatrixSeeder
php artisan db:seed --class=CampaignSeeder
php artisan db:seed --class=StoreSeeder
```

## Migration Order

When running migrations, they will execute in this order:

1. `2024_01_01_000001_create_stores_table.php`
2. `2024_01_01_000002_create_users_table.php`
3. `2024_01_01_000003_create_password_reset_tokens_table.php`
4. `2024_01_01_000004_create_sessions_table.php`
5. `2024_01_01_000005_create_personal_access_tokens_table.php`
6. `2024_01_01_000006_create_customers_table.php`
7. `2024_01_01_000007_create_tickets_table.php`
8. `2024_01_01_000008_create_price_matrix_table.php`
9. `2024_01_01_000009_create_campaigns_table.php`
10. `2024_01_01_000010_update_stores_table_add_location.php`

## Seeder Order

The `DatabaseSeeder` calls seeders in this order:

1. `StoreSeeder` - Creates stores (required for tickets)
2. `CustomerSeeder` - Creates customers (required for tickets)
3. `PriceMatrixSeeder` - Creates price matrix entries
4. `CampaignSeeder` - Creates campaigns
5. `TicketSeeder` - Creates tickets (requires stores and customers)

## Notes

- All tables use UUID primary keys
- All main tables have soft deletes enabled
- Tickets can exist without a store (store_id is nullable)
- Price matrix is used for pricing reference
- Campaigns track usage count automatically
- Customer visit tracking is handled by the `recordVisit()` method

