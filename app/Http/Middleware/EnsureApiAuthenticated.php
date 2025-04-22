<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class EnsureApiAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        \Log::info('Middleware: EnsureApiAuthenticated - Start', [
            'url' => $request->url(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
        ]);

        $token = $request->bearerToken();
        $user = $request->user('sanctum');

        if (!$user) {
            \Log::info('Token invalid or user not found', [
                'token' => $token,
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse('Unauthenticated', 401);
        }

        \Log::info('User authenticated', ['user_id' => $user->id]);
        return $next($request);
    }

    /**
     * Return a standardized error response.
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $details
     * @return JsonResponse
     */
    protected function errorResponse($message, $statusCode, $details = null): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'data' => null,
            'error' => [
                'code' => $statusCode,
                'message' => $message,
                'details' => $details,
            ],
            'last_updated' => Carbon::now()->toIso8601String(),
            'message' => null,
        ], $statusCode);
    }
}