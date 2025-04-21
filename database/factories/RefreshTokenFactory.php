<?php

namespace Database\Factories;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RefreshTokenFactory extends Factory
{
    protected $model = RefreshToken::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'token' => Str::random(64),
            'expires_at' => Carbon::now()->addDays(30),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}