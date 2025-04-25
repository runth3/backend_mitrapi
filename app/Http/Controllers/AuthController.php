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

/**
 * @OA\Tag(
 * name="Auth",
 * description="Operations related to user authentication"
 * )
 */
class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Post(
     * path="/api/auth/login",
     * summary="Authenticate user and get token",
     * description="Authenticates a user using username and password and returns an access token and refresh token. Requires the X-Device-ID header.",
     * tags={"Auth"},
     * @OA\RequestBody(
     * required=true,
     * description="User credentials",
     * @OA\JsonContent(
     * required={"username", "password"},
     * @OA\Property(property="username", type="string", example="johndoe"),
     * @OA\Property(property="password", type="string", format="password", example="secret")
     * )
     * ),
     * @OA\Header(
     * header="X-Device-ID",
     * required=true,
     * description="Unique identifier of the device making the request (minimum 8 alphanumeric characters, underscores, or hyphens)"
     * ),
     * @OA\Response(response=200, description="Successful authentication", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object", @OA\Property(property="access_token", type="string", example="eyJ..."), @OA\Property(property="token_type", type="string", example="Bearer"), @OA\Property(property="refresh_token", type="string", example="eyJ..."), @OA\Property(property="user", type="object", @OA\Property(property="id", type="integer", example=1), @OA\Property(property="username", type="string", example="johndoe"), @OA\Property(property="email", type="string", example="johndoe@example.com")), @OA\Property(property="expires_at", type="string", format="date-time", example="2025-05-02T10:00:00+00:00")), @OA\Property(property="error", type="null"), @OA\Property(property="meta", type="null"), @OA\Property(property="last_updated", type="string", format="date-time", example="2025-04-25T15:25:00+00:00"), @OA\Property(property="message", type="string", example="Login successful"))),
     * @OA\Response(response=400, description="Invalid input or missing Device ID", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="error"), @OA\Property(property="data", type="null"), @OA\Property(property="error", type="object", @OA\Property(property="code", type="integer", example=400), @OA\Property(property="message", type="string", example="Invalid input. Please check your username and password."), @OA\Property(property="details", type="object", @OA\Property(property="username", type="array", @OA\Items(type="string")), @OA\Property(property="password", type="array", @OA\Items(type="string")), @OA\Property(property="device_id", type="array", @OA\Items(type="string")))), @OA\Property(property="meta", type="null"), @OA\Property(property="last_updated", type="string", format="date-time", example="2025-04-25T15:25:00+00:00"), @OA\Property(property="message", type="string", example="Invalid input. Please check your username and password."))),
     * @OA\Response(response=401, description="Invalid login credentials", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="error"), @OA\Property(property="data", type="null"), @OA\Property(property="error", type="object", @OA\Property(property="code", type="integer", example=401), @OA\Property(property="message", type="string", example="Invalid login credentials."), @OA\Property(property="details", type="null")), @OA\Property(property="meta", type="null"), @OA\Property(property="last_updated", type="string", format="date-time", example="2025-04-25T15:25:00+00:00"), @OA\Property(property="message", type="string", example="Invalid login credentials."))),
     * @OA\Response(response=429, description="Too many login attempts", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="error"), @OA\Property(property="data", type="null"), @OA\Property(property="error", type="object", @OA\Property(property="code", type="integer", example=429), @OA\Property(property="message", type="string", example="Too many login attempts. Please try again later."), @OA\Property(property="details", type="object", @OA\Property(property="retry_after", type="integer", example=60))), @OA\Property(property="meta", type="object", @OA\Property(property="retry_after", type="integer", example=60)), @OA\Property(property="last_updated", type="string", format="date-time", example="2025-04-25T15:25:00+00:00"), @OA\Property(property="message", type="string", example="Too many login attempts. Please try again later.")))
     * )
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
                details: ['retry_after' => RateLimiter::availableIn($rateLimitKey)]
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
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid login credentials.',
                statusCode: 401,
                details: null
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
    }

    /**
     * @OA\Post(
     * path="/api/auth/refresh-token",
     * summary="Refresh access token",
     * description="Refreshes an expired access token using a valid refresh token. Requires the X-Device-ID header.",
     * tags={"Auth"},
     * @OA\RequestBody(
     * required=true,
     * description="Refresh token",
     * @OA\JsonContent(
     * required={"refresh_token"},
     * @OA\Property(property="refresh_token", type="string", example="eyJ...")
     * )
     * ),
     * @OA\Header(
     * header="X-Device-ID",
     * required=true,
     * description="Unique identifier of the device making the request (minimum 8 alphanumeric characters, underscores, or hyphens)"
     * ),
     * @OA\Response(response=200, description="Token refreshed successfully", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object", @OA\Property(property="access_token", type="string", example="eyJ..."), @OA\Property(property="token_type", type="string", example="Bearer"), @OA\Property(property="refresh_token", type="string", example="eyJ..."), @OA\Property(property="expires_at", type="string", format="date-time", example="2025-05-02T10:00:00+00:00")), @OA\Property(property="error", type="null"), @OA\Property(property="meta", type="null"), @OA\Property(property="last_updated", type="string", format="date-time", example="2025-04-25T15:25:00+00:00"), @OA\Property(property="message", type="string", example="Token refreshed successfully"))),
     * @OA\Response(response=400, description="Invalid input or missing Device ID", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="error"), @OA\Property(property="data", type="null"), @OA\Property(property="error", type="object", @OA\Property(property="code", type="integer", example=400), @OA\Property(property="message", type="string", example="Invalid input. Please provide a valid refresh token."), @OA\Property(property="details", type="object", @OA\Property(property="refresh_token", type="array", @OA\Items(type="string")), @OA\Property(property="device_id", type="array", @OA\Items(type="string")))), @OA\Property(property="meta", type="null"), @OA\Property(property="last_updated", type="string", format="date-time", example="2025-04-25T15:25:00+00:00"), @OA\Property(property="message", type="string", example="Invalid input. Please provide a valid refresh token."))),
     * @OA\Response(response=401, description="Invalid or expired refresh token", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="error"), @OA\Property(property="data", type="null"), @OA\Property(property="error", type="object", @OA\Property(property="code", type="integer", example=401), @OA\Property(property="message", type="string", example="Invalid or expired refresh token. Please login again."), @OA\Property(property="details", type="null")), @OA\Property(property="meta", type="null"), @OA\Property(property="last_updated", type="string", format="date-time", example="2025-04-25T15:25:00+00:00"), @OA\Property(property="message", type="string", example="Invalid or expired refresh token. Please login again."))),
     * @OA\Response(response=404, description="User not found", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="error"), @OA\Property(property="data", type="null"), @OA\Property(property="error", type="object", @OA\Property(property="code", type="integer", example=404), @OA\Property(property="message", type="string", example="User not found."), @OA\Property(property="details", type="null")), @OA\Property(property="meta", type="null"), @OA\Property(property="last_updated", type="string", format="date-time", example="2025-04-25T15:25:00+00:00"), @OA\Property(property="message", type="string", example="User not found.")))
     * )
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
                'errors' => $validator->errors()->toArray(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid input. Please provide a valid refresh token.',
                statusCode: 400,
                details: $validator->errors()->toArray()
            );
        }

        $refreshToken = RefreshToken::where('token', $request->refresh_token)->first();

        if (!$refreshToken || $refreshToken->expires_at < Carbon::now()) {
            Log::info('Refresh failed: Invalid or expired refresh token', [
                'ip' => $request->ip(),
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
                'device_id' => $deviceId,
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'User not found.',
                statusCode: 404,
                details: null
            );
        }

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
    }

    /**
     * @OA\Post(
     * path="/api/auth/logout",
     * summary="Logout user",
     * description="Invalidates the current access token and refresh token for the authenticated user.",
     * tags={"Auth"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(response=200, description="Logged out successfully", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="null"), @OA\Property(property="error", type="null"), @OA\Property(property="meta", type="null"), @OA\Property(property="last_updated", type="string", format="date-time", example="2025-04-25T15:25:00+00:00"), @OA\Property(property="message", type="string", example="Logged out successfully"))),
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="error"), @OA\Property(property="data", type="null"), @OA\Property(property="error", type="object", @OA\Property(property="code", type="integer", example=401), @OA\Property(property="message", type="string", example="Unauthorized"), @OA\Property(property="details", type="null")), @OA\Property(property="meta", type="null"), @OA\Property(property="last_updated", type="string", format="date-time", example="2025-04-25T15:25:00+00:00"), @OA\Property(property="message", type="string", example="Unauthorized")))
     * )
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        RefreshToken::where('user_id', $user->id)->delete();

        Log::info('Logout success', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'headers' => $request->headers->all(),
        ]);

        return $this->successResponse(
            data: null,
            message: 'Logged out successfully',
            meta: null,
            statusCode: 200
        );
    }
    /**
 * @OA\Post(
 *     path="/api/auth/validate-token",
 *     summary="Validate access token",
 *     description="Validates an access token provided in the Authorization: Bearer <token> header. Requires the X-Device-ID header for device validation.",
 *     tags={"Auth"},
 *     @OA\Header(
 *         header="Authorization",
 *         required=true,
 *         description="Bearer token for authentication"
 *     ),
 *     @OA\Header(
 *         header="X-Device-ID",
 *         required=true,
 *         description="Unique identifier of the device making the request (minimum 8 alphanumeric characters, underscores, or hyphens)"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Token is valid",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="is_valid", type="boolean", example=true),
 *                 @OA\Property(
 *                     property="user",
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="username", type="string", example="johndoe"),
 *                     @OA\Property(property="email", type="string", example="johndoe@example.com")
 *                 ),
 *                 @OA\Property(
 *                     property="token",
 *                     type="object",
 *                     @OA\Property(property="id", type="string", example="abcdef123456"),
 *                     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true, example="2025-05-02T10:00:00+00:00"),
 *                     @OA\Property(property="last_used_at", type="string", format="date-time", nullable=true, example="2025-04-25T15:20:00+00:00"),
 *                     @OA\Property(property="device_id", type="string", nullable=true, example="device123")
 *                 )
 *             ),
 *             @OA\Property(property="error", type="null"),
 *             @OA\Property(property="meta", type="null"),
 *             @OA\Property(property="last_updated", type="string", format="date-time", example="2025-04-25T15:25:00+00:00"),
 *             @OA\Property(property="message", type="string", example="Token is valid")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Device ID is required or invalid",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="data", type="null"),
 *             @OA\Property(
 *                 property="error",
 *                 type="object",
 *                 @OA\Property(property="code", type="integer", example=400),
 *                 @OA\Property(property="message", type="string", example="Device ID is required. Please include X-Device-ID in the request header."),
 *                 @OA\Property(
 *                     property="details",
 *                     type="object",
 *                     @OA\Property(property="device_id", type="array", @OA\Items(type="string"))
 *                 )
 *             ),
 *             @OA\Property(property="meta", type="null"),
 *             @OA\Property(property="last_updated", type="string", format="date-time", example="2025-04-25T15:25:00+00:00"),
 *             @OA\Property(property="message", type="string", example="Device ID is required. Please include X-Device-ID in the request header.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized - No token, invalid token, or device mismatch",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="data", type="null"),
 *             @OA\Property(
 *                 property="error",
 *                 type="object",
 *                 @OA\Property(property="code", type="integer", example=401),
 *                 @OA\Property(property="message", type="string", example="No token provided. Please include a valid Bearer token."),
 *                 @OA\Property(property="details", type="null")
 *             ),
 *             @OA\Property(
 *                 property="meta",
 *                 type="object",
 *                 @OA\Property(property="current_device_id", type="string", nullable=true, example="deviceABC"),
 *                 @OA\Property(property="expected_device_id", type="string", nullable=true, example="deviceXYZ")
 *             ),
 *             @OA\Property(property="last_updated", type="string", format="date-time", example="2025-04-25T15:25:00+00:00"),
 *             @OA\Property(property="message", type="string", example="No token provided. Please include a valid Bearer token.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="User not found for this token",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="data", type="null"),
 *             @OA\Property(
 *                 property="error",
 *                 type="object",
 *                 @OA\Property(property="code", type="integer", example=404),
 *                 @OA\Property(property="message", type="string", example="User not found for this token."),
 *                 @OA\Property(property="details", type="null")
 *             ),
 *             @OA\Property(property="meta", type="null"),
 *             @OA\Property(property="last_updated", type="string", format="date-time", example="2025-04-25T15:25:00+00:00"),
 *             @OA\Property(property="message", type="string", example="User not found for this token.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error during token validation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="data", type="null"),
 *             @OA\Property(
 *                 property="error",
 *                 type="object",
 *                 @OA\Property(property="code", type="integer", example=500),
 *                 @OA\Property(property="message", type="string", example="Failed to validate token due to an internal error. Please try again."),
 *                 @OA\Property(
 *                     property="details",
 *                     type="object",
 *                     @OA\Property(property="exception", type="string", example="...")
 *                 )
 *             ),
 *             @OA\Property(property="meta", type="null"),
 *             @OA\Property(property="last_updated", type="string", format="date-time", example="2025-04-25T15:25:00+00:00"),
 *             @OA\Property(property="message", type="string", example="Failed to validate token due to an internal error. Please try again.")
 *         )
 *     )
 * )
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
            $tokenDeviceId = $token->device_id ?? null;
            if ($tokenDeviceId && $tokenDeviceId !== $currentDeviceId) {
                Log::warning('Token validation failed: Device changed', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
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
                'ip' => $request->ip(),
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