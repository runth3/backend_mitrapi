<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

trait ApiResponseTrait
{
    public function successResponse($data = null, $message = null, $meta = null, $statusCode = 200): JsonResponse
    {
        $response = [
            'status' => 'success',
            'data' => $data,
            'error' => null,
            'meta' => $meta,
            'last_updated' => Carbon::now()->toIso8601String(),
            'message' => $message,
        ];

        if ($meta === null) {
            unset($response['meta']);
        }

        return response()->json($response, $statusCode);
    }

    public function errorResponse($message, $statusCode, $details = null, $meta = null): JsonResponse
    {
        $response = [
            'status' => 'error',
            'data' => null,
            'error' => [
                'code' => $statusCode,
                'error_code' => $this->getErrorCode($message, $statusCode),
                'message' => $message,
                'details' => $details,
            ],
            'meta' => $meta,
            'last_updated' => Carbon::now()->toIso8601String(),
            'message' => null,
        ];

        if ($meta === null) {
            unset($response['meta']);
        }

        return response()->json($response, $statusCode);
    }

    protected function getErrorCode($message, $statusCode)
    {
        $errorCodes = [
            'User not authenticated' => 'UNAUTHENTICATED',
            'Invalid credentials' => 'INVALID_CREDENTIALS',
            'Token expired' => 'TOKEN_EXPIRED',
            'Invalid input' => 'VALIDATION_ERROR',
            'User data not found' => 'USER_DATA_NOT_FOUND',
            'Already checked in' => 'DUPLICATE_CHECKIN',
            'Unauthorized' => 'UNAUTHORIZED_ACCESS',
        ];

        foreach ($errorCodes as $messagePattern => $code) {
            if (stripos($message, $messagePattern) !== false) {
                return $code;
            }
        }

        return match($statusCode) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            422 => 'UNPROCESSABLE_ENTITY',
            500 => 'INTERNAL_SERVER_ERROR',
            default => 'UNKNOWN_ERROR'
        };
    }
}