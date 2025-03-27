<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EnsureApiAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], Response::HTTP_UNAUTHORIZED); // 401
        }

        return $next($request);
    }
}