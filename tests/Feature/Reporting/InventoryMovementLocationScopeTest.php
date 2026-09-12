<?php

namespace Tests\Feature\Reporting;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryMovementLocationScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Location $allowedLocation;

    private Location $forbiddenLocation;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->allowedLocation = Location::factory()->create(['name' => 'Allowed Wh', 'is_active' => true]);
        $this->forbiddenLocation = Location::factory()->create(['name' => 'Forbidden Wh', 'is_active' => true]);

        $this->product = Product::factory()->create(['is_active' => true]);

        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('code', RoleCode::ADMIN->value)->first()->id);
        $this->user->locations()->attach($this->allowedLocation->id);
    }

    public function test_user_cannot_access_unassigned_location_in_report(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/reports/inventory-movement?location_id={$this->forbiddenLocation->id}");
        $response->assertStatus(403);
    }

    public function test_user_cannot_access_unassigned_location_in_dashboard_summary(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/dashboard/inventory-movement-summary?location_id={$this->forbiddenLocation->id}");
        $response->assertStatus(403);
    }

    public function test_user_without_assigned_locations_gets_empty_report(): void
    {
        $userWithoutLoc = User::factory()->create();
        $userWithoutLoc->roles()->attach(Role::where('code', RoleCode::ADMIN->value)->first()->id);

        $response = $this->actingAs($userWithoutLoc)->getJson('/api/v1/reports/inventory-movement');
        $response->assertStatus(200)
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.summary.slow_moving_count', 0)
            ->assertJsonPath('meta.summary.fast_moving_count', 0);
    }

    public function test_report_only_includes_movements_from_allowed_location(): void
    {
        // Outbound movement in forbidden location
        StockMovement::create([
            'movement_id' => (string) Str::uuid(),
            'product_id' => $this->product->id,
            'location_id' => $this->forbiddenLocation->id,
            'movement_type' => MovementType::ISSUE->value,
            'quantity' => '50.0000',
            'quantity_before' => '100.0000',
            'quantity_after' => '50.0000',
            'reference_type' => 'App\Features\Inventory\Models\StockIssue',
            'reference_id' => 1,
            'occurred_at' => now(),
            'created_by' => $this->user->id,
        ]);

        // Fast moving should be 0 in user's allowed scope
        $fastResponse = $this->actingAs($this->user)->getJson('/api/v1/reports/inventory-movement?type=fast-moving');
        $fastResponse->assertStatus(200)
            ->assertJsonPath('meta.summary.fast_moving_count', 0)
            ->assertJsonPath('data', []);

        // Slow moving should be 1 because product has 0 movements in allowed location
        $slowResponse = $this->actingAs($this->user)->getJson('/api/v1/reports/inventory-movement?type=slow-moving');
        $slowResponse->assertStatus(200)
            ->assertJsonPath('meta.summary.slow_moving_count', 1)
            ->assertJsonPath('data.0.product_id', $this->product->id)
            ->assertJsonPath('data.0.location_id', $this->allowedLocation->id);
    }
}
