<?php

namespace Tests\Feature\User;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $warehouseOfficer;

    protected User $guestUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $warehouseRole = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole->id);

        $this->warehouseOfficer = User::factory()->create(['is_active' => true]);
        $this->warehouseOfficer->roles()->attach($warehouseRole->id);

        $this->guestUser = User::factory()->create(['is_active' => true]);
    }

    public function test_unauthenticated_user_cannot_access_user_endpoints(): void
    {
        $response = $this->getJson('/api/v1/users');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/roles');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/permissions');
        $response->assertStatus(401);
    }

    public function test_user_without_users_manage_permission_is_forbidden(): void
    {
        $response = $this->actingAs($this->warehouseOfficer)->getJson('/api/v1/users');
        $response->assertStatus(403);

        $response = $this->actingAs($this->warehouseOfficer)->getJson('/api/v1/roles');
        $response->assertStatus(403);

        $response = $this->actingAs($this->warehouseOfficer)->getJson('/api/v1/permissions');
        $response->assertStatus(403);

        $response = $this->actingAs($this->warehouseOfficer)->postJson('/api/v1/users', [
            'name' => 'New User',
            'username' => 'newuser',
            'email' => 'new@example.com',
            'password' => 'password123',
            'role_ids' => [1],
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_with_users_manage_permission_can_access_endpoints(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/users');
        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/roles');
        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/permissions');
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
