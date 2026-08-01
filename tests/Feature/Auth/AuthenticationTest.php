<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_email_and_correct_password(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login berhasil.')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'username', 'email', 'is_active', 'roles', 'permissions'],
                ],
            ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_username_and_correct_password(): void
    {
        $user = User::factory()->create([
            'username' => 'officer1',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'officer1',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_incorrect_password(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'user@example.com',
            'password' => 'wrong_password',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Data yang dikirim tidak valid.')
            ->assertJsonValidationErrors(['login']);

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['login'])
            ->assertJsonPath('errors.login.0', 'Kredensial yang diberikan tidak cocok dengan catatan kami.');

        $this->assertGuest();

        // Pastikan tidak ada update metadata
        $user->refresh();
        $this->assertNull($user->last_login_at);
        $this->assertNull($user->last_login_ip);
    }

    public function test_login_success_updates_last_login_at_and_last_login_ip(): void
    {
        $user = User::factory()->create([
            'email' => 'active@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'last_login_at' => null,
            'last_login_ip' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'active@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk();

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertEquals('127.0.0.1', $user->last_login_ip);
    }

    public function test_password_incorrect_does_not_update_metadata(): void
    {
        $user = User::factory()->create([
            'email' => 'active@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'last_login_at' => null,
            'last_login_ip' => null,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'login' => 'active@example.com',
            'password' => 'wrong_password',
        ]);

        $user->refresh();
        $this->assertNull($user->last_login_at);
        $this->assertNull($user->last_login_ip);
    }

    public function test_login_is_rate_limited_after_many_failures(): void
    {
        $login = 'target@example.com';
        RateLimiter::clear(Str_transliterate_key($login));

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'login' => $login,
                'password' => 'wrong',
            ]);
        }

        // Percobaan ke-6 harusnya diblokir
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => $login,
            'password' => 'wrong',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['login']);
    }

    public function test_logged_in_user_can_get_profile(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user, 'web')->getJson('/api/v1/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_unauthenticated_user_cannot_get_profile(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user, 'web')->postJson('/api/v1/auth/logout');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Berhasil keluar dari sistem.');

        $this->assertGuest('web');
    }
}

// Helper untuk menyamakan rate limiting key
function Str_transliterate_key(string $login): string
{
    return Str::transliterate(Str::lower($login).'|127.0.0.1');
}
