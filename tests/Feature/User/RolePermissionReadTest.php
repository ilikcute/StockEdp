<?php

namespace Tests\Feature\User;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionReadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole->id);
    }

    public function test_can_read_roles_with_permissions_and_user_counts(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/roles');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'description',
                        'users_count',
                        'permissions' => [
                            '*' => ['id', 'code', 'name', 'group'],
                        ],
                    ],
                ],
            ]);
    }

    public function test_can_read_grouped_permissions(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/permissions');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'products',
                    'categories',
                    'units',
                    'suppliers',
                    'locations',
                    'stock_receipts',
                    'stock_issues',
                    'stock_transfers',
                    'stock_adjustments',
                    'stock_opnames',
                    'reports',
                    'users',
                ],
            ]);
    }
}
