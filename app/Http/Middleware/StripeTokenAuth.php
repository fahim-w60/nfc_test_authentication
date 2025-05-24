<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StripeTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        try {
            if (!$token = $request->bearerToken()) {
                return response()->json(['error' => 'No token provided'], 401);
            }

            if (!Auth::guard()->check()) {
                return response()->json(['error' => 'Invalid token'], 401);
            }

            return $next($request);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }
    }
}
