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

class ReplenishmentLowStockParityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Location $targetLocation;

    private Product $productA;

    private Product $productB;

    private Product $productWithoutBalance;

    private Product $inactiveProduct;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->targetLocation = Location::create([
            'code' => 'WH-PARITY',
            'name' => 'Gudang Parity',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create();
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole);
        $this->admin->locations()->attach($this->targetLocation);

        $cat = Category::create(['code' => 'CAT-01', 'name' => 'Kategori 1', 'is_active' => true]);
        $unit = Unit::create(['code' => 'UNT-01', 'name' => 'PCS', 'symbol' => 'pcs', 'is_active' => true]);

        // Product A: min 10, balance 4.5000 -> shortage 5.5000
        $this->productA = Product::create([
            'sku' => 'PRD-PARITY-A',
            'name' => 'Produk A',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'minimum_stock' => '10.00',
            'is_active' => true,
        ]);
        InventoryBalance::create([
            'product_id' => $this->productA->id,
            'location_id' => $this->targetLocation->id,
            'quantity' => '4.5000',
        ]);

        // Product B: min 20, balance 0.0000 -> shortage 20.0000 (Critical)
        $this->productB = Product::create([
            'sku' => 'PRD-PARITY-B',
            'name' => 'Produk B',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'minimum_stock' => '20.00',
            'is_active' => true,
        ]);
        InventoryBalance::create([
            'product_id' => $this->productB->id,
            'location_id' => $this->targetLocation->id,
            'quantity' => '0.0000',
        ]);

        // Product without balance: min 15, no balance row -> on_hand 0.0000, shortage 15.0000
        $this->productWithoutBalance = Product::create([
            'sku' => 'PRD-PARITY-NOBAL',
            'name' => 'Produk Tanpa Balance',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'minimum_stock' => '15.00',
            'is_active' => true,
        ]);

        // Inactive product: should be excluded
        $this->inactiveProduct = Product::create([
            'sku' => 'PRD-PARITY-INACTIVE',
            'name' => 'Produk Inaktif',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'minimum_stock' => '10.00',
            'is_active' => false,
        ]);
    }

    public function test_replenishment_gross_shortage_has_exact_parity_with_low_stock_report(): void
    {
        // 1. Query canonical Low Stock report
        $lowStockRes = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/low-stock?location_id='.$this->targetLocation->id.'&per_page=50')
            ->assertStatus(200);

        $lowStockItems = collect($lowStockRes->json('data.data'));

        // 2. Query Replenishment recommendations
        $replenishmentRes = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->targetLocation->id.'&per_page=50')
            ->assertStatus(200);

        $replenishmentItems = collect($replenishmentRes->json('data.data'));

        $this->assertEquals($lowStockItems->count(), $replenishmentItems->count());

        foreach ($lowStockItems as $lsItem) {
            $matchingRepl = $replenishmentItems->firstWhere('product_id', $lsItem['product_id']);
            $this->assertNotNull($matchingRepl, "Product ID {$lsItem['product_id']} must exist in replenishment recommendations.");

            $this->assertEquals(
                (string) $lsItem['shortage_quantity'],
                (string) $matchingRepl['gross_shortage_quantity'],
                "Gross shortage must match exact string parity for product {$lsItem['product_id']}"
            );
            $this->assertEquals(
                (string) $lsItem['on_hand_quantity'],
                (string) $matchingRepl['on_hand_quantity'],
                "On hand quantity must match exact string parity for product {$lsItem['product_id']}"
            );
        }
    }

    public function test_missing_balance_row_is_treated_as_zero_on_hand_and_full_shortage(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->targetLocation->id)
            ->assertStatus(200);

        $item = collect($response->json('data.data'))->firstWhere('product_id', $this->productWithoutBalance->id);

        $this->assertNotNull($item);
        $this->assertEquals('0.0000', $item['on_hand_quantity']);
        $this->assertEquals('15.0000', $item['minimum_stock']);
        $this->assertEquals('15.0000', $item['gross_shortage_quantity']);
        $this->assertEquals('CRITICAL', $item['priority']);
    }

    public function test_inactive_products_are_excluded(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->targetLocation->id)
            ->assertStatus(200);

        $item = collect($response->json('data.data'))->firstWhere('product_id', $this->inactiveProduct->id);
        $this->assertNull($item);
    }
}
