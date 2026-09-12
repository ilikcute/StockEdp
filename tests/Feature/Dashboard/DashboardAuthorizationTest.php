<?php

namespace Tests\Feature\Dashboard;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $warehouseOfficer;

    private User $supervisor;

    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $warehouseRole = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();
        $this->warehouseOfficer = User::factory()->create();
        $this->warehouseOfficer->roles()->attach($warehouseRole);

        $supervisorRole = Role::where('code', RoleCode::INVENTORY_SUPERVISOR->value)->first();
        $this->supervisor = User::factory()->create();
        $this->supervisor->roles()->attach($supervisorRole);

        $this->unauthorizedUser = User::factory()->create();
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/v1/dashboard');
        $response->assertUnauthorized();
    }

    public function test_unauthorized_user_without_permission_receives_forbidden(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->getJson('/api/v1/dashboard');
        $response->assertForbidden();
    }

    public function test_admin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/dashboard');
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'filters',
                    'inventory_health',
                    'operational_queue',
                    'period_activity',
                    'alerts',
                    'recent_activity',
                    'top_issued_products',
                    'top_received_products',
                    'generated_at',
                ],
            ]);
    }

    public function test_warehouse_officer_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->warehouseOfficer)->getJson('/api/v1/dashboard');
        $response->assertOk();
    }

    public function test_inventory_supervisor_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->supervisor)->getJson('/api/v1/dashboard');
        $response->assertOk();
    }

    public function test_seeder_has_assigned_dashboard_view_permission(): void
    {
        $permission = Permission::where('code', PermissionCode::DASHBOARD_VIEW->value)->first();
        $this->assertNotNull($permission);

        $this->assertTrue($this->admin->hasPermissionTo(PermissionCode::DASHBOARD_VIEW));
        $this->assertTrue($this->warehouseOfficer->hasPermissionTo(PermissionCode::DASHBOARD_VIEW));
        $this->assertTrue($this->supervisor->hasPermissionTo(PermissionCode::DASHBOARD_VIEW));
    }
}
