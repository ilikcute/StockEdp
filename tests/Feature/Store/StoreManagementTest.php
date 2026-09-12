<?php

namespace Tests\Feature\Store;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Store\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $viewerUser;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(
            ['code' => RoleCode::ADMIN->value],
            ['name' => 'Administrator']
        );

        $createPermission = Permission::firstOrCreate(
            ['code' => PermissionCode::STORES_CREATE->value],
            ['name' => 'Membuat Toko', 'group' => 'stores']
        );

        $viewPermission = Permission::firstOrCreate(
            ['code' => PermissionCode::STORES_VIEW->value],
            ['name' => 'Lihat Toko', 'group' => 'stores']
        );

        $updatePermission = Permission::firstOrCreate(
            ['code' => PermissionCode::STORES_UPDATE->value],
            ['name' => 'Ubah Toko', 'group' => 'stores']
        );

        $statusPermission = Permission::firstOrCreate(
            ['code' => PermissionCode::STORES_CHANGE_STATUS->value],
            ['name' => 'Ubah Status Toko', 'group' => 'stores']
        );

        $adminRole->permissions()->syncWithoutDetaching([
            $createPermission->id,
            $viewPermission->id,
            $updatePermission->id,
            $statusPermission->id,
        ]);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole->id);

        $viewerRole = Role::firstOrCreate(
            ['code' => RoleCode::WAREHOUSE_OFFICER->value],
            ['name' => 'Petugas Gudang']
        );
        $viewerRole->permissions()->syncWithoutDetaching([$viewPermission->id]);

        $this->viewerUser = User::factory()->create(['is_active' => true]);
        $this->viewerUser->roles()->attach($viewerRole->id);

        $this->regularUser = User::factory()->create(['is_active' => true]);
    }

    public function test_unauthenticated_user_cannot_access_stores(): void
    {
        $response = $this->getJson('/api/v1/stores');
        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_list_stores(): void
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/v1/stores');
        $response->assertStatus(403);
    }

    public function test_viewer_can_list_stores(): void
    {
        Store::create([
            'code' => 'TK-001',
            'name' => 'Toko Cabang Mawar',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->viewerUser)->getJson('/api/v1/stores');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_admin_can_create_store(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/stores', [
            'code' => 'tk-101',
            'name' => 'Toko Melati',
            'address' => 'Jl. Melati No. 12',
            'phone' => '08123456789',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'TK-101')
            ->assertJsonPath('data.name', 'Toko Melati')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('stores', [
            'code' => 'TK-101',
            'name' => 'Toko Melati',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_store_code_must_be_unique(): void
    {
        Store::create([
            'code' => 'TK-DUP',
            'name' => 'Toko A',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/v1/stores', [
            'code' => 'TK-DUP',
            'name' => 'Toko B',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_admin_can_update_store(): void
    {
        $store = Store::create([
            'code' => 'TK-OLD',
            'name' => 'Toko Lama',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->putJson("/api/v1/stores/{$store->id}", [
            'code' => 'TK-NEW',
            'name' => 'Toko Baru Diperbarui',
            'address' => 'Alamat Baru',
            'phone' => '08987654321',
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.code', 'TK-NEW')
            ->assertJsonPath('data.name', 'Toko Baru Diperbarui');

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'code' => 'TK-NEW',
            'updated_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_toggle_store_status(): void
    {
        $store = Store::create([
            'code' => 'TK-STATUS',
            'name' => 'Toko Status Test',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->patchJson("/api/v1/stores/{$store->id}/status", [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'is_active' => false,
        ]);
    }
}
