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
            \Log::info('Login failed: Validation error', ['errors' => $validator->errors()]);
            return $this->errorResponse('Invalid input', 400, $validator->errors());
        }

        \Log::info('Login attempt', ['username' => $request->username]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            \Log::info('Login failed: Invalid credentials');
            return $this->errorResponse('Invalid login credentials', 401);
        }

        $accessToken = $user->createToken('auth_token')->plainTextToken;
        $refreshToken = $this->generateRefreshToken($user);

        \Log::info('Login success', ['user_id' => $user->id]);

        return $this->successResponse([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'refresh_token' => $refreshToken->token,
            'user' => $user,
            'expires_at' => Carbon::now()->addDays(7)->toIso8601String(),
        ], 'Login successful');
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
            \Log::info('Refresh failed: Validation error', ['errors' => $validator->errors()]);
            return $this->errorResponse('Invalid input', 400, $validator->errors());
        }

        $refreshToken = RefreshToken::where('token', $request->refresh_token)->first();

        if (!$refreshToken || $refreshToken->expires_at < Carbon::now()) {
            \Log::info('Refresh failed: Invalid or expired refresh token');
            return $this->errorResponse('Invalid or expired refresh token', 401);
        }

        $user = $user = User::find($refreshToken->user_id);

        if (!$user) {
            \Log::info('Refresh failed: User not found');
            return $this->errorResponse('User not found', 404);
        }

        // Revoke old access tokens
        $user->tokens()->delete();

        // Generate new access token
        $newAccessToken = $user->createToken('auth_token')->plainTextToken;

        // Optionally, extend refresh token expiration or generate new one
        $newRefreshToken = $this->generateRefreshToken($user, $refreshToken);

        \Log::info('Token refreshed', ['user_id' => $user->id]);

        return $this->successResponse([
            'access_token' => $newAccessToken,
            'token_type' => 'Bearer',
            'refresh_token' => $newRefreshToken->token,
            'expires_at' => Carbon::now()->addDays(7)->toIso8601String(),
        ], 'Token refreshed successfully');
    }

    /**
     * Logout the authenticated user (revoke tokens).
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        RefreshToken::where('user_id', $user->id)->delete();
        \Log::info('Logout success', ['user_id' => $user->id]);
        return $this->successResponse(null, 'Logged out successfully');
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
            \Log::info('Change password failed: Validation error', ['errors' => $validator->errors()]);
            return $this->errorResponse('Invalid input', 400, $validator->errors());
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            \Log::info('Change password failed: Incorrect current password', ['user_id' => $user->id]);
            return $this->errorResponse('Current password is incorrect', 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        \Log::info('Change password success', ['user_id' => $user->id]);
        return $this->successResponse(null, 'Password changed successfully');
    }

    /**
     * Generate or update a refresh token for the user.
     */
    protected function generateRefreshToken(User $user, RefreshToken $existingToken = null): RefreshToken
    {
        if ($existingToken) {
            // Extend expiration
            $existingToken->expires_at = Carbon::now()->addDays(30);
            $existingToken->save();
            return $existingToken;
        }

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