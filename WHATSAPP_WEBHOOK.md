# WhatsApp Cloud API Webhook Documentation

## Overview

The WhatsApp Webhook Controller handles incoming messages from WhatsApp Cloud API, manages conversation state, and creates tickets/customers automatically based on the bot flow configuration.

## Route

- **Endpoint:** `POST /api/whatsapp/webhook`
- **Verification:** `GET /api/whatsapp/webhook` (for webhook setup)

## Features

### 1. Message Parsing
- **Text Messages**: Handles text input and processes according to bot flow
- **Media Messages**: Accepts images, videos, and documents
- **Location Messages**: Captures location data

### 2. State Tracking
- Tracks user conversation state in `bot_sessions` table
- Maintains conversation context and collected data
- Supports state transitions based on user input

### 3. Bot Flow
- Follows JSON flow defined in `config/bot_flow.php`
- Dynamic state transitions
- Placeholder replacement in messages
- Special actions (create ticket, show status)

### 4. Automatic Ticket Creation
- Creates customer record if doesn't exist
- Creates ticket with collected information
- Links photos to ticket
- Updates customer visit count

### 5. Media Handling
- Downloads media from WhatsApp Cloud API
- Saves to `storage/app/public/ticket-media/{session_id}/`
- Supports images, videos, and documents
- Stores file paths in ticket photos JSON field

## Configuration

### Environment Variables

Add to your `.env` file:

```env
WHATSAPP_TOKEN=your_whatsapp_access_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_VERIFY_TOKEN=your_verify_token
WHATSAPP_WEBHOOK_URL=https://yourdomain.com/api/whatsapp/webhook
```

### Bot Flow Configuration

The bot flow is configured in `config/bot_flow.php`. Each state defines:
- `message`: Text to send to user
- `next_states`: Map of user input to next state
- `validation`: Validation rules (optional)
- `data_key`: Key to store user input in session data
- `action`: Special action to execute (optional)
- `accepts_media`: Whether state accepts media (optional)

## Webhook Setup

### 1. Configure Webhook in Meta Developer Console

1. Go to Meta for Developers
2. Select your WhatsApp Business App
3. Navigate to Configuration > Webhook
4. Set Callback URL: `https://yourdomain.com/api/whatsapp/webhook`
5. Set Verify Token: (same as `WHATSAPP_VERIFY_TOKEN` in .env)
6. Subscribe to `messages` field

### 2. Verify Webhook

When you configure the webhook, Meta will send a GET request to verify:

```
GET /api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=your-token&hub.challenge=random-string
```

The controller automatically handles this verification.

## Bot Flow States

### Start State
- Welcome message
- Options: Report issue, Check status, Contact support

### Report Issue Flow
1. `report_issue` - Ask for phone brand
2. `get_model` - Ask for phone model
3. `get_problem` - Ask for problem type
4. `wait_for_photos` - Optional photo upload
5. `get_customer_name` - Ask for customer name
6. `create_ticket` - Create ticket automatically
7. `ticket_created` - Show confirmation

### Check Status Flow
1. `check_status` - Ask for ticket ID
2. `show_ticket_status` - Display ticket information

## Message Format

### Incoming Webhook Payload

```json
{
  "entry": [
    {
      "changes": [
        {
          "value": {
            "messages": [
              {
                "from": "1234567890",
                "id": "wamid.xxx",
                "type": "text",
                "text": {
                  "body": "Hello"
                }
              }
            ]
          }
        }
      ]
    }
  ]
}
```

### Outgoing Message Format

The controller sends messages using WhatsApp Cloud API:

```json
{
  "messaging_product": "whatsapp",
  "to": "1234567890",
  "type": "text",
  "text": {
    "body": "Your message here"
  }
}
```

## Database Schema

### bot_sessions Table

- `id` (UUID) - Primary key
- `phone_number` (string, unique) - User's phone number
- `current_state` (string) - Current conversation state
- `data` (JSON) - Collected data during conversation
- `customer_id` (UUID, nullable) - Linked customer
- `ticket_id` (UUID, nullable) - Linked ticket
- `last_interaction_at` (timestamp) - Last activity time
- `created_at`, `updated_at`, `deleted_at`

## Usage Examples

### Example Conversation Flow

1. **User sends:** "Hello"
   - **Bot responds:** Welcome message with options

2. **User sends:** "1"
   - **Bot responds:** "What is your phone brand?"

3. **User sends:** "Apple"
   - **Bot responds:** "What is your phone model?"

4. **User sends:** "iPhone 14"
   - **Bot responds:** "What is the problem?" (with options)

5. **User sends:** "1" (Screen Repair)
   - **Bot responds:** "Would you like to upload photos?"

6. **User sends:** "1" (Yes)
   - **Bot responds:** "Please send photos..."

7. **User sends:** Photo
   - **Bot responds:** "Photo received! Send another or type 'done'"

8. **User sends:** "done"
   - **Bot responds:** "What is your name?"

9. **User sends:** "John Doe"
   - **Bot creates ticket and responds with confirmation

## Media Storage

Media files are stored in:
```
storage/app/public/ticket-media/{session_id}/{filename}
```

The file path is stored in the ticket's `photos` JSON field:
```json
[
  "ticket-media/uuid-1/photo1.jpg",
  "ticket-media/uuid-1/photo2.jpg"
]
```

## Error Handling

- All errors are logged to Laravel logs
- WhatsApp always receives 200 response (to prevent retries)
- User receives error message if something goes wrong
- Invalid input shows "Sorry, I didn't understand that" message

## Testing

### Local Testing with ngrok

1. Start Laravel server:
   ```bash
   php artisan serve
   ```

2. Expose with ngrok:
   ```bash
   ngrok http 8000
   ```

3. Use ngrok URL in webhook configuration:
   ```
   https://your-ngrok-url.ngrok.io/api/whatsapp/webhook
   ```

### Testing Webhook Verification

```bash
curl "http://localhost:8000/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=your-token&hub.challenge=test123"
```

Should return: `test123`

## Security Considerations

1. **Webhook Verification**: Always verify the webhook token
2. **HTTPS**: Use HTTPS in production
3. **Rate Limiting**: Consider adding rate limiting middleware
4. **Signature Verification**: Implement X-Hub-Signature-256 verification (optional)
5. **Input Validation**: All user input is validated according to flow config

## Customization

### Adding New States

1. Add state to `config/bot_flow.php`:
```php
'my_new_state' => [
    'message' => 'Your message',
    'next_states' => [
        '1' => 'next_state',
    ],
    'validation' => 'required|string',
    'data_key' => 'my_data',
],
```

2. Update flow to transition to new state

### Custom Actions

Add custom actions in `processState()` method:
```php
case 'my_custom_action':
    $this->myCustomMethod($session);
    break;
```

## Troubleshooting

### Webhook Not Receiving Messages
- Check webhook is subscribed in Meta Console
- Verify webhook URL is accessible
- Check logs for incoming requests

### Media Not Downloading
- Verify `WHATSAPP_TOKEN` is correct
- Check `WHATSAPP_PHONE_NUMBER_ID` is set
- Ensure storage directory is writable

### State Not Transitioning
- Check `next_states` mapping in config
- Verify user input matches expected format
- Check logs for state transitions

## Logs

All webhook activity is logged:
- Incoming messages: `Log::info('WhatsApp Webhook Received')`
- Errors: `Log::error('WhatsApp Webhook Error')`
- State transitions: Check `bot_sessions` table

