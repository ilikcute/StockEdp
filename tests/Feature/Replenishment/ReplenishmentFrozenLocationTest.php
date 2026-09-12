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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReplenishmentFrozenLocationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Location $targetLocation;

    private Location $frozenSource;

    private Location $unfrozenSource;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->targetLocation = Location::create(['code' => 'WH-TGT', 'name' => 'Gudang Target', 'is_active' => true]);
        $this->frozenSource = Location::create(['code' => 'WH-FRZ', 'name' => 'Gudang Beku', 'is_active' => true]);
        $this->unfrozenSource = Location::create(['code' => 'WH-UNFRZ', 'name' => 'Gudang Normal', 'is_active' => true]);

        $this->admin = User::factory()->create();
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole);
        $this->admin->locations()->attach([
            $this->targetLocation->id,
            $this->frozenSource->id,
            $this->unfrozenSource->id,
        ]);

        $cat = Category::create(['code' => 'CAT-01', 'name' => 'Kategori 1', 'is_active' => true]);
        $unit = Unit::create(['code' => 'UNT-01', 'name' => 'PCS', 'symbol' => 'pcs', 'is_active' => true]);

        // Product: min 10, target on hand 2.0000 -> net need 8.0000
        $this->product = Product::create([
            'sku' => 'PRD-FRZ-001',
            'name' => 'Produk Freeze Test',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'minimum_stock' => '10.00',
            'is_active' => true,
        ]);
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->targetLocation->id,
            'quantity' => '2.0000',
        ]);
    }

    public function test_frozen_source_warehouse_is_excluded_from_candidate_allocations(): void
    {
        // Frozen source has massive surplus 50.0000 (balance 60.0000)
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->frozenSource->id,
            'quantity' => '60.0000',
        ]);
        DB::table('inventory_location_locks')->updateOrInsert(
            ['location_id' => $this->frozenSource->id],
            [
                'is_frozen' => true,
                'frozen_by_opname_id' => null,
                'frozen_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Unfrozen source has surplus 5.0000 (balance 15.0000)
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->unfrozenSource->id,
            'quantity' => '15.0000',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->targetLocation->id)
            ->assertStatus(200);

        $row = $response->json('data.data.0');
        // Net need = 8.0000, only Unfrozen source available (5.0000) -> MIXED (internal 5, external 3)
        $this->assertEquals('MIXED', $row['recommendation_type']);
        $this->assertEquals('5.0000', $row['internal_replenishment_quantity']);
        $this->assertEquals('3.0000', $row['external_reorder_quantity']);
        $this->assertCount(1, $row['source_allocations']);
        $this->assertEquals($this->unfrozenSource->id, $row['source_allocations'][0]['source_location_id']);

        // Assert frozen source is not listed
        $sourceIds = collect($row['source_allocations'])->pluck('source_location_id')->all();
        $this->assertNotContains($this->frozenSource->id, $sourceIds);
    }

    public function test_frozen_target_warehouse_is_non_actionable_with_blocked_reason(): void
    {
        // Freeze the target location
        DB::table('inventory_location_locks')->updateOrInsert(
            ['location_id' => $this->targetLocation->id],
            [
                'is_frozen' => true,
                'frozen_by_opname_id' => null,
                'frozen_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Unfrozen source has surplus 10.0000
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->unfrozenSource->id,
            'quantity' => '20.0000',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->targetLocation->id)
            ->assertStatus(200);

        $row = $response->json('data.data.0');
        $this->assertTrue($row['target_is_frozen']);
        $this->assertFalse($row['actionable']);
        $this->assertEquals('TARGET_LOCATION_FROZEN', $row['blocked_reason']);
    }
}
