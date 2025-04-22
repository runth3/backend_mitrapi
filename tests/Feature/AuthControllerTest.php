<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\RefreshToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_login_success()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'testuser',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => ['access_token', 'token_type', 'refresh_token', 'expires_at'],
                'error',
                'last_updated',
                'message',
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Login successful',
            ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'auth_token',
        ]);

        $this->assertDatabaseHas('refresh_tokens', [
            'user_id' => $user->id,
        ]);
    }

    public function test_login_invalid_credentials()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure([
                'status',
                'data',
                'error' => ['code', 'message'],
                'last_updated',
                'message',
            ])
            ->assertJson([
                'status' => 'error',
                'error' => [
                    'code' => 401,
                    'message' => 'Invalid login credentials',
                ],
            ]);
    }

    public function test_login_validation_error()
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => '',
            'password' => '',
        ]);

        $response->assertStatus(400)
            ->assertJsonStructure([
                'status',
                'data',
                'error' => ['code', 'message', 'details'],
                'last_updated',
                'message',
            ])
            ->assertJson([
                'status' => 'error',
                'error' => [
                    'code' => 400,
                    'message' => 'Invalid input',
                ],
            ])
            ->assertJsonPath('error.details.username', ['The username field is required.'])
            ->assertJsonPath('error.details.password', ['The password field is required.']);
    }

    public function test_refresh_success()
    {
        $user = User::factory()->create();
        $refreshToken = RefreshToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => ['access_token', 'token_type', 'refresh_token', 'expires_at'],
                'error',
                'last_updated',
                'message',
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Token refreshed successfully',
            ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'auth_token',
        ]);
    }

    public function test_refresh_invalid_token()
    {
        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => 'invalid_token',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'error' => [
                    'code' => 401,
                    'message' => 'Invalid or expired refresh token',
                ],
            ]);
    }

    public function test_refresh_expired_token()
    {
        $user = User::factory()->create();
        $refreshToken = RefreshToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken->token,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'error' => [
                    'code' => 401,
                    'message' => 'Invalid or expired refresh token',
                ],
            ]);
    }

    public function test_logout_success()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Logged out successfully',
                'data' => null,
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);

        $this->assertDatabaseMissing('refresh_tokens', [
            'user_id' => $user->id,
        ]);
    }

    public function test_logout_unauthenticated()
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'error' => [
                    'code' => 401,
                    'message' => 'Unauthenticated',
                ],
            ]);
    }

    public function test_change_password_success()
    {
        $user = User::factory()->create([
            'password' => bcrypt('OldPassword123!'),
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/changepassword', [
            'current_password' => 'OldPassword123!',
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Password changed successfully',
                'data' => null,
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
    }

    public function test_change_password_invalid_current_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('OldPassword123!'),
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/changepassword', [
            'current_password' => 'WrongPassword123!',
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'error' => [
                    'code' => 400,
                    'message' => 'Current password is incorrect',
                ],
            ]);
    }

    public function test_change_password_validation_error()
    {
        $user = User::factory()->create([
            'password' => bcrypt('OldPassword123!'),
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/changepassword', [
            'current_password' => 'OldPassword123!',
            'new_password' => 'weak',
            'new_password_confirmation' => 'weak',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'error' => [
                    'code' => 400,
                    'message' => 'Invalid input',
                ],
            ])
            ->assertJsonPath('error.details.new_password', [
                'The new password field must be at least 8 characters.',
                'The new password field format is invalid.',
            ]);
    }
}