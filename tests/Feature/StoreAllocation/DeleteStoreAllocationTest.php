<?php

namespace Tests\Feature\StoreAllocation;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\StockCondition;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Location\Enums\LocationType;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\ProductSerial\Enums\SerialStatus;
use App\Features\ProductSerial\Models\ProductSerial;
use App\Features\Store\Models\Store;
use App\Features\StoreAllocation\Actions\CreateStoreAllocationAction;
use App\Features\StoreAllocation\Models\StoreAllocation;
use App\Features\Unit\Models\Unit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DeleteStoreAllocationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected User $technician;

    protected Location $technicianLocation;

    protected Store $store;

    protected Product $productNew;

    protected Product $productPulled;

    protected function setUp(): void
    {
        parent::setUp();

        $uid = substr(uniqid(), -5);
        $category = Category::firstOrCreate(['code' => 'CAT-DEL-'.$uid], ['name' => 'Hardware Delete', 'is_active' => true]);
        $unit = Unit::firstOrCreate(['code' => 'PCS-DEL-'.$uid], ['name' => 'Pieces', 'symbol' => 'PCS', 'is_active' => true]);

        $this->productNew = Product::create([
            'sku' => 'SKU-NEW-'.$uid,
            'name' => 'EDC Terminal New',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $this->productPulled = Product::create([
            'sku' => 'SKU-PULL-'.$uid,
            'name' => 'EDC Terminal Pulled',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $adminRole = Role::firstOrCreate(
            ['code' => RoleCode::ADMIN->value],
            ['name' => 'Administrator']
        );

        $techRole = Role::firstOrCreate(
            ['code' => RoleCode::FIELD_TECHNICIAN->value],
            ['name' => 'Teknisi Lapangan']
        );

        $this->admin = User::factory()->create();
        $this->admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $this->technician = User::factory()->create();
        $this->technician->roles()->syncWithoutDetaching([$techRole->id]);

        $this->technicianLocation = Location::create([
            'code' => 'LOC-TECH-'.$uid,
            'name' => 'Bagasi Teknisi '.$uid,
            'type' => LocationType::FIELD_PERSONNEL->value,
            'user_id' => $this->technician->id,
            'is_active' => true,
        ]);

        $this->store = Store::create([
            'code' => 'STR-'.$uid,
            'name' => 'Toko Delete Test '.$uid,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_delete_store_allocation_and_rollback_stock_and_serials(): void
    {
        // Setup initial balance: technician has 5 GOOD units
        InventoryBalance::create([
            'product_id' => $this->productNew->id,
            'location_id' => $this->technicianLocation->id,
            'condition' => StockCondition::GOOD->value,
            'quantity' => '5.0000',
        ]);

        $serialSN = 'SN-INSTALL-'.uniqid();
        $pulledSN = 'SN-PULLED-'.uniqid();

        // Create allocation
        $action = app(CreateStoreAllocationAction::class);
        $allocation = $action->execute([
            'technician_user_id' => $this->technician->id,
            'technician_location_id' => $this->technicianLocation->id,
            'store_id' => $this->store->id,
            'items' => [
                [
                    'product_id' => $this->productNew->id,
                    'quantity' => 1,
                    'serial_number' => $serialSN,
                    'pulled_product_id' => $this->productPulled->id,
                    'pulled_quantity' => 1,
                    'pulled_serial_number' => $pulledSN,
                    'defective_reason' => 'Rusak Layar',
                ],
            ],
        ], $this->admin->id);

        // Verify state after creation:
        // Technician GOOD balance should be 4.0000
        /** @var InventoryBalance $goodBal */
        $goodBal = InventoryBalance::where('product_id', $this->productNew->id)
            ->where('location_id', $this->technicianLocation->id)
            ->where('condition', StockCondition::GOOD->value)
            ->first();
        $this->assertEquals('4.0000', $goodBal->quantity);

        // Technician DEFECTIVE balance should be 1.0000
        /** @var InventoryBalance $defBal */
        $defBal = InventoryBalance::where('product_id', $this->productPulled->id)
            ->where('location_id', $this->technicianLocation->id)
            ->where('condition', StockCondition::DEFECTIVE->value)
            ->first();
        $this->assertEquals('1.0000', $defBal->quantity);

        // Serial check: serialSN is INSTALLED at store
        /** @var ProductSerial $serial */
        $serial = ProductSerial::where('serial_number', $serialSN)->first();
        $this->assertEquals(SerialStatus::INSTALLED, $serial->status);
        $this->assertEquals($this->store->id, $serial->current_store_id);

        // Now Admin deletes the allocation
        $response = $this->actingAs($this->admin)->deleteJson("/api/v1/store-allocations/{$allocation->id}");
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Allocation document should be deleted
        $this->assertDatabaseMissing('store_allocations', ['id' => $allocation->id]);

        // Stock balances rolled back:
        $goodBal->refresh();
        $this->assertEquals('5.0000', $goodBal->quantity, 'GOOD balance must be refunded back to 5.0000');

        $defBal->refresh();
        $this->assertEquals('0.0000', $defBal->quantity, 'DEFECTIVE pulled balance must be deducted back to 0.0000');

        // Serial status rolled back:
        $serial->refresh();
        $this->assertEquals(SerialStatus::IN_STOCK, $serial->status);
        $this->assertNull($serial->current_store_id);
        $this->assertEquals($this->technicianLocation->id, $serial->current_location_id);
    }

    public function test_non_admin_cannot_delete_store_allocation(): void
    {
        InventoryBalance::create([
            'product_id' => $this->productNew->id,
            'location_id' => $this->technicianLocation->id,
            'condition' => StockCondition::GOOD->value,
            'quantity' => '2.0000',
        ]);

        $action = app(CreateStoreAllocationAction::class);
        $allocation = $action->execute([
            'technician_user_id' => $this->technician->id,
            'technician_location_id' => $this->technicianLocation->id,
            'store_id' => $this->store->id,
            'items' => [
                [
                    'product_id' => $this->productNew->id,
                    'quantity' => 1,
                ],
            ],
        ], $this->admin->id);

        $response = $this->actingAs($this->technician)->deleteJson("/api/v1/store-allocations/{$allocation->id}");
        $response->assertStatus(403);
    }
}
