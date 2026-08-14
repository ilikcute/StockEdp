<?php

namespace Tests\Feature\Replenishment;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Location\Models\Location;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplenishmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->location = Location::create([
            'code' => 'WH-AUTH-01',
            'name' => 'Gudang Otorisasi',
            'is_active' => true,
        ]);
    }

    public function test_guest_is_unauthorized_for_recommendations_and_filter_options(): void
    {
        $this->getJson('/api/v1/replenishment-recommendations?location_id='.$this->location->id)
            ->assertStatus(401);

        $this->getJson('/api/v1/replenishment-recommendations/filter-options')
            ->assertStatus(401);
    }

    public function test_user_without_replenishment_view_permission_is_forbidden(): void
    {
        $userWithoutPermission = User::factory()->create();
        $userWithoutPermission->locations()->attach($this->location);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->location->id)
            ->assertStatus(403);

        $this->actingAs($userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations/filter-options')
            ->assertStatus(403);
    }

    public function test_admin_is_authorized_for_recommendations_and_filter_options(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $admin->roles()->attach($adminRole);
        $admin->locations()->attach($this->location);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->location->id)
            ->assertStatus(200);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations/filter-options')
            ->assertStatus(200);
    }

    public function test_warehouse_officer_is_authorized(): void
    {
        $officer = User::factory()->create();
        $officerRole = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();
        $officer->roles()->attach($officerRole);
        $officer->locations()->attach($this->location);

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->location->id)
            ->assertStatus(200);

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations/filter-options')
            ->assertStatus(200);
    }

    public function test_inventory_supervisor_is_authorized(): void
    {
        $supervisor = User::factory()->create();
        $supervisorRole = Role::where('code', RoleCode::INVENTORY_SUPERVISOR->value)->first();
        $supervisor->roles()->attach($supervisorRole);
        $supervisor->locations()->attach($this->location);

        $this->actingAs($supervisor, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->location->id)
            ->assertStatus(200);

        $this->actingAs($supervisor, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations/filter-options')
            ->assertStatus(200);
    }

    public function test_role_and_permission_seeder_is_convergent_and_idempotent(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(RoleAndPermissionSeeder::class);

        $this->assertDatabaseHas('permissions', ['code' => 'replenishment.view']);
    }
}
