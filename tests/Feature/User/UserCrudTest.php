<?php

namespace Tests\Feature\User;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Location\Models\Location;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Role $adminRole;

    protected Role $warehouseRole;

    protected Location $locationA;

    protected Location $locationB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->warehouseRole = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($this->adminRole->id);

        $this->locationA = Location::create([
            'code' => 'WH-A',
            'name' => 'Gudang A',
            'is_active' => true,
        ]);

        $this->locationB = Location::create([
            'code' => 'WH-B',
            'name' => 'Gudang B',
            'is_active' => true,
        ]);
    }

    public function test_can_list_users_with_filters(): void
    {
        $user1 = User::factory()->create([
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'is_active' => true,
        ]);
        $user1->roles()->attach($this->warehouseRole->id);
        $user1->locations()->attach($this->locationA->id);

        $user2 = User::factory()->create([
            'name' => 'Jane Smith',
            'username' => 'janesmith',
            'email' => 'jane@example.com',
            'is_active' => false,
        ]);

        // Search test
        $response = $this->actingAs($this->admin)->getJson('/api/v1/users?search=John');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.username', 'johndoe');

        // Role filter
        $response = $this->actingAs($this->admin)->getJson('/api/v1/users?role_id='.$this->warehouseRole->id);
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // Status filter
        $response = $this->actingAs($this->admin)->getJson('/api/v1/users?is_active=false');
        $response->assertStatus(200)
            ->assertJsonPath('data.0.username', 'janesmith');
    }

    public function test_can_get_form_options(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/users/form-options');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'roles' => [['id', 'code', 'name', 'description']],
                    'locations' => [['id', 'code', 'name']],
                ],
            ]);
    }

    public function test_can_create_user_with_roles_and_locations(): void
    {
        $payload = [
            'name' => 'Petugas Gudang Baru',
            'username' => 'petugas_gudang1',
            'email' => 'petugas1@example.com',
            'password' => 'secret12345',
            'role_ids' => [$this->warehouseRole->id],
            'location_ids' => [$this->locationA->id, $this->locationB->id],
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/v1/users', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.username', 'petugas_gudang1')
            ->assertJsonPath('data.role_ids.0', $this->warehouseRole->id)
            ->assertJsonCount(2, 'data.locations');

        $this->assertDatabaseHas('users', [
            'username' => 'petugas_gudang1',
            'email' => 'petugas1@example.com',
            'is_active' => 1,
        ]);

        $createdUser = User::where('username', 'petugas_gudang1')->first();
        $this->assertTrue(Hash::check('secret12345', $createdUser->password));
        $this->assertTrue($createdUser->hasRole(RoleCode::WAREHOUSE_OFFICER));
        $this->assertEquals([$this->locationA->id, $this->locationB->id], $createdUser->getAllowedLocationIds());
    }

    public function test_create_user_validation_errors(): void
    {
        // 1. Duplicate username & email
        $payload = [
            'name' => 'Admin Copy',
            'username' => $this->admin->username,
            'email' => $this->admin->email,
            'password' => 'pass1234',
            'role_ids' => [$this->adminRole->id],
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/v1/users', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'email']);

        // 2. Short password (< 8 chars) & missing roles
        $response = $this->actingAs($this->admin)->postJson('/api/v1/users', [
            'name' => 'Short Pass',
            'username' => 'shortpass',
            'email' => 'short@example.com',
            'password' => '123',
            'role_ids' => [],
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password', 'role_ids']);
    }

    public function test_can_update_user_without_modifying_password(): void
    {
        $targetUser = User::factory()->create([
            'name' => 'Original Name',
            'username' => 'orig_user',
            'email' => 'orig@example.com',
            'password' => Hash::make('original_password'),
            'is_active' => true,
        ]);
        $targetUser->roles()->attach($this->warehouseRole->id);

        $updatePayload = [
            'name' => 'Updated Name',
            'username' => 'updated_user',
            'email' => 'updated@example.com',
            'password' => '', // kosong, tidak ganti password
            'role_ids' => [$this->warehouseRole->id],
            'location_ids' => [$this->locationA->id],
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->putJson("/api/v1/users/{$targetUser->id}", $updatePayload);
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.username', 'updated_user');

        $targetUser->refresh();
        $this->assertEquals('Updated Name', $targetUser->name);
        $this->assertTrue(Hash::check('original_password', $targetUser->password));
        $this->assertEquals([$this->locationA->id], $targetUser->getAllowedLocationIds());
    }

    public function test_can_update_user_password_when_provided(): void
    {
        $targetUser = User::factory()->create([
            'name' => 'User Password Test',
            'username' => 'passtest',
            'email' => 'passtest@example.com',
            'password' => Hash::make('old_secret123'),
        ]);
        $targetUser->roles()->attach($this->warehouseRole->id);

        $response = $this->actingAs($this->admin)->putJson("/api/v1/users/{$targetUser->id}", [
            'name' => 'User Password Test',
            'username' => 'passtest',
            'email' => 'passtest@example.com',
            'password' => 'new_secret_password_888',
            'role_ids' => [$this->warehouseRole->id],
        ]);

        $response->assertStatus(200);

        $targetUser->refresh();
        $this->assertTrue(Hash::check('new_secret_password_888', $targetUser->password));
        $this->assertFalse(Hash::check('old_secret123', $targetUser->password));
    }
}
