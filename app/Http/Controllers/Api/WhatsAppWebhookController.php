<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotSession;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PriceMatrix;
use App\Models\Product;
use App\Models\ProductRecommendation;
use App\Models\Ticket;
use App\Models\TradeIn;
use App\Services\TradeInPriceEngine;
use App\Services\TradeInPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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

        // Handle trade-in storage selection
        if ($currentState === 'tradein_ask_storage' && isset($stateConfig['storage_map'][$text])) {
            $storage = $stateConfig['storage_map'][$text];
            $session->updateState('tradein_ask_color', [
                'storage' => $storage,
            ]);
            $this->processState($session, 'tradein_ask_color');
            return;
        }

        // Handle trade-in condition selection
        if ($currentState === 'tradein_ask_condition' && isset($stateConfig['condition_map'][$text])) {
            $condition = $stateConfig['condition_map'][$text];
            $session->updateState('tradein_ask_battery', [
                'condition' => $condition,
            ]);
            $this->processState($session, 'tradein_ask_battery');
            return;
        }

        // Handle trade-in battery health
        if ($currentState === 'tradein_ask_battery') {
            $batteryHealth = null;
            if (strtolower($text) !== 'bilmiyorum' && is_numeric($text)) {
                $batteryHealth = (int) $text;
                if ($batteryHealth < 0) $batteryHealth = 0;
                if ($batteryHealth > 100) $batteryHealth = 100;
            }
            $session->updateState('tradein_ask_photos', [
                'battery_health' => $batteryHealth,
            ]);
            $this->processState($session, 'tradein_ask_photos');
            return;
        }

        // Handle trade-in photos done
        if ($currentState === 'tradein_wait_photos' && (strtolower($text) === 'tamam' || strtolower($text) === 'done')) {
            $session->updateState('tradein_calculate_price');
            $this->processState($session, 'tradein_calculate_price');
            return;
        }

        // Handle trade-in payment option selection
        if ($currentState === 'tradein_ask_payment' && isset($stateConfig['payment_map'][$text])) {
            $paymentOption = $stateConfig['payment_map'][$text];
            $session->updateState('tradein_create', [
                'payment_option' => $paymentOption,
            ]);
            $this->processState($session, 'tradein_create');
            return;
        }

        // Handle product selection (numeric input)
        if ($currentState === 'product_selection' && is_numeric($text)) {
            $productNumber = (int) $text;
            $availableProducts = $session->data['available_products'] ?? [];
            
            if (isset($availableProducts[$productNumber])) {
                $nextState = $stateConfig['next_states']['*'] ?? 'create_order_draft';
                $session->updateState($nextState, [
                    'selected_product' => $productNumber,
                ]);
                $this->processState($session, $nextState);
                return;
            } else {
                $this->sendMessage($session->phone_number, 'Geçersiz ürün numarası. Lütfen tekrar deneyin.');
                return;
            }
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

        // Only accept media in wait_for_photos or tradein_wait_photos state
        if ($currentState !== 'wait_for_photos' && $currentState !== 'tradein_wait_photos') {
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
            if ($session->current_state === 'tradein_wait_photos') {
                $this->sendMessage($session->phone_number, 'Fotoğraf işlenirken bir hata oluştu. Lütfen tekrar deneyin veya "tamam" yazın.');
            } else {
                $this->sendMessage($session->phone_number, 'Sorry, there was an error processing your photo. Please try again or type "done" to continue.');
            }
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
                case 'show_products':
                    $this->showProducts($session);
                    return; // Message already sent in method
                case 'create_order_draft':
                    $this->createOrderDraft($session);
                    return; // Message already sent in method
                case 'show_order_status':
                    $this->showOrderStatus($session);
                    return; // Message already sent in method
                case 'calculate_tradein_price':
                    $this->calculateTradeInPrice($session);
                    return; // Message already sent in method
                case 'create_tradein':
                    $this->createTradeIn($session);
                    return; // Message already sent in method
                case 'show_tradein_status':
                    $this->showTradeInStatus($session);
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

    /**
     * Show recommended products for the device model.
     *
     * @param  \App\Models\BotSession  $session
     * @return void
     */
    protected function showProducts(BotSession $session): void
    {
        try {
            $data = $session->data ?? [];
            $model = $data['model'] ?? null;

            if (!$model) {
                $this->sendMessage($session->phone_number, 'Model bilgisi bulunamadı. Lütfen tekrar deneyin.');
                $session->updateState('start');
                return;
            }

            // Get recommended products
            $recommendations = ProductRecommendation::with('product')
                ->forModel($model)
                ->orderedByPriority()
                ->whereHas('product', function ($query) {
                    $query->active()->inStock();
                })
                ->limit(3)
                ->get();

            // If no recommendations, get random products
            if ($recommendations->isEmpty()) {
                $products = Product::active()
                    ->inStock()
                    ->inRandomOrder()
                    ->limit(3)
                    ->get();
            } else {
                $products = $recommendations->pluck('product');
            }

            if ($products->isEmpty()) {
                $this->sendMessage($session->phone_number, 'Üzgünüz, şu anda uygun ürün bulunmamaktadır.');
                $session->updateState('ask_further_help');
                return;
            }

            // Format products list
            $productsList = "📱 *Modelinize Uygun Aksesuarlar:*\n\n";
            $productIds = [];

            foreach ($products as $index => $product) {
                $number = $index + 1;
                $productsList .= "{$number}. *{$product->name}*\n";
                $productsList .= "   💰 Fiyat: {$product->price} TL\n";
                if ($product->description) {
                    $productsList .= "   📝 {$product->description}\n";
                }
                $productsList .= "\n";
                $productIds[$number] = $product->id;
            }

            // Store product IDs in session for order creation
            $session->setData('available_products', $productIds);
            $session->updateState('product_selection');

            // Send products list
            $message = $productsList . "\nSatın almak istediğiniz ürün numarasını yazın (örn: 1, 2, 3) veya 'iptal' yazın.";
            $this->sendMessage($session->phone_number, $message);
        } catch (\Exception $e) {
            Log::error('Show products error', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
            ]);
            $this->sendMessage($session->phone_number, 'Ürünler yüklenirken bir hata oluştu. Lütfen tekrar deneyin.');
            $session->updateState('ask_further_help');
        }
    }

    /**
     * Create order draft from session.
     *
     * @param  \App\Models\BotSession  $session
     * @return void
     */
    protected function createOrderDraft(BotSession $session): void
    {
        try {
            $data = $session->data ?? [];
            $selectedProduct = $data['selected_product'] ?? null;
            $availableProducts = $data['available_products'] ?? [];

            if (!$selectedProduct || !isset($availableProducts[$selectedProduct])) {
                $this->sendMessage($session->phone_number, 'Geçersiz ürün seçimi. Lütfen tekrar deneyin.');
                $session->updateState('show_products');
                return;
            }

            $productId = $availableProducts[$selectedProduct];
            $product = Product::find($productId);

            if (!$product || !$product->is_active || $product->stock <= 0) {
                $this->sendMessage($session->phone_number, 'Seçilen ürün şu anda mevcut değil.');
                $session->updateState('ask_further_help');
                return;
            }

            // Get customer
            $customer = Customer::where('phone_number', $session->phone_number)->first();
            if (!$customer) {
                $this->sendMessage($session->phone_number, 'Müşteri bilgisi bulunamadı.');
                $session->updateState('start');
                return;
            }

            // Create order draft
            $order = Order::create([
                'customer_id' => $customer->id,
                'ticket_id' => $session->ticket_id,
                'store_id' => null, // Will be assigned later
                'total_price' => $product->price,
                'payment_status' => 'pending',
                'order_status' => 'draft',
            ]);

            // Create order item
            $order->orderItems()->create([
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => $product->price,
            ]);

            // Update session
            $session->setData('order_id', $order->id);
            $session->update(['order_id' => $order->id]);

            // Get pickup instructions
            $pickupInstructions = $this->getPickupInstructions($order);

            // Send confirmation
            $message = "✅ *Siparişiniz Oluşturuldu!*\n\n";
            $message .= "Sipariş No: " . substr($order->id, 0, 8) . "\n";
            $message .= "Ürün: {$product->name}\n";
            $message .= "Toplam: {$order->total_price} TL\n\n";
            $message .= "{$pickupInstructions}\n\n";
            $message .= "Başka bir şey için yardımcı olabilir miyim?\n\n1. Yeni talep\n2. Sipariş durumu\n3. Hayır, teşekkürler";

            $this->sendMessage($session->phone_number, $message);
            $session->updateState('order_created');
        } catch (\Exception $e) {
            Log::error('Create order draft error', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
            ]);
            $this->sendMessage($session->phone_number, 'Sipariş oluşturulurken bir hata oluştu. Lütfen tekrar deneyin.');
            $session->updateState('ask_further_help');
        }
    }

    /**
     * Show order status.
     *
     * @param  \App\Models\BotSession  $session
     * @return void
     */
    protected function showOrderStatus(BotSession $session): void
    {
        $orderId = $session->data['order_id_search'] ?? null;

        if (!$orderId) {
            $this->sendMessage($session->phone_number, 'Sipariş bulunamadı. Lütfen sipariş numaranızı kontrol edin.');
            $session->updateState('start');
            return;
        }

        $order = Order::with(['customer', 'store', 'orderItems.product'])->find($orderId);

        if (!$order) {
            $this->sendMessage($session->phone_number, 'Sipariş bulunamadı. Lütfen sipariş numaranızı kontrol edin.');
            $session->updateState('start');
            return;
        }

        $statusEmoji = [
            'draft' => '📝',
            'in_store' => '🏪',
            'on_the_way' => '🚚',
            'delivered' => '✅',
        ];

        $paymentEmoji = [
            'pending' => '⏳',
            'paid' => '✅',
            'canceled' => '❌',
        ];

        $message = "📦 *Sipariş Durumu*\n\n";
        $message .= "Sipariş No: " . substr($order->id, 0, 8) . "\n";
        $message .= "Durum: {$statusEmoji[$order->order_status] ?? ''} {$order->order_status}\n";
        $message .= "Ödeme: {$paymentEmoji[$order->payment_status] ?? ''} {$order->payment_status}\n";
        $message .= "Toplam: {$order->total_price} TL\n\n";

        if ($order->orderItems->isNotEmpty()) {
            $message .= "*Ürünler:*\n";
            foreach ($order->orderItems as $item) {
                $message .= "• {$item->product->name} x{$item->quantity} - {$item->price} TL\n";
            }
        }

        if ($order->store) {
            $message .= "\nMağaza: {$order->store->name}";
            if ($order->store->address) {
                $message .= "\nAdres: {$order->store->address}";
            }
        }

        $this->sendMessage($session->phone_number, $message);
        $session->updateState('start');
    }

    /**
     * Get pickup instructions for order.
     *
     * @param  \App\Models\Order  $order
     * @return string
     */
    protected function getPickupInstructions(Order $order): string
    {
        if ($order->store) {
            return sprintf(
                "Siparişiniz hazır olduğunda %s mağazamızdan teslim alabilirsiniz.\n\nAdres: %s",
                $order->store->name,
                $order->store->address ?? 'Adres bilgisi için mağazamızla iletişime geçin'
            );
        }

        return "Siparişiniz hazır olduğunda size bildirilecektir.";
    }

    /**
     * Calculate trade-in price.
     *
     * @param  \App\Models\BotSession  $session
     * @return void
     */
    protected function calculateTradeInPrice(BotSession $session): void
    {
        try {
            $data = $session->data ?? [];
            $brand = $data['brand'] ?? null;
            $model = $data['model'] ?? null;
            $condition = $data['condition'] ?? 'B';
            $batteryHealth = $data['battery_health'] ?? null;
            $storage = $data['storage'] ?? null;

            if (!$brand || !$model) {
                $this->sendMessage($session->phone_number, 'Marka veya model bilgisi bulunamadı. Lütfen tekrar deneyin.');
                $session->updateState('start');
                return;
            }

            $pricingService = new TradeInPricingService();
            $priceResult = $pricingService->calculateTradeInPrice($brand, $model, $storage, $condition, $batteryHealth);

            // Store price in session
            $session->setData('offer_min', $priceResult['min']);
            $session->setData('offer_max', $priceResult['max']);

            // Update session state
            $session->updateState('tradein_show_offer');

            // Format message
            $conditionLabels = ['A' => 'Mükemmel', 'B' => 'İyi', 'C' => 'Orta'];
            $conditionLabel = $conditionLabels[$condition] ?? 'Bilinmiyor';

            $message = "💰 *Fiyat Teklifiniz*\n\n";
            $message .= "Marka: {$brand}\n";
            $message .= "Model: {$model}\n";
            $message .= "Durum: {$conditionLabel}\n";
            if ($storage) {
                $message .= "Depolama: {$storage}\n";
            }
            if ($batteryHealth !== null) {
                $message .= "Pil Sağlığı: %{$batteryHealth}\n";
            }
            $message .= "\n";
            $message .= "Teklif Aralığı: {$priceResult['min']} - {$priceResult['max']} TL\n\n";
            $message .= "Bu teklifi kabul ediyor musunuz?\n\n1. Evet, kabul ediyorum\n2. Hayır, teşekkürler";

            $this->sendMessage($session->phone_number, $message);
        } catch (\Exception $e) {
            Log::error('Calculate trade-in price error', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
            ]);
            $this->sendMessage($session->phone_number, 'Fiyat hesaplanırken bir hata oluştu. Lütfen tekrar deneyin.');
            $session->updateState('start');
        }
    }

    /**
     * Create trade-in from session.
     *
     * @param  \App\Models\BotSession  $session
     * @return void
     */
    protected function createTradeIn(BotSession $session): void
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

            // Record visit
            $customer->recordVisit();

            // Create trade-in
            $tradeIn = TradeIn::create([
                'customer_id' => $customer->id,
                'brand' => $data['brand'] ?? 'Unknown',
                'model' => $data['model'] ?? 'Unknown',
                'storage' => $data['storage'] ?? null,
                'color' => $data['color'] ?? null,
                'condition' => $data['condition'] ?? 'B',
                'battery_health' => $data['battery_health'] ?? null,
                'photos' => $data['photos'] ?? null,
                'offer_min' => $data['offer_min'] ?? null,
                'offer_max' => $data['offer_max'] ?? null,
                'final_price' => null, // Will be set by admin if needed
                'payment_option' => $data['payment_option'] ?? null,
                'status' => 'new',
            ]);

            // Update session
            $session->update([
                'customer_id' => $customer->id,
                'tradein_id' => $tradeIn->id,
            ]);

            // Send confirmation
            $message = "✅ *Teklifiniz Kaydedildi!*\n\n";
            $message .= "Teklif No: " . substr($tradeIn->id, 0, 8) . "\n";
            $message .= "Marka: {$tradeIn->brand}\n";
            $message .= "Model: {$tradeIn->model}\n";
            $message .= "Teklif: {$tradeIn->offer_min} - {$tradeIn->offer_max} TL\n\n";
            $message .= "En kısa sürede sizinle iletişime geçeceğiz. Başka bir şey için yardımcı olabilir miyim?\n\n";
            $message .= "1. Yeni talep\n2. Teklif durumu\n3. Hayır, teşekkürler";

            $this->sendMessage($session->phone_number, $message);
            $session->updateState('tradein_created');
        } catch (\Exception $e) {
            Log::error('Create trade-in error', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
            ]);
            $this->sendMessage($session->phone_number, 'Teklif kaydedilirken bir hata oluştu. Lütfen tekrar deneyin.');
            $session->updateState('start');
        }
    }

    /**
     * Show trade-in status.
     *
     * @param  \App\Models\BotSession  $session
     * @return void
     */
    protected function showTradeInStatus(BotSession $session): void
    {
        $tradeInId = $session->data['tradein_id_search'] ?? null;

        if (!$tradeInId) {
            $this->sendMessage($session->phone_number, 'Teklif bulunamadı. Lütfen teklif numaranızı kontrol edin.');
            $session->updateState('start');
            return;
        }

        $tradeIn = TradeIn::with('customer')->find($tradeInId);

        if (!$tradeIn) {
            $this->sendMessage($session->phone_number, 'Teklif bulunamadı. Lütfen teklif numaranızı kontrol edin.');
            $session->updateState('start');
            return;
        }

        $statusEmoji = [
            'new' => '🆕',
            'waiting_device' => '⏳',
            'completed' => '✅',
            'canceled' => '❌',
        ];

        $conditionLabels = ['A' => 'Mükemmel', 'B' => 'İyi', 'C' => 'Orta'];

        $message = "📱 *Teklif Durumu*\n\n";
        $message .= "Teklif No: " . substr($tradeIn->id, 0, 8) . "\n";
        $message .= "Durum: {$statusEmoji[$tradeIn->status] ?? ''} {$tradeIn->status_label}\n";
        $message .= "Marka: {$tradeIn->brand}\n";
        $message .= "Model: {$tradeIn->model}\n";
        $message .= "Durum: {$conditionLabels[$tradeIn->condition] ?? 'Bilinmiyor'}\n";
        
        if ($tradeIn->storage) {
            $message .= "Depolama: {$tradeIn->storage}\n";
        }
        if ($tradeIn->battery_health !== null) {
            $message .= "Pil Sağlığı: %{$tradeIn->battery_health}\n";
        }
        
        if ($tradeIn->offer_min && $tradeIn->offer_max) {
            $message .= "Teklif: {$tradeIn->offer_min} - {$tradeIn->offer_max} TL\n";
        }

        $this->sendMessage($session->phone_number, $message);
        $session->updateState('start');
    }
}

