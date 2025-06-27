<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Stripe\Stripe;
use Stripe\Terminal\ConnectionToken;

class TerminalController extends Controller
{
    public function getConnectionToken(Request $request)
    {
        // Set your Stripe secret key
        Stripe::setApiKey(config('services.stripe.secret'));

        //dd(config('services.stripe.secret'));

        // Create a new connection token
        $connectionToken = ConnectionToken::create();

        //dd($connectionToken->secret);

        // Return the secret
        return response()->json([
            'secret' => $connectionToken->secret
        ]);
    }
}
