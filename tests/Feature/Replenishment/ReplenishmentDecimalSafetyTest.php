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

class ReplenishmentDecimalSafetyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Location $targetLocation;

    private Location $sourceLocation;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->targetLocation = Location::create(['code' => 'WH-TGT', 'name' => 'Gudang Target', 'is_active' => true]);
        $this->sourceLocation = Location::create(['code' => 'WH-SRC', 'name' => 'Gudang Sumber', 'is_active' => true]);

        $this->admin = User::factory()->create();
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole);
        $this->admin->locations()->attach([$this->targetLocation->id, $this->sourceLocation->id]);

        $cat = Category::create(['code' => 'CAT-01', 'name' => 'Kategori 1', 'is_active' => true]);
        $unit = Unit::create(['code' => 'UNT-01', 'name' => 'PCS', 'symbol' => 'pcs', 'is_active' => true]);

        // Precision 4 decimals: min 9999999.99, target on hand 0.0001
        $this->product = Product::create([
            'sku' => 'PRD-DEC-001',
            'name' => 'Produk Desimal Presisi',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'minimum_stock' => '100.00',
            'is_active' => true,
        ]);
    }

    public function test_fractional_quantities_maintain_exact_4_decimal_precision(): void
    {
        // Target: min 100.0000, on hand 33.3333 -> shortage 66.6667
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->targetLocation->id,
            'quantity' => '33.3333',
        ]);

        // Source: min 100.0000, on hand 125.1234 -> surplus 25.1234
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->sourceLocation->id,
            'quantity' => '125.1234',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->targetLocation->id)
            ->assertStatus(200);

        $row = $response->json('data.data.0');
        $this->assertEquals('100.0000', $row['minimum_stock']);
        $this->assertEquals('33.3333', $row['on_hand_quantity']);
        $this->assertEquals('66.6667', $row['gross_shortage_quantity']);
        $this->assertEquals('66.6667', $row['net_replenishment_need']);
        $this->assertEquals('MIXED', $row['recommendation_type']);
        $this->assertEquals('25.1234', $row['internal_replenishment_quantity']);
        $this->assertEquals('41.5433', $row['external_reorder_quantity']);

        $alloc = $row['source_allocations'][0];
        $this->assertEquals('125.1234', $alloc['source_on_hand_quantity']);
        $this->assertEquals('25.1234', $alloc['available_surplus_quantity']);
        $this->assertEquals('25.1234', $alloc['suggested_transfer_quantity']);
    }
}
