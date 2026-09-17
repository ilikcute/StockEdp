<?php

namespace Tests\Feature\Reporting;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\StockCondition;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Location\Enums\LocationType;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupedInventoryBalanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $technician;
    protected Product $product;
    protected Location $mainWarehouse;
    protected Location $technicianLocation;
    protected Location $damagedStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $uid = uniqid();

        $adminRole = Role::firstOrCreate(['code' => RoleCode::ADMIN->value], ['name' => 'Administrator']);
        $viewPerm = Permission::firstOrCreate(
            ['code' => PermissionCode::REPORTS_INVENTORY_BALANCE_VIEW->value],
            ['name' => 'Melihat Saldo Stok', 'group' => 'reports']
        );
        $adminRole->permissions()->syncWithoutDetaching([$viewPerm->id]);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole->id);

        $this->technician = User::factory()->create([
            'name' => 'Budi Teknisi EDP',
            'username' => 'budi_edp_'.$uid,
            'is_active' => true,
        ]);

        $unit = Unit::create(['name' => 'Unit', 'code' => 'UNT-'.$uid, 'symbol' => 'PCS']);
        $category = Category::create(['name' => 'Terminal EDC POS', 'code' => 'CAT-'.$uid]);

        $this->product = Product::create([
            'sku' => 'POS-PAX-'.$uid,
            'barcode' => '899000111222',
            'name' => 'Pax A920 Smart POS',
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'unit_price' => 2500000,
            'minimum_stock' => 5,
            'is_active' => true,
        ]);

        $this->mainWarehouse = Location::create([
            'code' => 'WH-MAIN-'.$uid,
            'name' => 'Gudang Induk Jakarta',
            'type' => LocationType::MAIN_WAREHOUSE->value,
            'is_active' => true,
        ]);

        $this->technicianLocation = Location::create([
            'code' => 'TECH-BUDI-'.$uid,
            'name' => 'Bagasi Operasional Budi',
            'type' => LocationType::FIELD_PERSONNEL->value,
            'user_id' => $this->technician->id,
            'is_active' => true,
        ]);

        $this->damagedStorage = Location::create([
            'code' => 'DMG-STORAGE-'.$uid,
            'name' => 'Gudang Isolasi Rusak',
            'type' => LocationType::DAMAGED_STORAGE->value,
            'is_active' => true,
        ]);
    }

    public function test_can_fetch_global_grouped_balances_with_location_breakdown(): void
    {
        // Setup balances:
        // Main Warehouse: 10 GOOD
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->mainWarehouse->id,
            'condition' => StockCondition::GOOD->value,
            'quantity' => '10.0000',
        ]);

        // Technician Budi: 3 GOOD
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->technicianLocation->id,
            'condition' => StockCondition::GOOD->value,
            'quantity' => '3.0000',
        ]);

        // Damaged Storage: 2 DEFECTIVE
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->damagedStorage->id,
            'condition' => StockCondition::DEFECTIVE->value,
            'quantity' => '2.0000',
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/reports/inventory-balances?view_mode=grouped');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $data = $response->json('data.data') ?? $response->json('data');
        $this->assertCount(2, $data); // 1 for GOOD, 1 for DEFECTIVE

        // Verify GOOD row
        $goodRow = collect($data)->firstWhere('condition', 'GOOD');
        $this->assertNotNull($goodRow);
        $this->assertEquals('13.0000', $goodRow['total_quantity']);
        $this->assertEquals(32500000, $goodRow['total_value']); // 13 * 2,500,000
        $this->assertEquals(2, $goodRow['locations_count']);
        $this->assertCount(2, $goodRow['locations']);

        // Check location breakdown details
        $techLocDetail = collect($goodRow['locations'])->firstWhere('location_id', $this->technicianLocation->id);
        $this->assertNotNull($techLocDetail);
        $this->assertEquals('3.0000', $techLocDetail['quantity']);
        $this->assertEquals('Budi Teknisi EDP', $techLocDetail['personnel_name']);
        $this->assertTrue($techLocDetail['is_field_personnel']);

        // Verify DEFECTIVE row
        $defectiveRow = collect($data)->firstWhere('condition', 'DEFECTIVE');
        $this->assertNotNull($defectiveRow);
        $this->assertEquals('2.0000', $defectiveRow['total_quantity']);
        $this->assertEquals(5000000, $defectiveRow['total_value']);
        $this->assertEquals(1, $defectiveRow['locations_count']);
        $this->assertCount(1, $defectiveRow['locations']);
        $this->assertEquals($this->damagedStorage->id, $defectiveRow['locations'][0]['location_id']);
    }

    public function test_can_search_in_grouped_mode(): void
    {
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->mainWarehouse->id,
            'condition' => StockCondition::GOOD->value,
            'quantity' => '5.0000',
        ]);

        // Search match
        $resMatch = $this->actingAs($this->admin)
            ->getJson('/api/v1/reports/inventory-balances?view_mode=grouped&search=Pax+A920');
        $resMatch->assertStatus(200);
        $dataMatch = $resMatch->json('data.data') ?? $resMatch->json('data');
        $this->assertCount(1, $dataMatch);

        // Search no match
        $resNoMatch = $this->actingAs($this->admin)
            ->getJson('/api/v1/reports/inventory-balances?view_mode=grouped&search=NON_EXISTENT_SKU');
        $resNoMatch->assertStatus(200);
        $dataNoMatch = $resNoMatch->json('data.data') ?? $resNoMatch->json('data');
        $this->assertCount(0, $dataNoMatch);
    }
}
