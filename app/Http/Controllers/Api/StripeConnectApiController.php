<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\OAuth;
use Stripe\Account;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StripeConnectApiController extends Controller
{
    public function getAuthUrl(Request $request)
    {
        // if (!$request->expectsJson()) {
        //     return response()->json(['error' => 'JSON response is required'], 406);
        // }
        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            
            $params = [
                'response_type' => 'code',
                'client_id' => config('services.stripe.client_id'),
                'scope' => 'read_write',
                'redirect_uri' => route('api.stripe.callback'),
                'stripe_user[country]' => 'US',
                'stripe_user[type]' => 'standard'
            ];

            $url = 'https://connect.stripe.com/oauth/authorize?' . http_build_query($params);

            return response()->json([
                'success' => true,
                'auth_url' => $url
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function handleCallback(Request $request)
    {
        try {
            if (!$request->code) {
                throw new \Exception('Authorization code not received');
            }

            Stripe::setApiKey(config('services.stripe.secret'));

            // Exchange the authorization code for an access token
            $response = OAuth::token([
                'grant_type' => 'authorization_code',
                'code' => $request->code,
            ]);

            // Get the connected account ID
            $connectedAccountId = $response->stripe_user_id;
            
            // Store or update user information
            $userData = [
                'stripe_account_id' => $connectedAccountId,
                'stripe_access_token' => $response->access_token,
                'stripe_refresh_token' => $response->refresh_token ?? null
            ];

            if (Auth::check()) {
                User::where('id', Auth::id())->update($userData);
                $user = Auth::user();
            } else {
                // Create a new user with just the Stripe info
                $user = User::create([
                    'stripe_account_id' => $connectedAccountId,
                    'stripe_access_token' => $response->access_token,
                    'stripe_refresh_token' => $response->refresh_token ?? null,
                    'email' => 'user_' . $connectedAccountId . '@example.com', // Placeholder email
                    'name' => 'Stripe User ' . $connectedAccountId,
                    'password' => bcrypt(uniqid())
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stripe account connected successfully',
                'stripe_account_id' => $connectedAccountId
                
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect Stripe account: ' . $e->getMessage()
            ], 500);
        }
    }
}
