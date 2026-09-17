<?php

namespace Tests\Feature\ProductSerial;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Enums\StockCondition;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Location\Enums\LocationType;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\ProductSerial\Enums\SerialMovementType;
use App\Features\ProductSerial\Enums\SerialStatus;
use App\Features\ProductSerial\Models\ProductSerial;
use App\Features\ProductSerial\Models\ProductSerialMovement;
use App\Features\ProductSerial\Services\ProductSerialService;
use App\Features\Store\Models\Store;
use App\Features\Unit\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSerialTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $technician;
    protected Role $adminRole;
    protected Role $technicianRole;
    protected Product $productEdc;
    protected Location $warehouse;
    protected Location $technicianLocation;
    protected Store $storeAlpha;
    protected Store $storeBeta;

    protected function setUp(): void
    {
        parent::setUp();

        $uid = uniqid();

        // Seed roles & permissions
        $this->adminRole = Role::firstOrCreate(
            ['code' => RoleCode::ADMIN->value],
            ['name' => 'Administrator']
        );

        $this->technicianRole = Role::firstOrCreate(
            ['code' => RoleCode::FIELD_TECHNICIAN->value],
            ['name' => 'Teknisi Lapangan']
        );

        $viewPerm = Permission::firstOrCreate(
            ['code' => PermissionCode::PRODUCT_SERIALS_VIEW->value],
            ['name' => 'Melihat Data Serial Number', 'group' => 'product_serials']
        );

        $managePerm = Permission::firstOrCreate(
            ['code' => PermissionCode::PRODUCT_SERIALS_MANAGE->value],
            ['name' => 'Mengelola Data Serial Number', 'group' => 'product_serials']
        );

        $allocOwnPerm = Permission::firstOrCreate(
            ['code' => PermissionCode::STORE_ALLOCATIONS_CREATE_OWN->value],
            ['name' => 'Membuat Alokasi Toko Sendiri', 'group' => 'store_allocations']
        );

        $this->adminRole->permissions()->syncWithoutDetaching([$viewPerm->id, $managePerm->id, $allocOwnPerm->id]);
        $this->technicianRole->permissions()->syncWithoutDetaching([$viewPerm->id, $allocOwnPerm->id]);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($this->adminRole->id);

        $this->technician = User::factory()->create(['is_active' => true]);
        $this->technician->roles()->attach($this->technicianRole->id);

        $unit = Unit::create(['name' => 'Unit', 'code' => 'UNT-'.$uid, 'symbol' => 'PCS']);
        $cat = Category::create(['name' => 'EDC Terminal', 'code' => 'CAT-'.$uid]);

        $this->productEdc = Product::create([
            'sku' => 'EDC-A920-'.$uid,
            'name' => 'Pax A920 Smart POS',
            'unit_id' => $unit->id,
            'category_id' => $cat->id,
            'is_active' => true,
        ]);

        $this->warehouse = Location::create([
            'code' => 'LOC-WH-'.$uid,
            'name' => 'Gudang Induk',
            'type' => LocationType::MAIN_WAREHOUSE->value,
            'is_active' => true,
        ]);

        $this->technicianLocation = Location::create([
            'code' => 'LOC-TECH-'.$uid,
            'name' => 'Bagasi Teknisi',
            'type' => LocationType::FIELD_PERSONNEL->value,
            'user_id' => $this->technician->id,
            'is_active' => true,
        ]);

        $this->storeAlpha = Store::create([
            'code' => 'STR-ALP-'.$uid,
            'name' => 'Indomaret Point Alpha',
            'address' => 'Jl. Sudirman 1',
            'is_active' => true,
        ]);

        $this->storeBeta = Store::create([
            'code' => 'STR-BET-'.$uid,
            'name' => 'Alfamart Beta',
            'address' => 'Jl. Thamrin 2',
            'is_active' => true,
        ]);

        // Stock balance in tech bag
        InventoryBalance::create([
            'location_id' => $this->technicianLocation->id,
            'product_id' => $this->productEdc->id,
            'condition' => StockCondition::GOOD->value,
            'quantity' => '10.0000',
        ]);
    }

    public function test_can_auto_register_serial_on_the_fly_during_store_allocation(): void
    {
        $sn = 'SN-NEW-9999';

        $payload = [
            'store_id' => $this->storeAlpha->id,
            'technician_user_id' => $this->technician->id,
            'technician_location_id' => $this->technicianLocation->id,
            'items' => [
                [
                    'product_id' => $this->productEdc->id,
                    'quantity' => 1,
                    'serial_number' => $sn,
                ],
            ],
        ];

        $response = $this->actingAs($this->technician)->postJson('/api/v1/store-allocations', $payload);
        $response->assertStatus(201);

        // Verify serial record was auto-registered
        $serial = ProductSerial::where('serial_number', $sn)->first();
        $this->assertNotNull($serial);
        $this->assertEquals($this->productEdc->id, $serial->product_id);
        $this->assertEquals(SerialStatus::INSTALLED, $serial->status);
        $this->assertEquals(StockCondition::GOOD, $serial->current_condition);
        $this->assertEquals($this->storeAlpha->id, $serial->current_store_id);
        $this->assertNull($serial->current_location_id);

        // Verify movement history was recorded
        $movement = ProductSerialMovement::where('product_serial_id', $serial->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals(SerialMovementType::STORE_ALLOCATION_INSTALL, $movement->movement_type);
        $this->assertEquals($this->technicianLocation->id, $movement->from_location_id);
        $this->assertEquals($this->storeAlpha->id, $movement->to_store_id);
    }

    public function test_can_track_pulled_defective_unit_from_store(): void
    {
        $snInstall = 'SN-GOOD-1111';
        $snPull = 'SN-BROKEN-2222';

        $payload = [
            'store_id' => $this->storeAlpha->id,
            'technician_user_id' => $this->technician->id,
            'technician_location_id' => $this->technicianLocation->id,
            'items' => [
                [
                    'product_id' => $this->productEdc->id,
                    'quantity' => 1,
                    'serial_number' => $snInstall,
                    'pulled_product_id' => $this->productEdc->id,
                    'pulled_quantity' => 1,
                    'pulled_serial_number' => $snPull,
                    'defective_reason' => 'Layar LCD bergaris & mati total',
                ],
            ],
        ];

        $response = $this->actingAs($this->technician)->postJson('/api/v1/store-allocations', $payload);
        $response->assertStatus(201);

        // Pulled unit should be recorded as DEFECTIVE in technician bag
        $pulledSerial = ProductSerial::where('serial_number', $snPull)->first();
        $this->assertNotNull($pulledSerial);
        $this->assertEquals(SerialStatus::DEFECTIVE, $pulledSerial->status);
        $this->assertEquals(StockCondition::DEFECTIVE, $pulledSerial->current_condition);
        $this->assertEquals($this->technicianLocation->id, $pulledSerial->current_location_id);
        $this->assertNull($pulledSerial->current_store_id);
        $this->assertEquals('Layar LCD bergaris & mati total', $pulledSerial->notes);

        $movement = ProductSerialMovement::where('product_serial_id', $pulledSerial->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals(SerialMovementType::STORE_ALLOCATION_PULL, $movement->movement_type);
        $this->assertEquals($this->storeAlpha->id, $movement->from_store_id);
        $this->assertEquals($this->technicianLocation->id, $movement->to_location_id);
        $this->assertEquals(SerialStatus::DEFECTIVE, $movement->to_status);
    }

    public function test_cannot_install_serial_that_is_already_installed_at_another_store(): void
    {
        // Pre-register serial as installed at store Alpha
        $sn = 'SN-ALREADY-INSTALLED';
        ProductSerial::create([
            'serial_number' => $sn,
            'product_id' => $this->productEdc->id,
            'current_location_id' => null,
            'current_store_id' => $this->storeAlpha->id,
            'current_condition' => StockCondition::GOOD,
            'status' => SerialStatus::INSTALLED,
        ]);

        // Attempt to install same serial at store Beta without pulling first
        $payload = [
            'store_id' => $this->storeBeta->id,
            'technician_user_id' => $this->technician->id,
            'technician_location_id' => $this->technicianLocation->id,
            'items' => [
                [
                    'product_id' => $this->productEdc->id,
                    'quantity' => 1,
                    'serial_number' => $sn,
                ],
            ],
        ];

        $response = $this->actingAs($this->technician)->postJson('/api/v1/store-allocations', $payload);
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'success' => false,
        ]);
        $this->assertStringContainsString('masih tercatat terpasang di toko lain', $response->json('message'));
    }

    public function test_cannot_submit_duplicate_serial_in_same_document(): void
    {
        $payload = [
            'store_id' => $this->storeAlpha->id,
            'technician_user_id' => $this->technician->id,
            'technician_location_id' => $this->technicianLocation->id,
            'items' => [
                [
                    'product_id' => $this->productEdc->id,
                    'quantity' => 1,
                    'serial_number' => 'SN-DUPLICATE-SAME-DOC',
                ],
                [
                    'product_id' => $this->productEdc->id,
                    'quantity' => 1,
                    'serial_number' => 'SN-DUPLICATE-SAME-DOC',
                ],
            ],
        ];

        $response = $this->actingAs($this->technician)->postJson('/api/v1/store-allocations', $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.1.serial_number']);
    }

    public function test_can_paginate_and_filter_serials_via_api(): void
    {
        ProductSerial::create([
            'serial_number' => 'SN-FILTER-001',
            'product_id' => $this->productEdc->id,
            'current_location_id' => $this->warehouse->id,
            'current_condition' => StockCondition::GOOD,
            'status' => SerialStatus::IN_STOCK,
        ]);

        ProductSerial::create([
            'serial_number' => 'SN-FILTER-002',
            'product_id' => $this->productEdc->id,
            'current_location_id' => null,
            'current_store_id' => $this->storeAlpha->id,
            'current_condition' => StockCondition::GOOD,
            'status' => SerialStatus::INSTALLED,
        ]);

        $resAll = $this->actingAs($this->admin)->getJson('/api/v1/product-serials');
        $resAll->assertStatus(200);
        $this->assertCount(2, $resAll->json('data'));

        // Filter by status=INSTALLED
        $resInstalled = $this->actingAs($this->admin)->getJson('/api/v1/product-serials?status=INSTALLED');
        $resInstalled->assertStatus(200);
        $this->assertCount(1, $resInstalled->json('data'));
        $this->assertEquals('SN-FILTER-002', $resInstalled->json('data.0.serial_number'));

        // Filter by search query
        $resSearch = $this->actingAs($this->admin)->getJson('/api/v1/product-serials?search=001');
        $resSearch->assertStatus(200);
        $this->assertCount(1, $resSearch->json('data'));
        $this->assertEquals('SN-FILTER-001', $resSearch->json('data.0.serial_number'));
    }

    public function test_can_lookup_serial_by_barcode_qr(): void
    {
        $sn = 'SN-LOOKUP-FAST';
        $serial = ProductSerial::create([
            'serial_number' => $sn,
            'product_id' => $this->productEdc->id,
            'current_location_id' => null,
            'current_store_id' => $this->storeAlpha->id,
            'current_condition' => StockCondition::GOOD,
            'status' => SerialStatus::INSTALLED,
        ]);

        $response = $this->actingAs($this->technician)->getJson('/api/v1/product-serials/lookup?sn='.$sn);
        $response->assertStatus(200);
        $response->assertJsonPath('data.serial_number', $sn);
        $response->assertJsonPath('data.status', 'INSTALLED');
        $response->assertJsonPath('data.current_store.name', $this->storeAlpha->name);
    }

    public function test_lookup_returns_404_for_unregistered_serial(): void
    {
        $response = $this->actingAs($this->technician)->getJson('/api/v1/product-serials/lookup?sn=UNKNOWN-999');
        $response->assertStatus(404);
        $response->assertJsonFragment(['success' => false]);
    }

    public function test_can_view_serial_detail_with_movement_history(): void
    {
        $service = app(ProductSerialService::class);
        $serial = $service->findOrCreateSerial(
            serialNumber: 'SN-TIMELINE-001',
            productId: $this->productEdc->id,
            locationId: $this->warehouse->id,
            condition: StockCondition::GOOD,
            status: SerialStatus::IN_STOCK
        );

        // Movement 1: Transfer from warehouse to technician
        $service->recordTransfer(
            serialNumber: 'SN-TIMELINE-001',
            fromLocationId: $this->warehouse->id,
            toLocationId: $this->technicianLocation->id,
            condition: StockCondition::GOOD,
            notes: 'Diambil teknisi untuk persiapan kunjungan'
        );

        // Movement 2: Install at Store Alpha
        $service->recordInstallation(
            serialNumber: 'SN-TIMELINE-001',
            productId: $this->productEdc->id,
            storeId: $this->storeAlpha->id,
            technicianLocationId: $this->technicianLocation->id
        );

        $response = $this->actingAs($this->admin)->getJson("/api/v1/product-serials/{$serial->id}");
        $response->assertStatus(200);
        $response->assertJsonPath('data.serial_number', 'SN-TIMELINE-001');
        $this->assertCount(2, $response->json('data.movements'));
    }

    public function test_unauthenticated_and_unauthorized_access_is_blocked(): void
    {
        // Unauthenticated
        $this->getJson('/api/v1/product-serials')->assertStatus(401);

        // User without permission
        $plainUser = User::factory()->create(['is_active' => true]);
        $this->actingAs($plainUser)->getJson('/api/v1/product-serials')->assertStatus(403);
    }
}
