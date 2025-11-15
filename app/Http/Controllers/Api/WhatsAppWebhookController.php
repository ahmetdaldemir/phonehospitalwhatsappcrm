<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotSession;
use App\Models\Customer;
use App\Models\PriceMatrix;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle incoming WhatsApp webhook.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function handle(Request $request)
    {
        // Handle webhook verification (GET request)
        if ($request->isMethod('GET')) {
            return $this->verifyWebhook($request);
        }

        try {
            // Verify webhook (optional - add signature verification if needed)
            $data = $request->all();

            Log::info('WhatsApp Webhook Received', $data);

            // Handle different webhook types
            if (isset($data['entry'][0]['changes'][0]['value']['messages'])) {
                $messages = $data['entry'][0]['changes'][0]['value']['messages'];
                
                foreach ($messages as $message) {
                    $this->processMessage($message, $data);
                }
            }

            // WhatsApp requires 200 response
            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error'], 200); // Still return 200 to WhatsApp
        }
    }

    /**
     * Verify WhatsApp webhook.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|string
     */
    protected function verifyWebhook(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('services.whatsapp.verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('WhatsApp Webhook Verified');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp Webhook Verification Failed', [
            'mode' => $mode,
            'token' => $token,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Process incoming message.
     *
     * @param  array  $message
     * @param  array  $webhookData
     * @return void
     */
    protected function processMessage(array $message, array $webhookData): void
    {
        $phoneNumber = $this->extractPhoneNumber($message['from']);
        $messageId = $message['id'] ?? null;
        $messageType = $message['type'] ?? 'text';

        // Get or create bot session
        $session = BotSession::getOrCreate($phoneNumber);

        // Process based on message type
        switch ($messageType) {
            case 'text':
                $this->handleTextMessage($session, $message['text']['body'] ?? '');
                break;
            case 'image':
            case 'video':
            case 'document':
                $this->handleMediaMessage($session, $message, $messageType);
                break;
            case 'location':
                $this->handleLocationMessage($session, $message['location']);
                break;
            default:
                $this->sendMessage($phoneNumber, config('bot_flow.default_messages.invalid_input'));
        }
    }

    /**
     * Handle text message.
     *
     * @param  \App\Models\BotSession  $session
     * @param  string  $text
     * @return void
     */
    protected function handleTextMessage(BotSession $session, string $text): void
    {
        $text = trim($text);
        $currentState = $session->current_state;
        $flow = config('bot_flow.flow');

        if (!isset($flow[$currentState])) {
            $this->sendMessage($session->phone_number, config('bot_flow.default_messages.error'));
            return;
        }

        $stateConfig = $flow[$currentState];
        $userInput = strtolower($text);

        // Check if input matches any next state
        $nextState = null;
        foreach ($stateConfig['next_states'] as $key => $state) {
            if ($key === '*' || $key === $userInput || $key === $text) {
                $nextState = $state;
                break;
            }
        }

        // Handle special states
        if ($currentState === 'get_problem' && isset($stateConfig['problem_map'][$text])) {
            $session->updateState('get_problem_other', [
                'problem_type' => $stateConfig['problem_map'][$text],
            ]);
            $this->processState($session, 'get_problem_other');
            return;
        }

        // Handle "done" for photo uploads
        if ($currentState === 'wait_for_photos' && $userInput === 'done') {
            $nextState = $stateConfig['next_states']['done'] ?? 'get_customer_name';
            $session->updateState($nextState);
            $this->processState($session, $nextState);
            return;
        }

        // Save data if data_key is specified
        if (isset($stateConfig['data_key']) && $nextState) {
            $session->updateState($nextState, [
                $stateConfig['data_key'] => $text,
            ]);
        } elseif ($nextState) {
            $session->updateState($nextState);
        }

        // Process next state
        if ($nextState) {
            $this->processState($session, $nextState);
        } else {
            $this->sendMessage($session->phone_number, config('bot_flow.default_messages.invalid_input'));
        }
    }

    /**
     * Handle media message.
     *
     * @param  \App\Models\BotSession  $session
     * @param  array  $message
     * @param  string  $type
     * @return void
     */
    protected function handleMediaMessage(BotSession $session, array $message, string $type): void
    {
        $currentState = $session->current_state;

        // Only accept media in wait_for_photos state
        if ($currentState !== 'wait_for_photos') {
            $this->sendMessage($session->phone_number, 'Please send photos only when requested. Type "back" to return to main menu.');
            return;
        }

        try {
            $mediaId = $message[$type]['id'] ?? null;
            if (!$mediaId) {
                throw new \Exception('Media ID not found');
            }

            // Download media from WhatsApp
            $mediaUrl = $this->downloadMedia($mediaId);
            if (!$mediaUrl) {
                throw new \Exception('Failed to download media');
            }

            // Save to storage
            $savedPath = $this->saveMedia($mediaUrl, $type, $session->id);

            // Add to session data
            $photos = $session->data['photos'] ?? [];
            $photos[] = $savedPath;
            $session->updateState('wait_for_photos', ['photos' => $photos]);

            $this->sendMessage($session->phone_number, '✅ Photo received! Send another photo or type "done" to continue.');
        } catch (\Exception $e) {
            Log::error('Media handling error', ['error' => $e->getMessage()]);
            $this->sendMessage($session->phone_number, 'Sorry, there was an error processing your photo. Please try again or type "done" to continue.');
        }
    }

    /**
     * Handle location message.
     *
     * @param  \App\Models\BotSession  $session
     * @param  array  $location
     * @return void
     */
    protected function handleLocationMessage(BotSession $session, array $location): void
    {
        // Store location in session data
        $session->updateState($session->current_state, [
            'location' => [
                'latitude' => $location['latitude'] ?? null,
                'longitude' => $location['longitude'] ?? null,
            ],
        ]);

        $this->sendMessage($session->phone_number, '📍 Location received! Thank you.');
    }

    /**
     * Process state and execute actions.
     *
     * @param  \App\Models\BotSession  $session
     * @param  string  $state
     * @return void
     */
    protected function processState(BotSession $session, string $state): void
    {
        $flow = config('bot_flow.flow');

        if (!isset($flow[$state])) {
            $this->sendMessage($session->phone_number, config('bot_flow.default_messages.error'));
            return;
        }

        $stateConfig = $flow[$state];
        $message = $stateConfig['message'] ?? '';

        // Handle special actions
        if (isset($stateConfig['action'])) {
            switch ($stateConfig['action']) {
                case 'create_ticket':
                    $this->createTicketFromSession($session);
                    $message = str_replace('{ticket_id}', $session->ticket_id ?? 'N/A', $message);
                    break;
                case 'show_ticket_status':
                    $this->showTicketStatus($session);
                    return; // Message already sent in method
            }
        }

        // Replace placeholders in message
        $message = $this->replacePlaceholders($message, $session);

        $this->sendMessage($session->phone_number, $message);
    }

    /**
     * Create ticket from session data.
     *
     * @param  \App\Models\BotSession  $session
     * @return void
     */
    protected function createTicketFromSession(BotSession $session): void
    {
        try {
            $data = $session->data ?? [];

            // Get or create customer
            $customer = Customer::firstOrCreate(
                ['phone_number' => $session->phone_number],
                [
                    'name' => $data['customer_name'] ?? null,
                    'total_visits' => 0,
                ]
            );

            // Update customer name if provided
            if (!empty($data['customer_name']) && !$customer->name) {
                $customer->update(['name' => $data['customer_name']]);
            }

            // Record visit
            $customer->recordVisit();

            // Get price from matrix if available
            $priceMatrix = null;
            if (isset($data['brand']) && isset($data['model']) && isset($data['problem_type'])) {
                $priceMatrix = PriceMatrix::findPrice(
                    $data['brand'],
                    $data['model'],
                    $data['problem_type']
                );
            }

            // Create ticket
            $ticket = Ticket::create([
                'customer_id' => $customer->id,
                'brand' => $data['brand'] ?? 'Unknown',
                'model' => $data['model'] ?? 'Unknown',
                'problem_type' => $data['problem_type'] ?? 'Other',
                'price_min' => $priceMatrix->price_min ?? null,
                'price_max' => $priceMatrix->price_max ?? null,
                'status' => 'new',
                'photos' => $data['photos'] ?? null,
            ]);

            // Update session
            $session->update([
                'customer_id' => $customer->id,
                'ticket_id' => $ticket->id,
                'current_state' => 'ticket_created',
            ]);

            // Send confirmation with ticket ID
            $message = config('bot_flow.flow')['ticket_created']['message'];
            $message = str_replace('{ticket_id}', $ticket->id, $message);
            $this->sendMessage($session->phone_number, $message);
        } catch (\Exception $e) {
            Log::error('Ticket creation error', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
            ]);
            $this->sendMessage($session->phone_number, config('bot_flow.default_messages.error'));
        }
    }

    /**
     * Show ticket status.
     *
     * @param  \App\Models\BotSession  $session
     * @return void
     */
    protected function showTicketStatus(BotSession $session): void
    {
        $ticketId = $session->data['ticket_id_search'] ?? null;

        if (!$ticketId) {
            $this->sendMessage($session->phone_number, config('bot_flow.default_messages.no_ticket_found'));
            $session->updateState('start');
            return;
        }

        $ticket = Ticket::with('customer')->find($ticketId);

        if (!$ticket) {
            $this->sendMessage($session->phone_number, config('bot_flow.default_messages.no_ticket_found'));
            $session->updateState('start');
            return;
        }

        $statusEmoji = [
            'new' => '🆕',
            'directed' => '➡️',
            'completed' => '✅',
            'canceled' => '❌',
        ];

        $message = "📋 *Ticket Status*\n\n";
        $message .= "Ticket ID: {$ticket->id}\n";
        $message .= "Status: {$statusEmoji[$ticket->status] ?? ''} {$ticket->status}\n";
        $message .= "Brand: {$ticket->brand}\n";
        $message .= "Model: {$ticket->model}\n";
        $message .= "Problem: {$ticket->problem_type}\n";
        
        if ($ticket->price_min && $ticket->price_max) {
            $message .= "Estimated Price: {$ticket->price_min} - {$ticket->price_max}\n";
        }

        $this->sendMessage($session->phone_number, $message);
        $session->updateState('start');
    }

    /**
     * Download media from WhatsApp.
     *
     * @param  string  $mediaId
     * @return string|null
     */
    protected function downloadMedia(string $mediaId): ?string
    {
        // This should call WhatsApp Cloud API to get media URL
        // For now, return placeholder - implement with actual WhatsApp API call
        $whatsappToken = config('services.whatsapp.token');
        $whatsappPhoneNumberId = config('services.whatsapp.phone_number_id');

        if (!$whatsappToken || !$whatsappPhoneNumberId) {
            Log::warning('WhatsApp credentials not configured');
            return null;
        }

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get("https://graph.facebook.com/v18.0/{$mediaId}", [
                'headers' => [
                    'Authorization' => "Bearer {$whatsappToken}",
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['url'] ?? null;
        } catch (\Exception $e) {
            Log::error('Media download error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Save media to storage.
     *
     * @param  string  $mediaUrl
     * @param  string  $type
     * @param  string  $sessionId
     * @return string
     */
    protected function saveMedia(string $mediaUrl, string $type, string $sessionId): string
    {
        $extension = match ($type) {
            'image' => 'jpg',
            'video' => 'mp4',
            'document' => 'pdf',
            default => 'bin',
        };

        $filename = Str::uuid() . '.' . $extension;
        $path = "ticket-media/{$sessionId}/{$filename}";

        // Download and save
        $contents = file_get_contents($mediaUrl);
        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    /**
     * Send message via WhatsApp.
     *
     * @param  string  $phoneNumber
     * @param  string  $message
     * @return void
     */
    protected function sendMessage(string $phoneNumber, string $message): void
    {
        $whatsappToken = config('services.whatsapp.token');
        $whatsappPhoneNumberId = config('services.whatsapp.phone_number_id');

        if (!$whatsappToken || !$whatsappPhoneNumberId) {
            Log::warning('WhatsApp credentials not configured, message not sent', [
                'phone' => $phoneNumber,
                'message' => $message,
            ]);
            return;
        }

        try {
            $client = new \GuzzleHttp\Client();
            $client->post("https://graph.facebook.com/v18.0/{$whatsappPhoneNumberId}/messages", [
                'headers' => [
                    'Authorization' => "Bearer {$whatsappToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'to' => $phoneNumber,
                    'type' => 'text',
                    'text' => [
                        'body' => $message,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp message send error', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
            ]);
        }
    }

    /**
     * Extract phone number from WhatsApp format.
     *
     * @param  string  $from
     * @return string
     */
    protected function extractPhoneNumber(string $from): string
    {
        // Remove @s.whatsapp.net or similar suffixes
        return preg_replace('/@.*$/', '', $from);
    }

    /**
     * Replace placeholders in message.
     *
     * @param  string  $message
     * @param  \App\Models\BotSession  $session
     * @return string
     */
    protected function replacePlaceholders(string $message, BotSession $session): string
    {
        $data = $session->data ?? [];
        
        $replacements = [
            '{ticket_id}' => $session->ticket_id ?? 'N/A',
            '{customer_name}' => $data['customer_name'] ?? 'there',
            '{brand}' => $data['brand'] ?? '',
            '{model}' => $data['model'] ?? '',
        ];

        foreach ($replacements as $placeholder => $value) {
            $message = str_replace($placeholder, $value, $message);
        }

        return $message;
    }
}

