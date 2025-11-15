# WhatsApp Template Messages

This document provides WhatsApp Business API template message examples for the Phone Hospital CRM system.

## Template Names

1. **first_reply** - Welcome message for first-time customers
2. **price_display** - Display repair price estimate
3. **location_request** - Request customer location
4. **success_coupon_confirmation** - Ticket confirmation with coupon code

## Template Structure

All templates follow Meta's WhatsApp Business API format:

```json
{
  "messaging_product": "whatsapp",
  "to": "PHONE_NUMBER",
  "type": "template",
  "template": {
    "name": "TEMPLATE_NAME",
    "language": {
      "code": "en"
    },
    "components": [
      {
        "type": "body",
        "parameters": [
          {
            "type": "text",
            "text": "VALUE"
          }
        ]
      }
    ]
  }
}
```

## 1. First Reply Template

### Template Content (to be created in Meta Business Manager)

**Template Name:** `first_reply`

**Category:** UTILITY

**Language:** English (en)

**Body:**
```
Hello {{1}}! 👋

Welcome to Phone Hospital! We're here to help you with all your phone repair needs.

How can we assist you today?
```

**Parameters:**
- `{{1}}` - Customer name (text)

### cURL Example

```bash
curl -X POST "https://graph.facebook.com/v18.0/{PHONE_NUMBER_ID}/messages" \
  -H "Authorization: Bearer {ACCESS_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "messaging_product": "whatsapp",
    "to": "1234567890",
    "type": "template",
    "template": {
      "name": "first_reply",
      "language": {
        "code": "en"
      },
      "components": [
        {
          "type": "body",
          "parameters": [
            {
              "type": "text",
              "text": "John Doe"
            }
          ]
        }
      ]
    }
  }'
```

### Laravel Http::post() Example

```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.whatsapp.token'),
    'Content-Type' => 'application/json',
])->post("https://graph.facebook.com/v18.0/{phone_number_id}/messages", [
    'messaging_product' => 'whatsapp',
    'to' => '1234567890',
    'type' => 'template',
    'template' => [
        'name' => 'first_reply',
        'language' => [
            'code' => 'en',
        ],
        'components' => [
            [
                'type' => 'body',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => 'John Doe',
                    ],
                ],
            ],
        ],
    ],
]);
```

### Using TicketService

```php
use App\Services\WhatsAppTemplateService;

$whatsappService = new WhatsAppTemplateService();
$result = $whatsappService->sendFirstReply('1234567890', 'John Doe');

if ($result['success']) {
    echo "Message sent: " . $result['message_id'];
}
```

---

## 2. Price Display Template

### Template Content

**Template Name:** `price_display`

**Category:** UTILITY

**Language:** English (en)

**Body:**
```
📱 Repair Price Estimate

Device: {{1}} {{2}}
Problem: {{3}}

Estimated Price Range:
💰 ${{4}} - ${{5}}

Would you like to proceed with this repair?
```

**Parameters:**
- `{{1}}` - Brand (text)
- `{{2}}` - Model (text)
- `{{3}}` - Problem type (text)
- `{{4}}` - Minimum price (text)
- `{{5}}` - Maximum price (text)

### cURL Example

```bash
curl -X POST "https://graph.facebook.com/v18.0/{PHONE_NUMBER_ID}/messages" \
  -H "Authorization: Bearer {ACCESS_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "messaging_product": "whatsapp",
    "to": "1234567890",
    "type": "template",
    "template": {
      "name": "price_display",
      "language": {
        "code": "en"
      },
      "components": [
        {
          "type": "body",
          "parameters": [
            {
              "type": "text",
              "text": "Apple"
            },
            {
              "type": "text",
              "text": "iPhone 14"
            },
            {
              "type": "text",
              "text": "Screen Repair"
            },
            {
              "type": "text",
              "text": "1500.00"
            },
            {
              "type": "text",
              "text": "3000.00"
            }
          ]
        }
      ]
    }
  }'
```

### Laravel Http::post() Example

```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.whatsapp.token'),
    'Content-Type' => 'application/json',
])->post("https://graph.facebook.com/v18.0/{phone_number_id}/messages", [
    'messaging_product' => 'whatsapp',
    'to' => '1234567890',
    'type' => 'template',
    'template' => [
        'name' => 'price_display',
        'language' => [
            'code' => 'en',
        ],
        'components' => [
            [
                'type' => 'body',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => 'Apple',
                    ],
                    [
                        'type' => 'text',
                        'text' => 'iPhone 14',
                    ],
                    [
                        'type' => 'text',
                        'text' => 'Screen Repair',
                    ],
                    [
                        'type' => 'text',
                        'text' => '1500.00',
                    ],
                    [
                        'type' => 'text',
                        'text' => '3000.00',
                    ],
                ],
            ],
        ],
    ],
]);
```

### Using TicketService

```php
use App\Services\WhatsAppTemplateService;

$whatsappService = new WhatsAppTemplateService();
$result = $whatsappService->sendPriceDisplay(
    '1234567890',
    'Apple',
    'iPhone 14',
    'Screen Repair',
    1500.00,
    3000.00
);
```

---

## 3. Location Request Template

### Template Content

**Template Name:** `location_request`

**Category:** UTILITY

**Language:** English (en)

**Body:**
```
📍 Location Request

To find the nearest repair store, please share your current location.

Tap the location icon below to send your location.
```

**Parameters:** None

### cURL Example

```bash
curl -X POST "https://graph.facebook.com/v18.0/{PHONE_NUMBER_ID}/messages" \
  -H "Authorization: Bearer {ACCESS_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "messaging_product": "whatsapp",
    "to": "1234567890",
    "type": "template",
    "template": {
      "name": "location_request",
      "language": {
        "code": "en"
      }
    }
  }'
```

