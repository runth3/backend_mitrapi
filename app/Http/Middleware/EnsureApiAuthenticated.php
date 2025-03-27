<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EnsureApiAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        \Log::info('Checking token', ['token' => $token]);

        // Cek user pake Sanctum bawaan
        $user = $request->user('sanctum'); // Langsung ambil dari request
        if (!$user) {
            \Log::info('Token invalid or user not found');
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        \Log::info('User authenticated', ['user_id' => $user->id]);
        return $next($request);
    }
}