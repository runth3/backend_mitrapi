<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EnsureApiAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        \Log::info('Middleware: EnsureApiAuthenticated - Start', [
            'url' => $request->url(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        $token = $request->bearerToken();
        \Log::info('Checking token', ['token' => $token]);

        // Check user using Sanctum
        $user = $request->user('sanctum'); // Retrieve user from Sanctum
        if (!$user) {
            \Log::info('Token invalid or user not found', [
                'token' => $token,
                'headers' => $request->headers->all(),
            ]);
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        \Log::info('User authenticated', ['user_id' => $user->id, 'user' => $user]);

        return $next($request);
    }
}