<?php

namespace Tests\Feature\Reporting;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Location\Models\Location;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryMovementAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->location = Location::factory()->create(['is_active' => true]);
    }

    public function test_guest_cannot_access_inventory_movement_report(): void
    {
        $response = $this->getJson('/api/v1/reports/inventory-movement');
        $response->assertStatus(401);
    }

    public function test_guest_cannot_access_dashboard_movement_summary(): void
    {
        $response = $this->getJson('/api/v1/dashboard/inventory-movement-summary');
        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_access_report(): void
    {
        $user = User::factory()->create();
        $user->locations()->attach($this->location->id);

        $response = $this->actingAs($user)->getJson('/api/v1/reports/inventory-movement');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_inventory_movement_report_and_summary(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('code', RoleCode::ADMIN->value)->first()->id);
        $admin->locations()->attach($this->location->id);

        $response = $this->actingAs($admin)->getJson('/api/v1/reports/inventory-movement');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'type',
                    'period',
                    'date_from',
                    'date_to',
                    'summary' => [
                        'period_days',
                        'slow_moving_count',
                        'fast_moving_count',
                    ],
                    'pagination',
                ],
            ]);

        $summaryRes = $this->actingAs($admin)->getJson('/api/v1/dashboard/inventory-movement-summary');
        $summaryRes->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period_days',
                    'slow_moving_count',
                    'fast_moving_count',
                ],
            ]);
    }

    public function test_warehouse_officer_can_access_inventory_movement_report(): void
    {
        $warehouseUser = User::factory()->create();
        $warehouseUser->roles()->attach(Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first()->id);
        $warehouseUser->locations()->attach($this->location->id);

        $response = $this->actingAs($warehouseUser)->getJson('/api/v1/reports/inventory-movement');
        $response->assertStatus(200);
    }

    public function test_supervisor_can_access_inventory_movement_report(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->roles()->attach(Role::where('code', RoleCode::INVENTORY_SUPERVISOR->value)->first()->id);
        $supervisor->locations()->attach($this->location->id);

        $response = $this->actingAs($supervisor)->getJson('/api/v1/reports/inventory-movement');
        $response->assertStatus(200);
    }
}
