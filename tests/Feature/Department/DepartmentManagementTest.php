<?php

namespace Tests\Feature\Department;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Department\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(
            ['code' => RoleCode::ADMIN->value],
            ['name' => 'Administrator']
        );

        $permissions = [
            PermissionCode::DEPARTMENTS_VIEW->value => 'Lihat Departemen',
            PermissionCode::DEPARTMENTS_CREATE->value => 'Membuat Departemen',
            PermissionCode::DEPARTMENTS_UPDATE->value => 'Ubah Departemen',
            PermissionCode::DEPARTMENTS_CHANGE_STATUS->value => 'Ubah Status Departemen',
        ];

        $pIds = [];
        foreach ($permissions as $code => $name) {
            $perm = Permission::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'group' => 'departments']
            );
            $pIds[] = $perm->id;
        }

        $adminRole->permissions()->syncWithoutDetaching($pIds);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole->id);

        $this->regularUser = User::factory()->create(['is_active' => true]);
    }

    public function test_admin_can_list_departments(): void
    {
        Department::create([
            'code' => 'IT',
            'name' => 'EDP & IT',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/departments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'code', 'name', 'is_active'],
                ],
                'meta',
            ])
            ->assertJsonFragment(['code' => 'IT', 'name' => 'EDP & IT']);
    }

    public function test_active_departments_list_can_be_retrieved(): void
    {
        Department::create(['code' => 'ACT1', 'name' => 'Active Dept 1', 'is_active' => true]);
        Department::create(['code' => 'INACT', 'name' => 'Inactive Dept', 'is_active' => false]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/departments/active');

        $response->assertStatus(200)
            ->assertJsonFragment(['code' => 'ACT1'])
            ->assertJsonMissing(['code' => 'INACT']);
    }

    public function test_admin_can_create_department(): void
    {
        $payload = [
            'code' => 'HRD',
            'name' => 'Human Resources',
            'description' => 'HR Department',
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/v1/departments', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'HRD')
            ->assertJsonPath('data.name', 'Human Resources');

        $this->assertDatabaseHas('departments', [
            'code' => 'HRD',
            'name' => 'Human Resources',
        ]);
    }

    public function test_cannot_create_department_with_duplicate_code(): void
    {
        Department::create([
            'code' => 'FIN',
            'name' => 'Finance',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/v1/departments', [
            'code' => 'fin',
            'name' => 'Finance Other',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_admin_can_update_department(): void
    {
        $dept = Department::create([
            'code' => 'MKT',
            'name' => 'Marketing',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->putJson("/api/v1/departments/{$dept->id}", [
            'code' => 'MKT',
            'name' => 'Marketing & Sales',
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Marketing & Sales');

        $this->assertDatabaseHas('departments', [
            'id' => $dept->id,
            'name' => 'Marketing & Sales',
        ]);
    }

    public function test_admin_can_change_department_status(): void
    {
        $dept = Department::create([
            'code' => 'LOG',
            'name' => 'Logistik',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->patchJson("/api/v1/departments/{$dept->id}/status", [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('departments', [
            'id' => $dept->id,
            'is_active' => false,
        ]);
    }

    public function test_user_without_permission_cannot_access_departments(): void
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/v1/departments');

        $response->assertStatus(403);
    }
}
