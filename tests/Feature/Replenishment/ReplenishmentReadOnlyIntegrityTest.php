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

class ReplenishmentReadOnlyIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->location = Location::create(['code' => 'WH-READONLY', 'name' => 'Gudang Read Only', 'is_active' => true]);

        $this->admin = User::factory()->create();
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole);
        $this->admin->locations()->attach($this->location);

        $cat = Category::create(['code' => 'CAT-01', 'name' => 'Kategori 1', 'is_active' => true]);
        $unit = Unit::create(['code' => 'UNT-01', 'name' => 'PCS', 'symbol' => 'pcs', 'is_active' => true]);

        $product = Product::create([
            'sku' => 'PRD-RO-001',
            'name' => 'Produk RO Test',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'minimum_stock' => '10.00',
            'is_active' => true,
        ]);

        InventoryBalance::create([
            'product_id' => $product->id,
            'location_id' => $this->location->id,
            'quantity' => '3.0000',
        ]);
    }

    public function test_repeated_recommendation_requests_cause_exact_zero_database_mutations(): void
    {
        // Snapshot initial state
        $initialBalances = DB::table('inventory_balances')->get()->toArray();
        $initialMovements = DB::table('stock_movements')->get()->toArray();
        $initialTransfers = DB::table('stock_transfers')->get()->toArray();
        $initialTransferItems = DB::table('stock_transfer_items')->get()->toArray();
        $initialLocks = DB::table('inventory_location_locks')->get()->toArray();

        // Perform 5 consecutive calls
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->admin, 'sanctum')
                ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->location->id)
                ->assertStatus(200);

            $this->actingAs($this->admin, 'sanctum')
                ->getJson('/api/v1/replenishment-recommendations/filter-options')
                ->assertStatus(200);
        }

        // Snapshot state after execution
        $afterBalances = DB::table('inventory_balances')->get()->toArray();
        $afterMovements = DB::table('stock_movements')->get()->toArray();
        $afterTransfers = DB::table('stock_transfers')->get()->toArray();
        $afterTransferItems = DB::table('stock_transfer_items')->get()->toArray();
        $afterLocks = DB::table('inventory_location_locks')->get()->toArray();

        $this->assertEquals($initialBalances, $afterBalances, 'Inventory balances must remain completely untouched.');
        $this->assertEquals($initialMovements, $afterMovements, 'Stock movements must remain completely untouched.');
        $this->assertEquals($initialTransfers, $afterTransfers, 'Stock transfers must remain completely untouched.');
        $this->assertEquals($initialTransferItems, $afterTransferItems, 'Stock transfer items must remain completely untouched.');
        $this->assertEquals($initialLocks, $afterLocks, 'Location locks must remain completely untouched.');
    }
}
