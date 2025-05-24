<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StripeConnectApiController;

// Public Stripe Connect Routes
Route::prefix('stripe')->group(function () {
    Route::get('/auth-url', [StripeConnectApiController::class, 'getAuthUrl']);
    Route::get('/callback', [StripeConnectApiController::class, 'handleCallback'])->name('api.stripe.callback');
});

// Protected Routes (require Stripe token)
Route::middleware('stripe.auth')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
