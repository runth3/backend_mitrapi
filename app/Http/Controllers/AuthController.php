<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        \Log::info('Login attempt', ['username' => $request->username]);

        if (!Auth::attempt($request->only('username', 'password'))) {
            \Log::info('Login failed: Invalid credentials');
            return response()->json(['message' => 'Invalid login credentials'], 401);
        }

        $user = User::where('username', $request->username)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;
        \Log::info('Login success', ['user_id' => $user->id, 'token' => $token]);

        // Cek apakah token tersimpan
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
}