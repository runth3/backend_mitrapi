<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        \Log::info('Login attempt', ['username' => $request->username]);

        // Cari user berdasarkan username
        $user = User::where('username', $request->username)->first();

        // Verifikasi kredensial secara manual
        if (!$user || !Hash::check($request->password, $user->password)) {
            \Log::info('Login failed: Invalid credentials');
            return response()->json(['message' => 'Invalid login credentials'], 401);
        }

        // Buat token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;
        \Log::info('Login success', ['user_id' => $user->id, 'token' => $token]);

        // Cek apakah token tersimpan (opsional, untuk debugging)
        $savedToken = \DB::table('personal_access_tokens')
            ->where('tokenable_id', $user->id)
            ->where('name', 'auth_token')
            ->latest()
            ->first();
        \Log::info('Token in DB', ['saved_token' => $savedToken ? $savedToken->token : 'not found']);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
    /**
     * Logout the authenticated user (revoke the token).
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Change the authenticated user's password.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => [
                'required',
                'string',
                'min:8', // Minimum 8 characters
                'regex:/[a-z]/', // At least one lowercase letter
                'regex:/[A-Z]/', // At least one uppercase letter
                'regex:/[0-9]/', // At least one digit
                'regex:/[@$!%*?&]/', // At least one special character
                'not_regex:/123456|password|qwerty|abcdef|letmein/', // Disallow common passwords
                'confirmed', // Must match new_password_confirmation
            ],
        ]);

        $user = $request->user();

        // Verify the current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 400);
        }

        // Update the user's password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password changed successfully']);
    }
}