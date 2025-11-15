# PricingEngine Service Documentation

## Overview

The `PricingEngine` service provides a clean interface for retrieving pricing information based on device brand, model, and problem type. It searches the `price_matrix` table for exact matches and returns pricing data or indicates when manual quoting is needed.

## Usage

### Basic Usage

```php
use App\Services\PricingEngine;

$pricingEngine = new PricingEngine();

$result = $pricingEngine->getPricing('Apple', 'iPhone 14', 'Screen Repair');

if ($result['needs_manual_quote']) {
    // Handle manual quote
} else {
    $minPrice = $result['price_min'];
    $maxPrice = $result['price_max'];
}
```

### Check if Pricing Exists

```php
$hasPricing = $pricingEngine->hasPricing('Apple', 'iPhone 14', 'Screen Repair');

if ($hasPricing) {
    // Pricing is available
} else {
    // Needs manual quote
}
```

## Return Format

### When Pricing Exists

```php
[
    'price_min' => 1500,
    'price_max' => 3000,
    'needs_manual_quote' => false,
]
```

### When Pricing Does Not Exist

```php
[
    'price_min' => null,
    'price_max' => null,
    'needs_manual_quote' => true,
]
```

## Features

### Case-Insensitive Matching

The service performs case-insensitive matching, so these are all equivalent:

```php
$pricingEngine->getPricing('Apple', 'iPhone 14', 'Screen Repair');
$pricingEngine->getPricing('APPLE', 'iphone 14', 'screen repair');
$pricingEngine->getPricing('apple', 'iPhone 14', 'SCREEN REPAIR');
```

### Whitespace Handling

Extra whitespace is automatically trimmed:

```php
$pricingEngine->getPricing('  Apple  ', '  iPhone 14  ', '  Screen Repair  ');
// Works correctly
```

### Exact Match Required

The service requires an exact match on all three parameters:
- Brand must match exactly
- Model must match exactly
- Problem type must match exactly

Partial matches will return `needs_manual_quote: true`.

## Integration Examples

### In Ticket Creation

```php
use App\Services\PricingEngine;

$pricingEngine = new PricingEngine();
$pricing = $pricingEngine->getPricing($brand, $model, $problemType);

$ticket = Ticket::create([
    'brand' => $brand,
    'model' => $model,
    'problem_type' => $problemType,
    'price_min' => $pricing['price_min'],
    'price_max' => $pricing['price_max'],
    'status' => 'new',
]);
```

### In WhatsApp Bot

```php
$pricing = $pricingEngine->getPricing($brand, $model, $problemType);

if ($pricing['needs_manual_quote']) {
    $message = "We'll need to provide a manual quote for this repair. Our team will contact you soon.";
} else {
    $message = "Estimated price: {$pricing['price_min']} - {$pricing['price_max']}";
}
```

## Testing

Run the unit tests:

```bash
php artisan test --filter PricingEngineTest
```

Or run all unit tests:

```bash
php artisan test tests/Unit/Services/PricingEngineTest.php
```

## Test Coverage

The service includes comprehensive unit tests covering:

- ✅ Exact match returns correct pricing
- ✅ No match returns needs manual quote
- ✅ Case-insensitive matching
- ✅ Whitespace handling
- ✅ Partial matches do not return pricing
- ✅ Multiple entries with same brand/model but different problems
- ✅ Empty database handling
- ✅ Various brands and models

## Database Requirements

The service requires the `price_matrix` table with the following structure:

- `brand` (string)
- `model` (string)
- `problem_type` (string)
- `price_min` (integer)
- `price_max` (integer)

All fields should be indexed for optimal performance.

## Performance Considerations

- The service uses database queries with `LOWER(TRIM())` for case-insensitive matching
- Consider adding database indexes on `brand`, `model`, and `problem_type` columns
- For high-traffic scenarios, consider caching frequently accessed pricing data

## Error Handling

The service does not throw exceptions. Instead, it returns a structured array with `needs_manual_quote` flag set to `true` when no match is found.

## Future Enhancements

Potential improvements:

1. **Fuzzy Matching**: Add support for similar model names (e.g., "iPhone 14" matches "iPhone 14 Pro")
2. **Caching**: Implement caching layer for frequently accessed prices
3. **Price History**: Track price changes over time
4. **Bulk Lookup**: Support for looking up multiple prices at once
5. **Price Ranges**: Support for different price tiers based on urgency or other factors

