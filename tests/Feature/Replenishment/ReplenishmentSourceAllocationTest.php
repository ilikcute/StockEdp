<?php

namespace Tests\Feature\Replenishment;

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

class ReplenishmentSourceAllocationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Location $targetLocation;

    private Location $sourceA;

    private Location $sourceB;

    private Location $sourceC;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->targetLocation = Location::create(['code' => 'WH-TGT', 'name' => 'Gudang Target', 'is_active' => true]);
        $this->sourceA = Location::create(['code' => 'WH-SRC-A', 'name' => 'Gudang A', 'is_active' => true]);
        $this->sourceB = Location::create(['code' => 'WH-SRC-B', 'name' => 'Gudang B', 'is_active' => true]);
        $this->sourceC = Location::create(['code' => 'WH-SRC-C', 'name' => 'Gudang C', 'is_active' => true]);

        $this->admin = User::factory()->create();
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole);
        $this->admin->locations()->attach([
            $this->targetLocation->id,
            $this->sourceA->id,
            $this->sourceB->id,
            $this->sourceC->id,
        ]);

        $cat = Category::create(['code' => 'CAT-01', 'name' => 'Kategori 1', 'is_active' => true]);
        $unit = Unit::create(['code' => 'UNT-01', 'name' => 'PCS', 'symbol' => 'pcs', 'is_active' => true]);

        // Product: min 10.00, target on hand 1.0000 -> gross shortage 9.0000
        $this->product = Product::create([
            'sku' => 'PRD-ALLOC-001',
            'name' => 'Produk Alokasi Test',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'minimum_stock' => '10.00',
            'is_active' => true,
        ]);
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->targetLocation->id,
            'quantity' => '1.0000',
        ]);
    }

    public function test_sources_at_or_below_minimum_stock_are_excluded(): void
    {
        // Source A has 25 on hand (surplus 15)
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->sourceA->id,
            'quantity' => '25.0000',
        ]);

        // Source B has 10 on hand (min is 10, surplus 0)
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->sourceB->id,
            'quantity' => '10.0000',
        ]);

        // Source C has 8 on hand (below min 10, surplus 0)
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->sourceC->id,
            'quantity' => '8.0000',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->targetLocation->id)
            ->assertStatus(200);

        $row = $response->json('data.data.0');
        $this->assertEquals('INTERNAL_TRANSFER', $row['recommendation_type']);
        $this->assertEquals('9.0000', $row['internal_replenishment_quantity']);
        $this->assertEquals('0.0000', $row['external_reorder_quantity']);
        $this->assertCount(1, $row['source_allocations']);

        $alloc = $row['source_allocations'][0];
        $this->assertEquals($this->sourceA->id, $alloc['source_location_id']);
        $this->assertEquals('15.0000', $alloc['available_surplus_quantity']);
        $this->assertEquals('9.0000', $alloc['suggested_transfer_quantity']);
    }

    public function test_greedy_deterministic_multi_source_allocation(): void
    {
        // Net need = 9.0000
        // Source A surplus = 5.0000 (balance 15)
        // Source B surplus = 3.0000 (balance 13)
        // Source C surplus = 10.0000 (balance 20)
        // Expected sort: C (10) > A (5) > B (3)
        // C alone can satisfy entire need 9.0000!
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->sourceA->id,
            'quantity' => '15.0000',
        ]);
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->sourceB->id,
            'quantity' => '13.0000',
        ]);
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->sourceC->id,
            'quantity' => '20.0000',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->targetLocation->id)
            ->assertStatus(200);

        $row = $response->json('data.data.0');
        $this->assertEquals('INTERNAL_TRANSFER', $row['recommendation_type']);
        $this->assertCount(1, $row['source_allocations']);
        $this->assertEquals($this->sourceC->id, $row['source_allocations'][0]['source_location_id']);
        $this->assertEquals('9.0000', $row['source_allocations'][0]['suggested_transfer_quantity']);
    }

    public function test_multi_source_decimal_split_when_single_source_insufficient(): void
    {
        // Target balance 2.5000, min 10.00 -> net need 7.5000
        InventoryBalance::where('product_id', $this->product->id)
            ->where('location_id', $this->targetLocation->id)
            ->update(['quantity' => '2.5000']);

        // Source A balance 15.2500 (min 10 -> surplus 5.2500)
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->sourceA->id,
            'quantity' => '15.2500',
        ]);

        // Source B balance 14.0000 (min 10 -> surplus 4.0000)
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->sourceB->id,
            'quantity' => '14.0000',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->targetLocation->id)
            ->assertStatus(200);

        $row = $response->json('data.data.0');
        $this->assertEquals('7.5000', $row['net_replenishment_need']);
        $this->assertEquals('INTERNAL_TRANSFER', $row['recommendation_type']);
        $this->assertEquals('7.5000', $row['internal_replenishment_quantity']);
        $this->assertEquals('0.0000', $row['external_reorder_quantity']);
        $this->assertCount(2, $row['source_allocations']);

        // 1st allocation: Source A -> 5.2500
        $this->assertEquals($this->sourceA->id, $row['source_allocations'][0]['source_location_id']);
        $this->assertEquals('5.2500', $row['source_allocations'][0]['suggested_transfer_quantity']);

        // 2nd allocation: Source B -> 2.2500
        $this->assertEquals($this->sourceB->id, $row['source_allocations'][1]['source_location_id']);
        $this->assertEquals('2.2500', $row['source_allocations'][1]['suggested_transfer_quantity']);
    }

    public function test_partial_surplus_produces_mixed_recommendation(): void
    {
        // Target net need = 9.0000 (balance 1, min 10)
        // Source A surplus = 4.0000 (balance 14)
        // Total surplus = 4.0000 < 9.0000 -> internal = 4.0000, external = 5.0000 -> MIXED
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->sourceA->id,
            'quantity' => '14.0000',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->targetLocation->id)
            ->assertStatus(200);

        $row = $response->json('data.data.0');
        $this->assertEquals('MIXED', $row['recommendation_type']);
        $this->assertEquals('4.0000', $row['internal_replenishment_quantity']);
        $this->assertEquals('5.0000', $row['external_reorder_quantity']);
        $this->assertCount(1, $row['source_allocations']);
    }

    public function test_zero_surplus_produces_external_reorder_recommendation(): void
    {
        // No source has surplus above minimum stock
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->targetLocation->id)
            ->assertStatus(200);

        $row = $response->json('data.data.0');
        $this->assertEquals('EXTERNAL_REORDER', $row['recommendation_type']);
        $this->assertEquals('0.0000', $row['internal_replenishment_quantity']);
        $this->assertEquals('9.0000', $row['external_reorder_quantity']);
        $this->assertEmpty($row['source_allocations']);
    }

    public function test_tie_breaking_deterministic_allocation_by_location_id_asc(): void
    {
        // Source A and Source B both have surplus = 5.0000 (balance 15.0000)
        // Net need = 7.0000
        // Because $this->sourceA->id < $this->sourceB->id, A gets allocated 5.0000, B gets 2.0000
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->sourceA->id,
            'quantity' => '15.0000',
        ]);
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->sourceB->id,
            'quantity' => '15.0000',
        ]);

        // Adjust target balance to 3.0000 (net need = 7.0000)
        InventoryBalance::where('product_id', $this->product->id)
            ->where('location_id', $this->targetLocation->id)
            ->update(['quantity' => '3.0000']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->targetLocation->id)
            ->assertStatus(200);

        $row = $response->json('data.data.0');
        $this->assertCount(2, $row['source_allocations']);
        $this->assertEquals($this->sourceA->id, $row['source_allocations'][0]['source_location_id']);
        $this->assertEquals('5.0000', $row['source_allocations'][0]['suggested_transfer_quantity']);
        $this->assertEquals($this->sourceB->id, $row['source_allocations'][1]['source_location_id']);
        $this->assertEquals('2.0000', $row['source_allocations'][1]['suggested_transfer_quantity']);
    }
}
