<?php

namespace Tests\Feature\Dashboard;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardLocationScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Location $allowedLocation;

    private Location $forbiddenLocation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->allowedLocation = Location::create([
            'code' => 'LOC-ALLOW',
            'name' => 'Allowed Location',
            'is_active' => true,
        ]);

        $this->forbiddenLocation = Location::create([
            'code' => 'LOC-FORBID',
            'name' => 'Forbidden Location',
            'is_active' => true,
        ]);

        $role = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();
        $this->user = User::factory()->create();
        $this->user->roles()->attach($role);
        $this->user->locations()->attach($this->allowedLocation);
    }

    public function test_dashboard_scopes_counts_and_activities_only_to_allowed_locations(): void
    {
        $cat = Category::create(['code' => 'CAT-1', 'name' => 'Category 1', 'is_active' => true]);
        $unit = Unit::create(['code' => 'UNT-1', 'name' => 'Unit 1', 'symbol' => 'u1', 'is_active' => true]);
        $product = Product::create([
            'sku' => 'PRD-SCOPE-001',
            'name' => 'Product Scope Test',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'minimum_stock' => 10,
            'is_active' => true,
        ]);

        // Balance in allowed location = 0 (out of stock)
        InventoryBalance::create([
            'product_id' => $product->id,
            'location_id' => $this->allowedLocation->id,
            'quantity' => '0.0000',
        ]);

        // Balance in forbidden location = 0 (out of stock)
        InventoryBalance::create([
            'product_id' => $product->id,
            'location_id' => $this->forbiddenLocation->id,
            'quantity' => '0.0000',
        ]);

        // Movement in allowed location
        StockMovement::create([
            'movement_id' => 'MOV-ALLOW-01',
            'reference_type' => StockIssue::class,
            'reference_id' => 1,
            'product_id' => $product->id,
            'location_id' => $this->allowedLocation->id,
            'movement_type' => MovementType::ISSUE->value,
            'quantity' => '10.0000',
            'quantity_before' => '10.0000',
            'quantity_after' => '0.0000',
            'reference_number' => 'REF-ALLOW-01',
            'created_by' => $this->user->id,
            'occurred_at' => now(),
        ]);

        // Movement in forbidden location
        StockMovement::create([
            'movement_id' => 'MOV-FORBID-01',
            'reference_type' => StockIssue::class,
            'reference_id' => 1,
            'product_id' => $product->id,
            'location_id' => $this->forbiddenLocation->id,
            'movement_type' => MovementType::ISSUE->value,
            'quantity' => '99.0000',
            'quantity_before' => '99.0000',
            'quantity_after' => '0.0000',
            'reference_number' => 'REF-FORBID-01',
            'created_by' => $this->user->id,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/dashboard');
        $response->assertOk();

        $data = $response->json('data');

        // Only 1 out of stock count in allowed location
        $this->assertSame(1, $data['inventory_health']['out_of_stock_count']);

        // Only recent activity for allowed location
        $this->assertCount(1, $data['recent_activity']);
        $this->assertSame($this->allowedLocation->code, $data['recent_activity'][0]['location_code']);

        // Top issued product total quantity must reflect only allowed location (10.0000)
        $this->assertCount(1, $data['top_issued_products']);
        $this->assertSame('10.0000', $data['top_issued_products'][0]['total_quantity']);
    }

    public function test_querying_forbidden_location_id_returns_forbidden(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/dashboard?location_id='.$this->forbiddenLocation->id);
        $response->assertForbidden();
    }

    public function test_querying_allowed_location_id_succeeds(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/dashboard?location_id='.$this->allowedLocation->id);
        $response->assertOk();
    }
}
