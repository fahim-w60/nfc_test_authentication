<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\OAuth;
use Stripe\Account;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class StripeConnectController extends Controller
{
    public function redirectToStripe()
    {
        // Set your secret key
        Stripe::setApiKey(config('services.stripe.secret'));
        
        // Generate a random state string for CSRF protection
        $state = bin2hex(random_bytes(16));
        Session::put('stripe_state', $state);
        
        $params = [
            'response_type' => 'code',
            'client_id' => config('services.stripe.client_id'),
            'scope' => 'read_write',
            'redirect_uri' => route('auth.stripe.callback'), // Using named route
            'state' => $state,
            'stripe_user[country]' => 'US', // Set your country
            'stripe_user[type]' => 'standard' // Specify Standard account type
        ];
        
        // Add user information if available
        if (Auth::check()) {
            $params['stripe_user'] = [
                'email' => Auth::user()->email,
                'url' => config('app.url'),
            ];
        }
        
        // Redirect to Stripe's OAuth form for Standard accounts
        return redirect('https://connect.stripe.com/oauth/authorize?' . http_build_query($params));
    }

    public function handleCallback(Request $request)
    {
        try {
            // Verify the state parameter
            if ($request->state !== Session::pull('stripe_state')) {
                throw new \Exception('Invalid state parameter');
            }

            if (!$request->code) {
                throw new \Exception('Authorization code not received');
            }

            // Set your secret key
            Stripe::setApiKey(config('services.stripe.secret'));

            // Exchange the authorization code for an access token
            $response = OAuth::token([
                'grant_type' => 'authorization_code',
                'code' => $request->code,
            ]);

            // Get the connected account ID
            $connectedAccountId = $response->stripe_user_id;
            
            // Retrieve the connected account details
            $account = Account::retrieve($connectedAccountId);
            
            // Store or update user information
            $userData = [
                'stripe_account_id' => $connectedAccountId,
                'stripe_access_token' => $response->access_token,
                'stripe_refresh_token' => $response->refresh_token ?? null
            ];

            if (Auth::check()) {
                User::where('id', Auth::id())->update($userData);
            } else {
                // Create a new user if not logged in
                $userData['email'] = $account->email;
                $userData['name'] = $account->business_profile->name ?? 'Stripe User';
                $userData['password'] = bcrypt(Str::random(16));
                User::create($userData);
            }

            return redirect()->route('dashboard')->with('success', 'Your Stripe account has been connected successfully!');
            
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Failed to connect Stripe account: ' . $e->getMessage());
        }
    }
}