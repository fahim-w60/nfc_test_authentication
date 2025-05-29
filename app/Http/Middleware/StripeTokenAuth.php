<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

class StripeTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        try {
            if (!$token = $request->bearerToken()) {
                return response()->json(['error' => 'No token provided'], 401);
            }

            // Authenticate user with JWT
            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();
            
            if (!$user) {
                return response()->json(['error' => 'User not found'], 401);
            }

            // Set the authenticated user
            Auth::setUser($user);

            return $next($request);

        } 
        catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token has expired'], 401);
        } 
        
        catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Token is invalid'], 401);
        } 
        
        catch (\Exception $e) {
            return response()->json(['error' => 'Could not authenticate user: ' . $e->getMessage()], 401);
        }
    }
}
