# WhatsApp Commerce Integration

This document describes the WhatsApp Commerce integration module that extends the CRM to support product-based upsell in the WhatsApp Bot.

## Overview

The commerce module allows customers to:
1. View recommended products after creating a repair ticket
2. Select and purchase products through WhatsApp
3. Track order status

## Database Structure

### Tables

#### `products`
- Stores product information (phone accessories, cases, etc.)
- Fields: id, sku, name, description, brand, model, price, stock, images, is_active
- Supports soft deletes

#### `product_recommendations`
- Links products to device models with priority
- Fields: id, model, product_id, priority
- Used to recommend products based on device model

#### `orders`
- Stores customer orders
- Fields: id, ticket_id, customer_id, store_id, total_price, payment_status, order_status
- Statuses: draft, in_store, on_the_way, delivered
- Payment statuses: pending, paid, canceled

#### `order_items`
- Stores individual items in an order
- Fields: id, order_id, product_id, quantity, price

## Models & Relationships

### Product Model
- `hasMany(OrderItem::class)`
- Scopes: `active()`, `inStock()`
- Accessor: `first_image` - returns first image from images array

### ProductRecommendation Model
- `belongsTo(Product::class)`
- Scopes: `forModel($model)`, `orderedByPriority()`

### Order Model
- `belongsTo(Ticket::class)` - optional link to repair ticket
- `belongsTo(Customer::class)`
- `belongsTo(Store::class)` - optional store for pickup
- `hasMany(OrderItem::class)`
- Method: `calculateTotal()` - calculates total from order items

### OrderItem Model
- `belongsTo(Order::class)`
- `belongsTo(Product::class)`
- Accessor: `total` - calculates item total (price * quantity)

### Customer Model (Updated)
- `hasMany(Order::class)` - added relationship

## API Endpoints

### Product Endpoints

#### GET `/api/products/recommended?model={model}`
Returns top 3 recommended products for a device model.

**Response:**
```json
{
  "products": [
    {
      "id": "uuid",
      "sku": "PROD-001",
      "name": "Phone Case",
      "description": "Protective case",
      "brand": "Apple",
      "model": "iPhone 14",
      "price": 500,
      "stock": 10,
      "first_image": "path/to/image.jpg",
      "images": ["path/to/image.jpg"]
    }
  ],
  "source": "recommendations" // or "random"
}
```

#### GET `/api/products`
List all products with filters (brand, model, in_stock_only).

#### GET `/api/products/{id}`
Get single product details.

### Order Endpoints

#### POST `/api/orders/draft`
Create a draft order.

**Request:**
```json
{
  "customer_id": "uuid",
  "product_ids": ["uuid1", "uuid2"],
  "ticket_id": "uuid (optional)",
  "store_id": "uuid (optional)"
}
```

**Response:**
```json
{
  "order_id": "uuid",
  "total_price": 1000,
  "store": {
    "id": "uuid",
    "name": "Store Name",
    "address": "Store Address"
  },
  "pickup_instructions": "Instructions text"
}
```

#### GET `/api/orders`
List orders with filters (customer_id, store_id, order_status, payment_status).

#### GET `/api/orders/{id}`
Get single order with relationships.

#### PATCH `/api/orders/{id}/status`
Update order or payment status.

## Bot Flow Integration

### New Bot States

1. **ticket_created** (Updated)
   - After ticket creation, asks if customer wants to see products
   - Options: "1️⃣ Evet" or "2️⃣ Hayır"

2. **show_products**
   - Fetches recommended products for device model
   - Displays product list with prices
   - Action: `show_products`

3. **product_selection**
   - Customer selects product by number (1, 2, 3)
   - Can cancel with "iptal"

4. **create_order_draft**
   - Creates order draft
   - Action: `create_order_draft`

5. **order_created**
   - Confirms order creation
   - Shows order ID and pickup instructions

6. **check_order_status**
   - Customer enters order ID to check status

7. **show_order_status**
   - Displays order details
   - Action: `show_order_status`

8. **ask_further_help**
   - Asks if customer needs further assistance

### Bot Flow Example

```
1. Customer creates ticket
   ↓
2. Bot: "Modeline uygun aksesuarları görmek ister misin?"
   ↓
3. Customer: "1" (Evet)
   ↓
4. Bot shows 3 recommended products
   ↓
5. Customer: "2" (selects product #2)
   ↓
6. Bot creates order draft
   ↓
7. Bot: "Siparişiniz oluşturuldu! Sipariş No: ..."
```

## Implementation Details

### Product Recommendation Logic

1. **Priority-based recommendations:**
   - Queries `product_recommendations` table
   - Filters by device model
   - Orders by priority (descending)
   - Returns top 3 products

2. **Fallback to random:**
   - If no recommendations found
   - Returns 3 random active, in-stock products

### Order Creation

1. Validates product availability
2. Creates order with status "draft"
3. Creates order items
4. Calculates total price
5. Returns order ID and pickup instructions

### Pickup Instructions

- If store assigned: Shows store name and address
- If no store: Generic message about notification

## Usage Examples

### Creating Product Recommendations

```php
use App\Models\Product;
use App\Models\ProductRecommendation;

$product = Product::create([
    'sku' => 'CASE-IPHONE14',
    'name' => 'iPhone 14 Case',
    'brand' => 'Apple',
    'model' => 'iPhone 14',
    'price' => 500,
    'stock' => 10,
    'is_active' => true,
]);

ProductRecommendation::create([
    'model' => 'iPhone 14',
    'product_id' => $product->id,
    'priority' => 10, // Higher priority = shown first
]);
```

### Creating Order via API

```bash
curl -X POST http://localhost:8000/api/orders/draft \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": "customer-uuid",
    "product_ids": ["product-uuid-1", "product-uuid-2"]
  }'
```

### Getting Recommended Products

```bash
curl "http://localhost:8000/api/products/recommended?model=iPhone%2014" \
  -H "Authorization: Bearer {token}"
```

## Testing

### Test Product Recommendation

1. Create products
2. Create product recommendations for a model
3. Call `/api/products/recommended?model={model}`
4. Verify top 3 products are returned in priority order

### Test Bot Flow

1. Start conversation with bot
2. Create a ticket
3. Select "Evet" when asked about products
4. Verify products are shown
5. Select a product number
6. Verify order is created

## Future Enhancements

- [ ] Multiple product selection in single order
- [ ] Quantity selection for products
- [ ] Delivery option (currently only pickup)
- [ ] Payment integration
- [ ] Order tracking with status updates
- [ ] Product images in WhatsApp messages
- [ ] Cart functionality (add multiple products before checkout)

## Notes

- Products must be active and in stock to be recommended
- Orders start as "draft" status
- Store assignment can be done later
- Order items store price at time of purchase (price snapshot)

