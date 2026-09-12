<?php

namespace Tests\Feature\User;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSecurityGuardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin1;

    protected User $admin2;

    protected Role $adminRole;

    protected Role $warehouseRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->warehouseRole = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();

        $this->admin1 = User::factory()->create(['is_active' => true]);
        $this->admin1->roles()->attach($this->adminRole->id);

        $this->admin2 = User::factory()->create(['is_active' => true]);
        $this->admin2->roles()->attach($this->adminRole->id);
    }

    public function test_user_cannot_deactivate_self_via_update_endpoint(): void
    {
        $response = $this->actingAs($this->admin1)->putJson("/api/v1/users/{$this->admin1->id}", [
            'name' => $this->admin1->name,
            'username' => $this->admin1->username,
            'email' => $this->admin1->email,
            'role_ids' => [$this->adminRole->id],
            'is_active' => false,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Anda tidak dapat menonaktifkan akun sendiri yang sedang digunakan.');
    }

    public function test_user_cannot_deactivate_self_via_status_endpoint(): void
    {
        $response = $this->actingAs($this->admin1)->patchJson("/api/v1/users/{$this->admin1->id}/status", [
            'is_active' => false,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Anda tidak dapat menonaktifkan akun sendiri yang sedang digunakan.');
    }

    public function test_cannot_deactivate_the_last_active_admin(): void
    {
        // Deactivate admin2 first so admin1 is the only active admin remaining
        $this->admin2->update(['is_active' => false]);

        // Attempting to deactivate admin1 (as an admin request) should be rejected
        $response = $this->actingAs($this->admin1)->patchJson("/api/v1/users/{$this->admin1->id}/status", [
            'is_active' => false,
        ]);

        $response->assertStatus(422);

        // Now activate admin1 and try deactivating admin1 from admin2 perspective
        $this->admin2->update(['is_active' => true]);
        $this->admin1->update(['is_active' => false]); // admin2 is now the single active admin

        $response = $this->actingAs($this->admin2)->patchJson("/api/v1/users/{$this->admin2->id}/status", [
            'is_active' => false,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_cannot_remove_admin_role_from_last_active_admin(): void
    {
        // Deactivate admin2 so admin1 is the sole active admin
        $this->admin2->update(['is_active' => false]);

        // Attempt to change admin1 roles to only warehouse officer (removing ADMIN role)
        $response = $this->actingAs($this->admin1)->putJson("/api/v1/users/{$this->admin1->id}", [
            'name' => $this->admin1->name,
            'username' => $this->admin1->username,
            'email' => $this->admin1->email,
            'role_ids' => [$this->warehouseRole->id], // stripping ADMIN role
            'is_active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Tidak dapat menonaktifkan atau mencabut hak akses dari Administrator aktif terakhir di sistem.');
    }
}
