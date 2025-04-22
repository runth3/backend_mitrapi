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
}