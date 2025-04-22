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

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Authenticate user and generate Sanctum token.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            \Log::info('Login failed: Validation error', [
                'errors' => $validator->errors()->toArray(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('Invalid input', 400, $validator->errors()->toArray());
        }

        \Log::info('Login attempt', [
            'username' => $request->username,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            \Log::info('Login failed: Invalid credentials', [
                'username' => $request->username,
                'ip' => $request->ip(),
            ]);
            return $this->errorResponse('Invalid login credentials', 401);
        }

        $accessToken = $user->createToken('auth_token')->plainTextToken;
        $refreshToken = $this->generateRefreshToken($user);

        \Log::info('Login success', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'refresh_token' => $refreshToken->token,
            'user' => $user,
            'expires_at' => Carbon::now()->addDays(7)->toIso8601String(),
        ], 'Login successful', 200);
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
            \Log::info('Refresh failed: Validation error', [
                'errors' => $validator->errors()->toArray(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('Invalid input', 400, $validator->errors()->toArray());
        }

        $refreshToken = RefreshToken::where('token', $request->refresh_token)->first();

        if (!$refreshToken || $refreshToken->expires_at < Carbon::now()) {
            \Log::info('Refresh failed: Invalid or expired refresh token', [
                'ip' => $request->ip(),
            ]);
            return $this->errorResponse('Invalid or expired refresh token', 401);
        }

        $user = User::find($refreshToken->user_id);

        if (!$user) {
            \Log::info('Refresh failed: User not found', [
                'ip' => $request->ip(),
            ]);
            return $this->errorResponse('User not found', 404);
        }

        // Revoke old access tokens and refresh token
        $user->tokens()->delete();
        $refreshToken->delete();

        // Generate new tokens
        $newAccessToken = $user->createToken('auth_token')->plainTextToken;
        $newRefreshToken = $this->generateRefreshToken($user);

        \Log::info('Token refreshed', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse([
            'access_token' => $newAccessToken,
            'token_type' => 'Bearer',
            'refresh_token' => $newRefreshToken->token,
            'expires_at' => Carbon::now()->addDays(7)->toIso8601String(),
        ], 'Token refreshed successfully', 200);
    }

    /**
     * Logout the authenticated user (revoke tokens).
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        RefreshToken::where('user_id', $user->id)->delete();

        \Log::info('Logout success', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse(null, 'Logged out successfully', 200);
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
            \Log::info('Change password failed: Validation error', [
                'errors' => $validator->errors()->toArray(),
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
            ]);
            return $this->errorResponse('Invalid input', 400, $validator->errors()->toArray());
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            \Log::info('Change password failed: Incorrect current password', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);
            return $this->errorResponse('Current password is incorrect', 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        \Log::info('Change password success', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse(null, 'Password changed successfully', 200);
    }

    /**
     * Validate the authenticated user's access token.
     */
    public function validateToken(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            \Log::info('Token validation failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            // Get the current access token
            $token = $user->currentAccessToken();
            if (!$token) {
                \Log::info('Token validation failed: No active token found', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                ]);
                return $this->errorResponse('No active token found', 401);
            }

            \Log::info('Token validation success', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'token_id' => $token->id,
            ]);

            return $this->successResponse([
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
                ],
            ], 'Token is valid', 200);
        } catch (\Exception $e) {
            \Log::error('Token validation failed', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Internal server error', 500, $e->getMessage());
        }
    }

    /**
     * Generate a new refresh token for the user.
     */
    protected function generateRefreshToken(User $user): RefreshToken
    {
        // Limit number of refresh tokens (e.g., max 5 per user)
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