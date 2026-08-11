<?php

namespace Tests\Feature\Dashboard;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTopMovementsTest extends TestCase
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

        $this->location = Location::create(['code' => 'LOC-TOP', 'name' => 'Loc Top', 'is_active' => true]);
        $this->admin->locations()->attach($this->location);
    }

    public function test_top_issued_and_received_products_ranks_correctly_with_decimal_totals(): void
    {
        $cat = Category::create(['code' => 'CAT-TOP', 'name' => 'Cat Top', 'is_active' => true]);
        $unit = Unit::create(['code' => 'UNT-TOP', 'name' => 'Unit Top', 'symbol' => 'ut', 'is_active' => true]);

        $p1 = Product::create([
            'sku' => 'PRD-TOP-001',
            'name' => 'Product 1',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);
        $p2 = Product::create([
            'sku' => 'PRD-TOP-002',
            'name' => 'Product 2',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        // P1: Issued 100.5000 total (2 movements: 60.5000 + 40.0000)
        StockMovement::create([
            'movement_id' => 'MOV-TOP-01',
            'reference_type' => 'App\Features\Inventory\Models\StockIssue',
            'reference_id' => 1,
            'product_id' => $p1->id,
            'location_id' => $this->location->id,
            'movement_type' => MovementType::ISSUE->value,
            'quantity' => '60.5000',
            'quantity_before' => '100.5000',
            'quantity_after' => '40.0000',
            'reference_number' => 'REF-TOP-01',
            'created_by' => $this->admin->id,
            'occurred_at' => now(),
        ]);
        StockMovement::create([
            'movement_id' => 'MOV-TOP-02',
            'reference_type' => 'App\Features\Inventory\Models\StockIssue',
            'reference_id' => 2,
            'product_id' => $p1->id,
            'location_id' => $this->location->id,
            'movement_type' => MovementType::ISSUE->value,
            'quantity' => '40.0000',
            'quantity_before' => '40.0000',
            'quantity_after' => '0.0000',
            'reference_number' => 'REF-TOP-02',
            'created_by' => $this->admin->id,
            'occurred_at' => now(),
        ]);

        // P2: Issued 250.0000 total
        StockMovement::create([
            'movement_id' => 'MOV-TOP-03',
            'reference_type' => 'App\Features\Inventory\Models\StockIssue',
            'reference_id' => 3,
            'product_id' => $p2->id,
            'location_id' => $this->location->id,
            'movement_type' => MovementType::ISSUE->value,
            'quantity' => '250.0000',
            'quantity_before' => '250.0000',
            'quantity_after' => '0.0000',
            'reference_number' => 'REF-TOP-03',
            'created_by' => $this->admin->id,
            'occurred_at' => now(),
        ]);

        // P1 Received 500.0000
        StockMovement::create([
            'movement_id' => 'MOV-TOP-04',
            'reference_type' => 'App\Features\Inventory\Models\StockReceipt',
            'reference_id' => 4,
            'product_id' => $p1->id,
            'location_id' => $this->location->id,
            'movement_type' => MovementType::RECEIPT->value,
            'quantity' => '500.0000',
            'quantity_before' => '0.0000',
            'quantity_after' => '500.0000',
            'reference_number' => 'REF-TOP-04',
            'created_by' => $this->admin->id,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/dashboard');
        $response->assertOk();

        $topIssued = $response->json('data.top_issued_products');
        $topReceived = $response->json('data.top_received_products');

        // Top Issued ranking: P2 (250.0000) first, P1 (100.5000) second
        $this->assertCount(2, $topIssued);
        $this->assertSame($p2->sku, $topIssued[0]['sku']);
        $this->assertSame('250.0000', $topIssued[0]['total_quantity']);
        $this->assertSame($p1->sku, $topIssued[1]['sku']);
        $this->assertSame('100.5000', $topIssued[1]['total_quantity']);

        // Top Received ranking: P1 (500.0000) first
        $this->assertCount(1, $topReceived);
        $this->assertSame($p1->sku, $topReceived[0]['sku']);
        $this->assertSame('500.0000', $topReceived[0]['total_quantity']);
    }
}
