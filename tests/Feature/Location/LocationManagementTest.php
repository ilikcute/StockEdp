<?php

namespace Tests\Feature\Location;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Location\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $viewerUser;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create([
            'code' => RoleCode::ADMIN->value,
            'name' => 'Administrator',
        ]);

        $createPermission = Permission::create([
            'code' => PermissionCode::LOCATIONS_CREATE->value,
            'name' => 'Membuat Lokasi',
            'group' => 'locations',
        ]);

        $viewPermission = Permission::create([
            'code' => PermissionCode::LOCATIONS_VIEW->value,
            'name' => 'Lihat Lokasi',
            'group' => 'locations',
        ]);

        $adminRole->permissions()->attach([$createPermission->id, $viewPermission->id]);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole->id);

        $viewerRole = Role::create([
            'code' => RoleCode::WAREHOUSE_OFFICER->value,
            'name' => 'Petugas Gudang',
        ]);
        $viewerRole->permissions()->attach($viewPermission->id);

        $this->viewerUser = User::factory()->create(['is_active' => true]);
        $this->viewerUser->roles()->attach($viewerRole->id);

        $this->regularUser = User::factory()->create(['is_active' => true]);
    }

    public function test_unauthenticated_user_cannot_access_locations(): void
    {
        $response = $this->getJson('/api/v1/locations');
        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_list_locations(): void
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/v1/locations');
        $response->assertStatus(403);
    }

    public function test_viewer_can_list_locations(): void
    {
        Location::factory()->count(3)->create();

        $response = $this->actingAs($this->viewerUser)->getJson('/api/v1/locations');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_user_without_create_permission_cannot_create_location(): void
    {
        $response = $this->actingAs($this->viewerUser)->postJson('/api/v1/locations', [
            'code' => 'LOC-001',
            'name' => 'Gudang Utama',
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_can_create_location(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/locations', [
            'code' => 'loc-001',
            'name' => 'Gudang Utama',
            'address' => 'Jl. Merdeka No.1',
            'phone' => '081234567890',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'LOC-001')
            ->assertJsonPath('data.name', 'Gudang Utama')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('locations', [
            'code' => 'LOC-001',
            'name' => 'Gudang Utama',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_location_code_must_be_unique(): void
    {
        Location::factory()->create(['code' => 'LOC-001']);

        $response = $this->actingAs($this->admin)->postJson('/api/v1/locations', [
            'code' => 'LOC-001',
            'name' => 'Gudang Cabang',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_admin_can_update_location(): void
    {
        $updatePermission = Permission::create([
            'code' => PermissionCode::LOCATIONS_UPDATE->value,
            'name' => 'Ubah Lokasi',
            'group' => 'locations',
        ]);
        $this->admin->roles->first()->permissions()->attach($updatePermission->id);

        $location = Location::factory()->create();

        $response = $this->actingAs($this->admin)->putJson("/api/v1/locations/{$location->id}", [
            'code' => $location->code,
            'name' => 'Nama Baru',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nama Baru');

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'name' => 'Nama Baru',
            'updated_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_change_location_status(): void
    {
        $statusPermission = Permission::create([
            'code' => PermissionCode::LOCATIONS_CHANGE_STATUS->value,
            'name' => 'Ubah Status',
            'group' => 'locations',
        ]);
        $this->admin->roles->first()->permissions()->attach($statusPermission->id);

        $location = Location::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->patchJson("/api/v1/locations/{$location->id}/status", [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'is_active' => false,
        ]);
    }
}
