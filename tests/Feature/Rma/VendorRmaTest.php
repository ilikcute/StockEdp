<?php

namespace Tests\Feature\Rma;

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
use App\Features\ProductSerial\Enums\SerialMovementType;
use App\Features\ProductSerial\Enums\SerialStatus;
use App\Features\ProductSerial\Models\ProductSerial;
use App\Features\Rma\Enums\RmaStatus;
use App\Features\Rma\Models\VendorRma;
use App\Features\Supplier\Models\Supplier;
use App\Features\Unit\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorRmaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Role $adminRole;
    protected Product $product;
    protected Location $damagedStorage;
    protected Location $mainWarehouse;
    protected Supplier $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $uid = uniqid();

        $this->adminRole = Role::firstOrCreate(
            ['code' => RoleCode::ADMIN->value],
            ['name' => 'Administrator']
        );

        $viewPerm = Permission::firstOrCreate(
            ['code' => PermissionCode::VENDOR_RMAS_VIEW->value],
            ['name' => 'Melihat Data RMA Vendor', 'group' => 'vendor_rmas']
        );

        $managePerm = Permission::firstOrCreate(
            ['code' => PermissionCode::VENDOR_RMAS_MANAGE->value],
            ['name' => 'Mengelola RMA Vendor', 'group' => 'vendor_rmas']
        );

        $this->adminRole->permissions()->syncWithoutDetaching([$viewPerm->id, $managePerm->id]);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($this->adminRole->id);

        $unit = Unit::create(['name' => 'Unit', 'code' => 'UNT-'.$uid, 'symbol' => 'PCS']);
        $cat = Category::create(['name' => 'EDC Terminal', 'code' => 'CAT-'.$uid]);

        $this->product = Product::create([
            'sku' => 'POS-VX520-'.$uid,
            'name' => 'Verifone VX520',
            'unit_id' => $unit->id,
            'category_id' => $cat->id,
            'is_active' => true,
        ]);

        $this->damagedStorage = Location::create([
            'code' => 'LOC-DMG-'.$uid,
            'name' => 'Gudang Isolasi Rusak',
            'type' => LocationType::DAMAGED_STORAGE->value,
            'is_active' => true,
        ]);

        $this->mainWarehouse = Location::create([
            'code' => 'LOC-WH-'.$uid,
            'name' => 'Gudang Utama',
            'type' => LocationType::MAIN_WAREHOUSE->value,
            'is_active' => true,
        ]);

        $this->vendor = Supplier::create([
            'code' => 'SUP-'.$uid,
            'name' => 'PT Vendor Terminal Terpadu',
            'contact_person' => 'Budi Vendor',
            'phone' => '081234567890',
            'is_active' => true,
        ]);
    }

    public function test_can_create_draft_vendor_rma(): void
    {
        $payload = [
            'supplier_id' => $this->vendor->id,
            'origin_location_id' => $this->damagedStorage->id,
            'notes' => 'Klaim servis layar blank unit EDC',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'serial_number' => 'SN-RMA-TEST-001',
                    'quantity' => 1,
                    'fault_description' => 'Layar bergaris dan freeze saat boot',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/v1/vendor-rmas', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('vendor_rmas', [
            'supplier_id' => $this->vendor->id,
            'origin_location_id' => $this->damagedStorage->id,
            'status' => RmaStatus::DRAFT->value,
        ]);
        $this->assertDatabaseHas('vendor_rma_items', [
            'product_id' => $this->product->id,
            'serial_number' => 'SN-RMA-TEST-001',
            'fault_description' => 'Layar bergaris dan freeze saat boot',
        ]);
    }

    public function test_can_dispatch_vendor_rma_which_deducts_defective_balance_and_updates_serial_status(): void
    {
        // Setup initial defective balance
        InventoryBalance::create([
            'location_id' => $this->damagedStorage->id,
            'product_id' => $this->product->id,
            'condition' => StockCondition::DEFECTIVE->value,
            'quantity' => '2.0000',
        ]);

        // Setup tracked serial
        $serial = ProductSerial::create([
            'serial_number' => 'SN-DISPATCH-999',
            'product_id' => $this->product->id,
            'current_location_id' => $this->damagedStorage->id,
            'current_store_id' => null,
            'current_condition' => StockCondition::DEFECTIVE,
            'status' => SerialStatus::DEFECTIVE,
            'notes' => 'Unit rusak dari toko A',
        ]);

        $rma = VendorRma::create([
            'rma_number' => 'RMA-202609-0099',
            'supplier_id' => $this->vendor->id,
            'origin_location_id' => $this->damagedStorage->id,
            'status' => RmaStatus::DRAFT,
            'created_by' => $this->admin->id,
        ]);

        $rma->items()->create([
            'product_id' => $this->product->id,
            'product_serial_id' => $serial->id,
            'serial_number' => 'SN-DISPATCH-999',
            'quantity' => 1,
            'fault_description' => 'Mati total',
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/v1/vendor-rmas/{$rma->id}/dispatch");

        $response->assertStatus(200);

        // Balance should decrease from 2 to 1
        $balance = InventoryBalance::where('location_id', $this->damagedStorage->id)
            ->where('product_id', $this->product->id)
            ->where('condition', StockCondition::DEFECTIVE->value)
            ->first();
        $this->assertEquals('1.0000', $balance->quantity);

        // Serial status should become RETURNED_TO_VENDOR
        $serial->refresh();
        $this->assertEquals(SerialStatus::RETURNED_TO_VENDOR, $serial->status);
        $this->assertNull($serial->current_location_id);

        // Movement timeline should contain RMA_DISPATCH
        $this->assertDatabaseHas('product_serial_movements', [
            'product_serial_id' => $serial->id,
            'movement_type' => SerialMovementType::RMA_DISPATCH->value,
            'to_status' => SerialStatus::RETURNED_TO_VENDOR->value,
        ]);
    }

    public function test_can_complete_rma_and_return_stock_as_good_in_warehouse(): void
    {
        // Initial defective balance
        InventoryBalance::create([
            'location_id' => $this->damagedStorage->id,
            'product_id' => $this->product->id,
            'condition' => StockCondition::DEFECTIVE->value,
            'quantity' => '1.0000',
        ]);

        $rma = VendorRma::create([
            'rma_number' => 'RMA-202609-0100',
            'supplier_id' => $this->vendor->id,
            'origin_location_id' => $this->damagedStorage->id,
            'status' => RmaStatus::DRAFT,
            'created_by' => $this->admin->id,
        ]);

        $rma->items()->create([
            'product_id' => $this->product->id,
            'serial_number' => 'SN-COMPLETE-123',
            'quantity' => 1,
            'fault_description' => 'IC power rusak',
        ]);

        // Dispatch first
        $this->actingAs($this->admin)->postJson("/api/v1/vendor-rmas/{$rma->id}/dispatch")->assertStatus(200);

        // Complete: return to main warehouse as GOOD
        $response = $this->actingAs($this->admin)->postJson("/api/v1/vendor-rmas/{$rma->id}/complete", [
            'destination_location_id' => $this->mainWarehouse->id,
            'notes' => 'Unit selesai diganti IC power dan lulus QC',
        ]);

        $response->assertStatus(200);

        // GOOD balance in main warehouse should now be 1
        $goodBalance = InventoryBalance::where('location_id', $this->mainWarehouse->id)
            ->where('product_id', $this->product->id)
            ->where('condition', StockCondition::GOOD->value)
            ->first();
        $this->assertNotNull($goodBalance);
        $this->assertEquals('1.0000', $goodBalance->quantity);

        // Serial should now be IN_STOCK and GOOD in main warehouse
        $serial = ProductSerial::where('serial_number', 'SN-COMPLETE-123')->first();
        $this->assertNotNull($serial);
        $this->assertEquals(SerialStatus::IN_STOCK, $serial->status);
        $this->assertEquals(StockCondition::GOOD, $serial->current_condition);
        $this->assertEquals($this->mainWarehouse->id, $serial->current_location_id);

        // Movement timeline should contain RMA_RETURN
        $this->assertDatabaseHas('product_serial_movements', [
            'product_serial_id' => $serial->id,
            'movement_type' => SerialMovementType::RMA_RETURN->value,
            'to_status' => SerialStatus::IN_STOCK->value,
            'to_condition' => StockCondition::GOOD->value,
        ]);
    }

    public function test_cannot_submit_duplicate_serial_in_same_rma(): void
    {
        $payload = [
            'supplier_id' => $this->vendor->id,
            'origin_location_id' => $this->damagedStorage->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'serial_number' => 'SN-DUPLICATE-RMA',
                    'quantity' => 1,
                ],
                [
                    'product_id' => $this->product->id,
                    'serial_number' => 'SN-DUPLICATE-RMA',
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/v1/vendor-rmas', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.1.serial_number']);
    }
}
