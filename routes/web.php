<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StripeConnectController;

// Authentication Routes
// Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
// Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

// Stripe Connect Routes
// Route::get('/auth/stripe', [StripeConnectController::class, 'redirectToStripe'])->name('auth.stripe.login');
// Route::get('/auth/stripe/callback', [StripeConnectController::class, 'handleCallback'])->name('auth.stripe.callback');

// Dashboard
// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->name('dashboard')->middleware('auth');

// Stripe Account Check
// Route::get('/check-stripe-account', [AuthController::class, 'checkStripeAccountType']);