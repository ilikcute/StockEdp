<?php

namespace Tests\Feature\Dashboard;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);
    }

    public function test_dashboard_endpoint_executes_within_2000_ms(): void
    {
        $startTime = microtime(true);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/dashboard');
        $response->assertOk();

        $executionTimeMs = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(2000, $executionTimeMs, "Dashboard response time ({$executionTimeMs} ms) exceeded 2000 ms limit.");
    }
}
