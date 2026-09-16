<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_user_cannot_update_profile(): void
    {
        $this->patchJson('/api/v1/auth/profile', [
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
        ])->assertStatus(401);
    }

    public function test_user_can_update_name_and_email(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->patchJson('/api/v1/auth/profile', [
                'name' => 'Andi Wijaya',
                'email' => 'andi@example.com',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Profil berhasil diperbarui.')
            ->assertJsonPath('data.name', 'Andi Wijaya')
            ->assertJsonPath('data.email', 'andi@example.com');

        $this->user->refresh();
        $this->assertEquals('Andi Wijaya', $this->user->name);
        $this->assertEquals('andi@example.com', $this->user->email);
    }

    public function test_email_is_normalized_to_lowercase_on_update(): void
    {
        $this->actingAs($this->user, 'web')
            ->patchJson('/api/v1/auth/profile', [
                'name' => 'Budi Santoso',
                'email' => 'Budi@Example.COM',
            ])
            ->assertOk();

        $this->user->refresh();
        $this->assertEquals('budi@example.com', $this->user->email);
    }

    public function test_profile_update_rejects_email_used_by_other_user(): void
    {
        User::factory()->create([
            'email' => 'taken@example.com',
        ]);

        $this->actingAs($this->user, 'web')
            ->patchJson('/api/v1/auth/profile', [
                'name' => 'Budi Santoso',
                'email' => 'taken@example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_profile_update_requires_name_and_email(): void
    {
        $this->actingAs($this->user, 'web')
            ->patchJson('/api/v1/auth/profile', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_unauthenticated_user_cannot_update_password(): void
    {
        $this->patchJson('/api/v1/auth/profile/password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertStatus(401);
    }

    public function test_user_can_update_password_with_valid_current_password(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->patchJson('/api/v1/auth/profile/password', [
                'current_password' => 'password123',
                'password' => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Kata sandi berhasil diperbarui.');

        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword456', $this->user->password));
    }

    public function test_password_update_rejects_wrong_current_password(): void
    {
        $this->actingAs($this->user, 'web')
            ->patchJson('/api/v1/auth/profile/password', [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_password_update_requires_confirmation(): void
    {
        $this->actingAs($this->user, 'web')
            ->patchJson('/api/v1/auth/profile/password', [
                'current_password' => 'password123',
                'password' => 'newpassword456',
                'password_confirmation' => 'differentpassword',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_password_update_requires_minimum_length(): void
    {
        $this->actingAs($this->user, 'web')
            ->patchJson('/api/v1/auth/profile/password', [
                'current_password' => 'password123',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_password_is_not_changed_when_current_password_is_wrong(): void
    {
        $oldHash = $this->user->password;

        $this->actingAs($this->user, 'web')
            ->patchJson('/api/v1/auth/profile/password', [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ])
            ->assertUnprocessable();

        $this->user->refresh();
        $this->assertEquals($oldHash, $this->user->password);
    }
}