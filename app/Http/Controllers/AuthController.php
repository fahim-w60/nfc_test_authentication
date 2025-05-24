<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    private function checkStripeAccountType()
    {
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            
            // Get the platform account details
            $account = \Stripe\Account::retrieve();
            
            Log::info('Stripe Account Details', [
                'account_id' => $account->id,
                'type' => $account->type,
                'capabilities' => $account->capabilities ?? [],
                'settings' => [
                    'connect_enabled' => $account->settings->connect_enabled ?? false,
                    'payouts_enabled' => $account->settings->payouts_enabled ?? false
                ]
            ]);
            
            return $account;
            
        } catch (\Exception $e) {
            Log::error('Error checking Stripe account: ' . $e->getMessage());
            return null;
        }
    }

    public function stripeLogin()
    {
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            
            // Debug information
            \Log::info('Stripe Connect Settings:', [
                'client_id' => config('services.stripe.client_id'),
                'redirect_uri' => route('auth.stripe.callback')
            ]);
            
            // Using Standard Connect OAuth URL
            $url = 'https://connect.stripe.com/oauth/authorize?' . http_build_query([
                'response_type' => 'code',
                'redirect_uri' => route('auth.stripe.callback'),
                'client_id' => config('services.stripe.client_id'),
                'scope' => 'read_write',
                'state' => csrf_token(),
                'stripe_user[type]' => 'standard', // Explicitly request Standard account
                'stripe_user[country]' => 'US', // Default to US, change if needed
            ]);
            
            return redirect($url);
        } catch (\Exception $e) {
            \Log::error('Stripe Login Error: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Unable to connect to Stripe: ' . $e->getMessage());
        }
    }

    public function handleStripeCallback(Request $request)
    {
        try {
            if (!$request->code) {
                \Log::error('No code received from Stripe');
                return redirect()->route('login')->with('error', 'Authentication failed: No code received from Stripe');
            }

            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            
            if ($request->state !== csrf_token()) {
                \Log::error('Invalid CSRF state in Stripe callback');
                return redirect()->route('login')->with('error', 'Invalid state parameter');
            }
            
            // Debug information
            \Log::info('Stripe Callback Received', [
                'code' => $request->code,
                'state' => $request->state
            ]);
            
            // Exchange the authorization code for an access token
            $response = \Stripe\OAuth::token([
                'grant_type' => 'authorization_code',
                'code' => $request->code,
            ]);
            
            \Log::info('Stripe OAuth Token Response', [
                'account_id' => $response->stripe_user_id ?? 'not_found'
            ]);
            
            // Get the connected account details
            $account = \Stripe\Account::retrieve($response->stripe_user_id);
            
            // Find or create user
            $user = User::firstOrCreate(
                ['stripe_account_id' => $response->stripe_user_id],
                [
                    'name' => $account->business_profile->name ?? $account->settings->dashboard->display_name ?? 'Stripe User',
                    'email' => $account->email,
                    'password' => bcrypt(Str::random(16))
                ]
            );
            
            Auth::login($user);
            
            return redirect()->route('dashboard')->with('success', 'Successfully logged in with Stripe!');
            
        } catch (\Exception $e) {
            \Log::error('Stripe Callback Error: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Failed to authenticate with Stripe: ' . $e->getMessage());
        }
    
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
