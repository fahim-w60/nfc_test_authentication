<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StripeConnectApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Stripe Connect API Routes
Route::prefix('stripe')->group(function () {
    Route::get('/auth-url', [StripeConnectApiController::class, 'getAuthUrl']);
    Route::get('/callback', [StripeConnectApiController::class, 'handleCallback'])->name('api.stripe.callback');
});
