<?php

namespace Tests\Feature\Dashboard;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Location\Models\Location;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFilterOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_assignment_scoped_filter_options(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $loc1 = Location::create(['code' => 'LOC-OPT1', 'name' => 'Loc Opt 1', 'is_active' => true]);
        $loc2 = Location::create(['code' => 'LOC-OPT2', 'name' => 'Loc Opt 2', 'is_active' => true]);
        $locInactive = Location::create(['code' => 'LOC-INACT', 'name' => 'Loc Inactive', 'is_active' => false]);

        $officerRole = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();
        $officer = User::factory()->create();
        $officer->roles()->attach($officerRole);
        $officer->locations()->attach([$loc1->id, $locInactive->id]); // Only loc1 and inactive attached

        $response = $this->actingAs($officer)->getJson('/api/v1/dashboard');
        $response->assertOk();

        $locations = $response->json('data.filter_options.locations');
        $locationIds = array_column($locations, 'id');

        // Contains active allowed loc1, excludes unassigned loc2, excludes inactive locInactive
        $this->assertContains($loc1->id, $locationIds);
        $this->assertNotContains($loc2->id, $locationIds);
        $this->assertNotContains($locInactive->id, $locationIds);
    }
}
