<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TradeInController;
use App\Http\Controllers\Api\TradeInBasePriceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Controllers\Api\WhatsAppTemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// WhatsApp Webhook (public, but should be verified in production)
// GET for webhook verification, POST for incoming messages
Route::match(['get', 'post'], '/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle']);

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });
});

// Protected API routes - require authentication
Route::middleware(['auth:sanctum', 'scope.store'])->group(function () {
    
    // User management routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        
        // Admin only routes
        Route::middleware('role.admin')->group(function () {
            Route::post('/', [UserController::class, 'store']);
            Route::put('/{id}', [UserController::class, 'update']);
            Route::patch('/{id}', [UserController::class, 'update']);
            Route::delete('/{id}', [UserController::class, 'destroy']);
        });
        
        // Store users can update their own profile
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::patch('/profile', [UserController::class, 'updateProfile']);
    });
    
    // Ticket routes
    Route::prefix('tickets')->group(function () {
        Route::get('/', [TicketController::class, 'index']);
        Route::get('/statistics', [TicketController::class, 'statistics']);
        Route::post('/', [TicketController::class, 'store']);
        Route::get('/{id}', [TicketController::class, 'show']);
        Route::put('/{id}', [TicketController::class, 'update']);
        Route::patch('/{id}', [TicketController::class, 'update']);
        Route::delete('/{id}', [TicketController::class, 'destroy']);
    });

    // WhatsApp Template routes
    Route::prefix('whatsapp/templates')->group(function () {
        Route::post('/first-reply', [WhatsAppTemplateController::class, 'sendFirstReply']);
        Route::post('/price-display', [WhatsAppTemplateController::class, 'sendPriceDisplay']);
        Route::post('/location-request', [WhatsAppTemplateController::class, 'sendLocationRequest']);
        Route::post('/success-coupon', [WhatsAppTemplateController::class, 'sendSuccessCouponConfirmation']);
        Route::post('/ticket/{ticketId}/confirmation', [WhatsAppTemplateController::class, 'sendTicketConfirmation']);
    });

    // Product routes
    Route::prefix('products')->group(function () {
        Route::get('/recommended', [ProductController::class, 'recommended']);
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
    });

    // Order routes
    Route::prefix('orders')->group(function () {
        Route::post('/draft', [OrderController::class, 'createDraft']);
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::patch('/{id}/status', [OrderController::class, 'updateStatus']);
    });

    // Trade-in routes
    Route::prefix('tradeins')->group(function () {
        Route::get('/statistics', [TradeInController::class, 'statistics']);
        Route::get('/', [TradeInController::class, 'index']);
        Route::get('/{id}', [TradeInController::class, 'show']);
        Route::put('/{id}', [TradeInController::class, 'update']);
        Route::patch('/{id}', [TradeInController::class, 'update']);
        Route::patch('/{id}/status', [TradeInController::class, 'updateStatus']);
        Route::patch('/{id}/price', [TradeInController::class, 'updatePrice']);
        Route::patch('/{id}/final-price', [TradeInController::class, 'updateFinalPrice']);
        Route::get('/{id}/price-preview', [TradeInController::class, 'calculatePricePreview']);
    });

    // Trade-in base prices routes
    Route::prefix('tradein-base-prices')->group(function () {
        Route::get('/', [TradeInBasePriceController::class, 'index']);
        Route::post('/', [TradeInBasePriceController::class, 'store']);
        Route::get('/{id}', [TradeInBasePriceController::class, 'show']);
        Route::put('/{id}', [TradeInBasePriceController::class, 'update']);
        Route::patch('/{id}', [TradeInBasePriceController::class, 'update']);
        Route::delete('/{id}', [TradeInBasePriceController::class, 'destroy']);
    });
});