### Laravel Http::post() Example

```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.whatsapp.token'),
    'Content-Type' => 'application/json',
])->post("https://graph.facebook.com/v18.0/{phone_number_id}/messages", [
    'messaging_product' => 'whatsapp',
    'to' => '1234567890',
    'type' => 'template',
    'template' => [
        'name' => 'location_request',
        'language' => [
            'code' => 'en',
        ],
    ],
]);
```

### Using TicketService

```php
use App\Services\WhatsAppTemplateService;

$whatsappService = new WhatsAppTemplateService();
$result = $whatsappService->sendLocationRequest('1234567890');
```

---

## 4. Success & Coupon Confirmation Template

### Template Content

**Template Name:** `success_coupon_confirmation`

**Category:** UTILITY

**Language:** English (en)

**Body:**
```
✅ Ticket Created Successfully!

Your repair ticket has been created:
Ticket ID: {{1}}

🎉 Special Offer!
Use coupon code: {{2}}
Get {{3}}% off on your repair!

We'll contact you soon. Thank you for choosing Phone Hospital!
```

**Parameters:**
- `{{1}}` - Ticket ID (text)
- `{{2}}` - Coupon code (text)
- `{{3}}` - Discount percentage (text)

### cURL Example

```bash
curl -X POST "https://graph.facebook.com/v18.0/{PHONE_NUMBER_ID}/messages" \
  -H "Authorization: Bearer {ACCESS_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "messaging_product": "whatsapp",
    "to": "1234567890",
    "type": "template",
    "template": {
      "name": "success_coupon_confirmation",
      "language": {
        "code": "en"
      },
      "components": [
        {
          "type": "body",
          "parameters": [
            {
              "type": "text",
              "text": "TKT-123456"
            },
            {
              "type": "text",
              "text": "WELCOME20"
            },
            {
              "type": "text",
              "text": "20"
            }
          ]
        }
      ]
    }
  }'
```

### Laravel Http::post() Example

```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.whatsapp.token'),
    'Content-Type' => 'application/json',
])->post("https://graph.facebook.com/v18.0/{phone_number_id}/messages", [
    'messaging_product' => 'whatsapp',
    'to' => '1234567890',
    'type' => 'template',
    'template' => [
        'name' => 'success_coupon_confirmation',
        'language' => [
            'code' => 'en',
        ],
        'components' => [
            [
                'type' => 'body',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => 'TKT-123456',
                    ],
                    [
                        'type' => 'text',
                        'text' => 'WELCOME20',
                    ],
                    [
                        'type' => 'text',
                        'text' => '20',
                    ],
                ],
            ],
        ],
    ],
]);
```

### Using TicketService

```php
use App\Services\WhatsAppTemplateService;

$whatsappService = new WhatsAppTemplateService();
$result = $whatsappService->sendSuccessCouponConfirmation(
    '1234567890',
    'TKT-123456',
    'WELCOME20',
    20
);
```

---

## Template Setup in Meta Business Manager

### Steps to Create Templates

1. Go to [Meta Business Manager](https://business.facebook.com/)
2. Navigate to **WhatsApp Manager** > **Message Templates**
3. Click **Create Template**
4. Fill in template details:
   - **Name:** (e.g., `first_reply`)
   - **Category:** UTILITY
   - **Language:** English
   - **Body:** Enter the template text with `{{1}}`, `{{2}}`, etc. for parameters
5. Submit for approval (usually takes 24-48 hours)

### Template Approval Guidelines

- Templates must be approved by Meta before use
- Use UTILITY category for transactional messages
- Keep messages clear and professional
- Avoid promotional language in UTILITY templates
- Include all necessary information

---

## Integration with WhatsApp Webhook Controller

### Example Usage in Controller

```php
use App\Services\WhatsAppTemplateService;
use App\Models\Ticket;

// In your webhook controller
$whatsappService = new WhatsAppTemplateService();

// Send first reply
$whatsappService->sendFirstReply($phoneNumber, $customerName);

// Send price display
$whatsappService->sendPriceDisplay(
    $phoneNumber,
    $ticket->brand,
    $ticket->model,
    $ticket->problem_type,
    $ticket->price_min,
    $ticket->price_max
);

// Request location
$whatsappService->sendLocationRequest($phoneNumber);

// Send confirmation with coupon
$whatsappService->sendSuccessCouponConfirmation(
    $phoneNumber,
    $ticket->id,
    'WELCOME20',
    20
);
```

---

## Error Handling

### Response Format

**Success:**
```json
{
  "success": true,
  "message_id": "wamid.xxx",
  "data": {
    "messaging_product": "whatsapp",
    "contacts": [...],
    "messages": [...]
  }
}
```

**Error:**
```json
{
  "success": false,
  "error": {
    "message": "Error message",
    "type": "OAuthException",
    "code": 190
  }
}
```

### Common Errors

- **190:** Invalid access token
- **131047:** Template not approved
- **131026:** Invalid phone number format
- **131051:** Rate limit exceeded

---

## Testing

### Test Phone Numbers

Use Meta's test phone numbers for development:
- Add test numbers in Meta Business Manager
- Test templates before production use
- Verify all parameters are correctly formatted

### Testing Checklist

- [ ] Template approved in Meta Business Manager
- [ ] Access token is valid
- [ ] Phone number format is correct (with country code, no +)
- [ ] All parameters are provided
- [ ] Template name matches exactly
- [ ] Language code is correct

---

## Notes

- Phone numbers must include country code (e.g., `1234567890` for US)
- Do not include `+` in phone numbers
- Templates must be approved before use
- Rate limits apply (check Meta documentation)
- All templates are logged for debugging

