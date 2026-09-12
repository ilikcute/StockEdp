<?php

namespace Tests\Feature\Dashboard;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardLowStockParityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->location = Location::create(['code' => 'LOC-PARITY', 'name' => 'Location Parity', 'is_active' => true]);
        $this->admin->locations()->attach($this->location);
    }

    public function test_dashboard_low_stock_count_matches_canonical_low_stock_report_count(): void
    {
        $cat = Category::create(['code' => 'CAT-PAR', 'name' => 'Cat Parity', 'is_active' => true]);
        $unit = Unit::create(['code' => 'UNT-PAR', 'name' => 'Unit Parity', 'symbol' => 'up', 'is_active' => true]);

        // 1. Product with minimum stock = 10, balance = 3 -> Low Stock
        $p1 = Product::create([
            'sku' => 'PRD-PAR-001',
            'name' => 'P1 Low Stock',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'minimum_stock' => 10,
            'is_active' => true,
        ]);
        InventoryBalance::create([
            'product_id' => $p1->id,
            'location_id' => $this->location->id,
            'quantity' => '3.0000',
        ]);

        // 2. Product with minimum stock = 5, balance = 0 -> Low Stock & Out of Stock
        $p2 = Product::create([
            'sku' => 'PRD-PAR-002',
            'name' => 'P2 Out of Stock',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'minimum_stock' => 5,
            'is_active' => true,
        ]);
        InventoryBalance::create([
            'product_id' => $p2->id,
            'location_id' => $this->location->id,
            'quantity' => '0.0000',
        ]);

        // 3. Product with minimum stock = 10, balance = 15 -> Normal (Not Low Stock)
        $p3 = Product::create([
            'sku' => 'PRD-PAR-003',
            'name' => 'P3 Normal Stock',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'minimum_stock' => 10,
            'is_active' => true,
        ]);
        InventoryBalance::create([
            'product_id' => $p3->id,
            'location_id' => $this->location->id,
            'quantity' => '15.0000',
        ]);

        // Fetch Low Stock Report
        $reportResponse = $this->actingAs($this->admin)->getJson('/api/v1/reports/low-stock?location_id='.$this->location->id);
        $reportResponse->assertOk();
        $reportCount = $reportResponse->json('data.meta.total') ?? count($reportResponse->json('data.data'));

        // Fetch Dashboard
        $dashboardResponse = $this->actingAs($this->admin)->getJson('/api/v1/dashboard?location_id='.$this->location->id);
        $dashboardResponse->assertOk();
        $dashboardLowStockCount = $dashboardResponse->json('data.inventory_health.low_stock_count');

        // Exact parity requirement
        $this->assertGreaterThan(0, $reportCount);
        $this->assertSame($reportCount, $dashboardLowStockCount);
    }
}
