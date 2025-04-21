<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

trait ApiResponseTrait
{
    /**
     * Return a standardized success response.
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public function successResponse($data = null, $message = null, $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $data,
            'error' => null,
            'last_updated' => Carbon::now()->toIso8601String(),
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Return a standardized error response.
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $details
     * @return JsonResponse
     */
    public function errorResponse($message, $statusCode, $details = null): JsonResponse
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