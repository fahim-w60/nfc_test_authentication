<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StripeConnectApiController;
use App\Http\Controllers\Api\PaymentController;

// Public Stripe Connect Routes
Route::prefix('stripe')->group(function () {
    Route::get('/auth-url', [StripeConnectApiController::class, 'getAuthUrl']);
    Route::get('/callback', [StripeConnectApiController::class, 'handleCallback'])->name('api.stripe.callback');
    Route::post('exchange-code', [StripeConnectApiController::class, 'exchangeCode']);
});

// Protected Routes (require JWT token)
Route::middleware('stripe.auth')->group(function () {
    // User profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Payment routes
    Route::prefix('payment')->group(function () {
        Route::post('/create-intent', [PaymentController::class, 'createPaymentIntent']);
        Route::post('/process-nfc', [PaymentController::class, 'processNfcPayment']);
        Route::post('/capture', [PaymentController::class, 'capturePayment']);
        Route::get('/transactions', [PaymentController::class, 'getTransactions']);
    });
});
