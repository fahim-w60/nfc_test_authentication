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
            $response = OAuth::token([
                'grant_type' => 'authorization_code',
                'code' => $request->code,
            ]);

            
            $connectedAccountId = $response->stripe_user_id;
            
            // Get account details from Stripe
            $account = Account::retrieve($connectedAccountId);
            
            $user = User::updateOrCreate(
                ['stripe_account_id' => $connectedAccountId],
                [
                    'stripe_access_token' => $response->access_token,
                    'stripe_refresh_token' => $response->refresh_token ?? null,
                    'email' => $account->email ?? 'user_' . $connectedAccountId . '@example.com',
                    'name' => $account->business_profile->name ?? 'Stripe User ' . $connectedAccountId,
                    'password' => bcrypt(uniqid()),
                    'stripe_access_code' =>$request->code,
                ]
            );

            // Generate JWT token
            $token = Auth::login($user);

            return response()->json([
                'success' => true,
                'message' => 'Stripe account connected successfully',
                'data' => [
                    'value' => 1,
                    'stripe_account_id' => $connectedAccountId,
                    'stripe_access_token' => $response->access_token,
                    'stripe_access_code' =>$user->stripe_access_code,
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->name
                    ],
                    'authorization' => [
                        'token' => $token,
                        'type' => 'bearer'
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect Stripe account: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exchangeCode(Request $request)
    {
        $code = $request->code;
        $user = User::where('stripe_access_code', $code)->first();
        if($user)
        {
            $token = Auth::login($user);
            return response()->json([
                'success' => true,
                'message' => 'User found',
                'user' => $user,
                'token' => $token,
            ]);
        }
    }

}
