<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\RefreshToken;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Authenticate user and get token.
     * Authenticates a user using username and password and returns an access token and refresh token.
     * Requires the X-Device-ID header.
     */
    public function login(Request $request)
    {
        // Validasi X-Device-ID
        $deviceId = $request->header('X-Device-ID');
        if (!$deviceId) {
            Log::warning('Login failed: Device ID missing', [
                'username' => $request->username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Device ID is required. Please include X-Device-ID in the request header.',
                statusCode: 400,
                details: ['device_id' => 'The X-Device-ID header is required.']
            );
        }

        // Validasi format Device ID
        if (!preg_match('/^[a-zA-Z0-9_-]{8,}$/', $deviceId)) {
            Log::warning('Login failed: Invalid Device ID format', [
                'username' => $request->username,
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid Device ID format. Device ID must be at least 8 characters and contain only alphanumeric characters, underscores, or hyphens.',
                statusCode: 400,
                details: ['device_id' => 'Invalid Device ID format.']
            );
        }

        // Rate limiting berdasarkan username dan device ID
        $rateLimitKey = 'login:' . $request->username . ':' . $deviceId;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            Log::warning('Login rate limit exceeded', [
                'username' => $request->username,
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Too many login attempts. Please try again later.',
                statusCode: 429,
                details: ['retry_after' => RateLimiter::availableIn($rateLimitKey)],
                meta: ['retry_after' => RateLimiter::availableIn($rateLimitKey)]
            );
        }

        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            RateLimiter::increment($rateLimitKey, 60);
            Log::info('Login failed: Validation error', [
                'username' => $request->username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'errors' => $validator->errors()->toArray(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid input. Please check your username and password.',
                statusCode: 400,
                details: $validator->errors()->toArray()
            );
        }

        Log::info('Login attempt', [
            'username' => $request->username,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_id' => $deviceId,
            'headers' => $request->headers->all(),
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::increment($rateLimitKey, 60);
            Log::info('Login failed: Invalid credentials', [
                'username' => $request->username,
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid login credentials.',
                statusCode: 401,
                details: null
            );
        }

        try {
            RateLimiter::clear($rateLimitKey);
            $accessToken = $user->createToken('auth_token', ['*'], null, ['device_id' => $deviceId])->plainTextToken;
            $refreshToken = $this->generateRefreshToken($user, $deviceId);

            Log::info('Login success', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: [
                    'access_token' => $accessToken,
                    'token_type' => 'Bearer',
                    'refresh_token' => $refreshToken->token,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                    ],
                    'expires_at' => Carbon::now()->addDays(7)->toIso8601String(),
                ],
                message: 'Login successful',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            RateLimiter::increment($rateLimitKey, 60);
            Log::error('Login failed: Token creation error', [
                'user_id' => $user->id ?? 'unknown',
                'username' => $request->username,
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Failed to create authentication token.',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Refresh access token.
     * Refreshes an expired access token using a valid refresh token. Requires the X-Device-ID header.
     */
    public function refresh(Request $request)
    {
        // Validasi X-Device-ID
        $deviceId = $request->header('X-Device-ID');
        if (!$deviceId) {
            Log::warning('Refresh failed: Device ID missing', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Device ID is required. Please include X-Device-ID in the request header.',
                statusCode: 400,
                details: ['device_id' => 'The X-Device-ID header is required.']
            );
        }

        // Validasi format Device ID
        if (!preg_match('/^[a-zA-Z0-9_-]{8,}$/', $deviceId)) {
            Log::warning('Refresh failed: Invalid Device ID format', [
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid Device ID format. Device ID must be at least 8 characters and contain only alphanumeric characters, underscores, or hyphens.',
                statusCode: 400,
                details: ['device_id' => 'Invalid Device ID format.']
            );
        }

        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::info('Refresh failed: Validation error', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'errors' => $validator->errors()->toArray(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid input. Please provide a valid refresh token.',
                statusCode: 400,
                details: $validator->errors()->toArray()
            );
        }

        $refreshToken = RefreshToken::where('token', $request->refresh_token)
            ->where('device_id', $deviceId)
            ->first();

        if (!$refreshToken || $refreshToken->expires_at < Carbon::now()) {
            Log::info('Refresh failed: Invalid or expired refresh token', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid or expired refresh token. Please login again.',
                statusCode: 401,
                details: null
            );
        }

        $user = User::find($refreshToken->user_id);

        if (!$user) {
            Log::info('Refresh failed: User not found', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'User not found.',
                statusCode: 404,
                details: null
            );
        }

        try {
            $user->tokens()->where('device_id', $deviceId)->delete();
            $refreshToken->delete();

            $newAccessToken = $user->createToken('auth_token', ['*'], null, ['device_id' => $deviceId])->plainTextToken;
            $newRefreshToken = $this->generateRefreshToken($user, $deviceId);

            Log::info('Token refreshed', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: [
                    'access_token' => $newAccessToken,
                    'token_type' => 'Bearer',
                    'refresh_token' => $newRefreshToken->token,
                    'expires_at' => Carbon::now()->addDays(7)->toIso8601String(),
                ],
                message: 'Token refreshed successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Refresh failed: Token creation error', [
                'user_id' => $user->id ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Failed to refresh token.',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Logout user.
     * Invalidates the current access token and refresh token for the authenticated user.
     * Requires the X-Device-ID header.
     */
    public function logout(Request $request)
    {
        // Validasi X-Device-ID
        $deviceId = $request->header('X-Device-ID');
        if (!$deviceId) {
            Log::warning('Logout failed: Device ID missing', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Device ID is required. Please include X-Device-ID in the request header.',
                statusCode: 400,
                details: ['device_id' => 'The X-Device-ID header is required.']
            );
        }

        // Validasi format Device ID
        if (!preg_match('/^[a-zA-Z0-9_-]{8,}$/', $deviceId)) {
            Log::warning('Logout failed: Invalid Device ID format', [
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid Device ID format. Device ID must be at least 8 characters and contain only alphanumeric characters, underscores, or hyphens.',
                statusCode: 400,
                details: ['device_id' => 'Invalid Device ID format.']
            );
        }

        $user = $request->user();
        if (!$user) {
            Log::warning('Logout failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Unauthorized',
                statusCode: 401,
                details: null
            );
        }

        try {
            $user->tokens()->where('device_id', $deviceId)->delete();
            RefreshToken::where('user_id', $user->id)->where('device_id', $deviceId)->delete();

            Log::info('Logout success', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: null,
                message: 'Logged out successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'user_id' => $user->id ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Failed to logout.',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Validate access token.
     * Validates an access token provided in the Authorization: Bearer <token> header.
     * Requires the X-Device-ID header for device validation.
     */
    public function validateToken(Request $request)
    {
        try {
            // Validasi X-Device-ID
            $currentDeviceId = $request->header('X-Device-ID');
            if (!$currentDeviceId) {
                Log::warning('Token validation failed: Device ID missing', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Device ID is required. Please include X-Device-ID in the request header.',
                    statusCode: 400,
                    details: ['device_id' => 'The X-Device-ID header is required.']
                );
            }

            // Validasi format Device ID
            if (!preg_match('/^[a-zA-Z0-9_-]{8,}$/', $currentDeviceId)) {
                Log::warning('Token validation failed: Invalid Device ID format', [
                    'ip' => $request->ip(),
                    'device_id' => $currentDeviceId,
                    'user_agent' => $request->userAgent(),
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Invalid Device ID format. Device ID must be at least 8 characters and contain only alphanumeric characters, underscores, or hyphens.',
                    statusCode: 400,
                    details: ['device_id' => 'Invalid Device ID format.']
                );
            }

            // Ambil token dari header Authorization
            $tokenString = $request->bearerToken();
            if (!$tokenString) {
                Log::info('Token validation failed: No token provided', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $currentDeviceId,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'No token provided. Please include a valid Bearer token.',
                    statusCode: 401,
                    details: null
                );
            }

            // Cari token di personal_access_tokens
            $token = PersonalAccessToken::findToken($tokenString);
            if (!$token) {
                Log::info('Token validation failed: Invalid token', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $currentDeviceId,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Invalid token. Please login again.',
                    statusCode: 401,
                    details: null
                );
            }

            // Periksa apakah token kedaluwarsa
            if ($token->expires_at && $token->expires_at->isPast()) {
                Log::info('Token validation failed: Token expired', [
                    'ip' => $request->ip(),
                    'token_id' => $token->id,
                    'user_agent' => $request->userAgent(),
                    'device_id' => $currentDeviceId,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Token has expired. Please login again.',
                    statusCode: 401,
                    details: null
                );
            }

            // Ambil pengguna terkait
            $user = $token->tokenable;
            if (!$user || !($user instanceof User)) {
                Log::info('Token validation failed: User not found', [
                    'ip' => $request->ip(),
                    'token_id' => $token->id,
                    'user_agent' => $request->userAgent(),
                    'device_id' => $currentDeviceId,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'User not found for this token.',
                    statusCode: 404,
                    details: null
                );
            }

            // Validasi perangkat
            $tokenDeviceId = $token->device_id;
            if ($tokenDeviceId !== $currentDeviceId) {
                Log::warning('Token validation failed: Device changed', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'current_device_id' => $currentDeviceId,
                    'token_device_id' => $tokenDeviceId,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Token invalid: Device changed. Please login again from this device.',
                    statusCode: 401,
                    details: ['device_id' => 'Device ID does not match the token.'],
                    meta: [
                        'current_device_id' => $currentDeviceId,
                        'expected_device_id' => $tokenDeviceId,
                    ]
                );
            }

            Log::info('Token validation success', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'token_id' => $token->id,
                'device_id' => $currentDeviceId,
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: [
                    'is_valid' => true,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                    ],
                    'token' => [
                        'id' => $token->id,
                        'expires_at' => $token->expires_at ? $token->expires_at->toIso8601String() : null,
                        'last_used_at' => $token->last_used_at ? $token->last_used_at->toIso8601String() : null,
                        'device_id' => $tokenDeviceId,
                    ],
                ],
                message: 'Token is valid',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Token validation failed: Internal error', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $request->header('X-Device-ID', 'unknown'),
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Failed to validate token due to an internal error. Please try again.',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Generate a new refresh token for the user.
     */
    protected function generateRefreshToken(User $user, string $deviceId): RefreshToken
    {
        // Limit number of refresh tokens (max 5 per user and device)
        $existingTokens = RefreshToken::where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->count();
        if ($existingTokens >= 5) {
            RefreshToken::where('user_id', $user->id)
                ->where('device_id', $deviceId)
                ->oldest()
                ->delete();
        }

        return RefreshToken::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'device_id' => $deviceId,
            'expires_at' => Carbon::now()->addDays(30),
        ]);
    }
}