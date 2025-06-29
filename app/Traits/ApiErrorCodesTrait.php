<?php

namespace App\Traits;

trait ApiErrorCodesTrait
{
    protected function getErrorCode($message, $statusCode)
    {
        $errorCodes = [
            // Authentication errors
            'User not authenticated' => 'UNAUTHENTICATED',
            'Invalid credentials' => 'INVALID_CREDENTIALS',
            'Token expired' => 'TOKEN_EXPIRED',
            'Invalid token' => 'INVALID_TOKEN',
            
            // Validation errors
            'Invalid input' => 'VALIDATION_ERROR',
            'Required field missing' => 'MISSING_REQUIRED_FIELD',
            
            // Resource errors
            'User data not found' => 'USER_DATA_NOT_FOUND',
            'Face model not found' => 'FACE_MODEL_NOT_FOUND',
            'Attendance not found' => 'ATTENDANCE_NOT_FOUND',
            
            // Permission errors
            'Unauthorized' => 'UNAUTHORIZED_ACCESS',
            'Access denied' => 'ACCESS_DENIED',
            
            // Business logic errors
            'Already checked in' => 'DUPLICATE_CHECKIN',
            'No active face model' => 'NO_ACTIVE_FACE_MODEL',
        ];

        foreach ($errorCodes as $messagePattern => $code) {
            if (stripos($message, $messagePattern) !== false) {
                return $code;
            }
        }

        // Default error codes based on status code
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