<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StripeConnectApiController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TerminalController;
use Stripe\Stripe;

// Public Stripe Connect Routes
Route::prefix('stripe')->group(function () {
    Route::get('/auth-url', [StripeConnectApiController::class, 'getAuthUrl']);
    Route::get('/callback', [StripeConnectApiController::class, 'handleCallback'])->name('api.stripe.callback');
    Route::post('exchange-code', [StripeConnectApiController::class, 'exchangeCode']);
    Route::post('/create_intent', [PaymentController::class, 'createPaymentIntent']);
    Route::get('secret_key', [StripeConnectApiController::class, 'getSecretKey']);
    Route::post('/terminal/connection_token', [TerminalController::class, 'getConnectionToken']);
});

// Protected Routes (require JWT token)
Route::middleware('stripe.auth')->group(function () {
    // Terminal routes
   
    
    // User profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Payment routes
    Route::prefix('payment')->group(function () {
        // Route::post('/create-intent', [PaymentController::class, 'createPaymentIntent']);
        Route::post('/terminal/create-reader', [PaymentController::class, 'createTerminalReader']);
        Route::post('/terminal/process', [PaymentController::class, 'processTerminalPayment']);
        Route::post('/capture', [PaymentController::class, 'capturePayment']);
        Route::get('/transactions', [PaymentController::class, 'getTransactions']);
    });
});
