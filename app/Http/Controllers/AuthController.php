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
     * Authenticate user and generate Sanctum token.
     */
    public function login(Request $request)
    {
        $deviceId = $request->header('X-Device-ID', 'unknown');
        $rateLimitKey = 'login:' . $request->username . ':' . $deviceId;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            Log::warning('Login rate limit exceeded', [
                'username' => $request->username,
                'ip' => $request->ip(),
                'device_id' => $deviceId,
            ]);
            return $this->errorResponse(
                message: 'Too many login attempts. Please try again later.',
                statusCode: 429,
                errorDetails: null,
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
                'errors' => $validator->errors()->toArray(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
            ]);
            return $this->errorResponse(
                message: 'Invalid input. Please check your username and password.',
                statusCode: 400,
                errorDetails: $validator->errors()->toArray()
            );
        }

        Log::info('Login attempt', [
            'username' => $request->username,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_id' => $deviceId,
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::increment($rateLimitKey, 60);
            Log::info('Login failed: Invalid credentials', [
                'username' => $request->username,
                'ip' => $request->ip(),
                'device_id' => $deviceId,
            ]);
            return $this->errorResponse(
                message: 'Invalid login credentials.',
                statusCode: 401
            );
        }

        RateLimiter::clear($rateLimitKey);
        $token = $user->createToken('auth_token');
        $token->accessToken->device_id = $deviceId;
        $token->accessToken->save();
        $accessToken = $token->plainTextToken;
        $refreshToken = $this->generateRefreshToken($user);

        Log::info('Login success', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'device_id' => $deviceId,
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
    }

    /**
     * Refresh access token using refresh token.
     */
    public function refresh(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::info('Refresh failed: Validation error', [
                'errors' => $validator->errors()->toArray(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse(
                message: 'Invalid input. Please provide a valid refresh token.',
                statusCode: 400,
                errorDetails: $validator->errors()->toArray()
            );
        }

        $refreshToken = RefreshToken::where('token', $request->refresh_token)->first();

        if (!$refreshToken || $refreshToken->expires_at < Carbon::now()) {
            Log::info('Refresh failed: Invalid or expired refresh token', [
                'ip' => $request->ip(),
            ]);
            return $this->errorResponse(
                message: 'Invalid or expired refresh token. Please login again.',
                statusCode: 401
            );
        }

        $user = User::find($refreshToken->user_id);

        if (!$user) {
            Log::info('Refresh failed: User not found', [
                'ip' => $request->ip(),
            ]);
            return $this->errorResponse(
                message: 'User not found.',
                statusCode: 404
            );
        }

        $deviceId = $request->header('X-Device-ID', 'unknown');
        $user->tokens()->delete();
        $refreshToken->delete();

        $token = $user->createToken('auth_token');
        $token->accessToken->device_id = $deviceId;
        $token->accessToken->save();
        $newAccessToken = $token->plainTextToken;
        $newRefreshToken = $this->generateRefreshToken($user);

        Log::info('Token refreshed', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'device_id' => $deviceId,
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
    }

    /**
     * Logout the authenticated user (revoke tokens).
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        RefreshToken::where('user_id', $user->id)->delete();

        Log::info('Logout success', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse(
            data: null,
            message: 'Logged out successfully',
            meta: null,
            statusCode: 200
        );
    }

    /**
     * Change the authenticated user's password.
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*?&]/',
                'not_regex:/123456|password|qwerty|abcdef|letmein/',
                'confirmed',
            ],
        ]);

        if ($validator->fails()) {
            Log::info('Change password failed: Validation error', [
                'errors' => $validator->errors()->toArray(),
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
            ]);
            return $this->errorResponse(
                message: 'Invalid input. Please check your password requirements.',
                statusCode: 400,
                errorDetails: $validator->errors()->toArray()
            );
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            Log::info('Change password failed: Incorrect current password', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);
            return $this->errorResponse(
                message: 'Current password is incorrect.',
                statusCode: 400
            );
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        Log::info('Change password success', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse(
            data: null,
            message: 'Password changed successfully',
            meta: null,
            statusCode: 200
        );
    }

    /**
     * Validate an access token (public route).
     */
    public function validateToken(Request $request)
    {
        try {
            // Ambil token dari header Authorization
            $tokenString = $request->bearerToken();
            if (!$tokenString) {
                Log::info('Token validation failed: No token provided', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                return $this->errorResponse(
                    message: 'No token provided. Please include a valid Bearer token.',
                    statusCode: 401
                );
            }

            // Cari token di personal_access_tokens
            $token = PersonalAccessToken::findToken($tokenString);
            if (!$token) {
                Log::info('Token validation failed: Invalid token', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                return $this->errorResponse(
                    message: 'Invalid token. Please login again.',
                    statusCode: 401
                );
            }

            // Periksa apakah token kedaluwarsa
            if ($token->expires_at && $token->expires_at->isPast()) {
                Log::info('Token validation failed: Token expired', [
                    'ip' => $request->ip(),
                    'token_id' => $token->id,
                ]);
                return $this->errorResponse(
                    message: 'Token has expired. Please login again.',
                    statusCode: 401
                );
            }

            // Ambil pengguna terkait
            $user = $token->tokenable;
            if (!$user || !($user instanceof User)) {
                Log::info('Token validation failed: User not found', [
                    'ip' => $request->ip(),
                    'token_id' => $token->id,
                ]);
                return $this->errorResponse(
                    message: 'User not found for this token.',
                    statusCode: 404
                );
            }

            // Validasi perangkat (jika device_id ada di database)
            $currentDeviceId = $request->header('X-Device-ID', 'unknown');
            $tokenDeviceId = $token->device_id ?? null;

            if ($tokenDeviceId && $tokenDeviceId !== $currentDeviceId) {
                Log::warning('Token validation failed: Device changed', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'current_device_id' => $currentDeviceId,
                    'token_device_id' => $tokenDeviceId,
                ]);
                return $this->errorResponse(
                    message: 'Token invalid: Device changed. Please login again from this device.',
                    statusCode: 401,
                    errorDetails: 'device_changed',
                    meta: [
                        'current_device_id' => $currentDeviceId,
                        'expected_device_id' => $tokenDeviceId,
                    ]
                );
            }

            Log::info('Token validation success', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'token_id' => $token->id,
                'device_id' => $currentDeviceId,
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
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse(
                message: 'Failed to validate token due to an internal error. Please try again.',
                statusCode: 500,
                errorDetails: $e->getMessage()
            );
        }
    }

    /**
     * Generate a new refresh token for the user.
     */
    protected function generateRefreshToken(User $user): RefreshToken
    {
        $existingTokens = RefreshToken::where('user_id', $user->id)->count();
        if ($existingTokens >= 5) {
            RefreshToken::where('user_id', $user->id)->oldest()->delete();
        }

        return RefreshToken::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => Carbon::now()->addDays(30),
        ]);
    }
}