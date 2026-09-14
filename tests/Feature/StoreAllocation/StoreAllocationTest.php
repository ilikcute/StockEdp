<?php

namespace Tests\Feature\StoreAllocation;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Store\Models\Store;
use App\Features\Unit\Models\Unit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StoreAllocationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $technician;

    protected Location $technicianLocation;

    protected Store $store;

    protected Product $productA;

    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $uid = substr(uniqid(), -5);
        $category = Category::firstOrCreate(['code' => 'CAT-01'], ['name' => 'Hardware', 'is_active' => true]);
        $unit = Unit::firstOrCreate(['code' => 'PCS'], ['name' => 'Pieces', 'symbol' => 'pcs', 'is_active' => true]);

        $this->productA = Product::create([
            'sku' => 'PRD-NEW-'.$uid,
            'name' => 'Printer Thermal Epson',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $this->productB = Product::create([
            'sku' => 'PRD-OLD-'.$uid,
            'name' => 'Printer Thermal Bekas',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $technicianRole = Role::firstOrCreate(
            ['code' => RoleCode::FIELD_TECHNICIAN->value],
            ['name' => 'Teknisi Lapangan']
        );

        $permission = Permission::firstOrCreate(
            ['code' => PermissionCode::STORE_ALLOCATIONS_CREATE->value],
            ['name' => 'Membuat Alokasi Toko', 'group' => 'store_allocations']
        );

        $technicianRole->permissions()->syncWithoutDetaching([$permission->id]);

        $this->technician = User::factory()->create(['is_active' => true]);
        $this->technician->roles()->attach($technicianRole->id);

        $this->technicianLocation = Location::create([
            'code' => 'LOC-TECH-'.$uid,
            'name' => 'Bagasi Teknisi Budi',
            'type' => 'FIELD_PERSONNEL',
            'user_id' => $this->technician->id,
            'is_active' => true,
        ]);

        $this->technician->locations()->attach($this->technicianLocation->id);

        $this->store = Store::create([
            'code' => 'TK-SDR-'.$uid,
            'name' => 'Toko Sudirman Branch',
            'address' => 'Jl. Jendral Sudirman No. 45',
            'is_active' => true,
        ]);

        // Seed initial balance in technician mobile bag: 5 GOOD units of Product A
        InventoryBalance::create([
            'product_id' => $this->productA->id,
            'location_id' => $this->technicianLocation->id,
            'condition' => 'GOOD',
            'quantity' => '5.0000',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_store_allocations(): void
    {
        $response = $this->getJson('/api/v1/store-allocations');
        $response->assertStatus(401);
    }

    public function test_technician_can_create_store_allocation_and_dual_inventory_updated(): void
    {
        $payload = [
            'store_id' => $this->store->id,
            'technician_user_id' => $this->technician->id,
            'technician_location_id' => $this->technicianLocation->id,
            'allocated_at' => now()->toDateString(),
            'notes' => 'Penggantian unit printer kasir rusak',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 2,
                    'serial_number' => 'SN-EPSON-999',
                    'pulled_product_id' => $this->productB->id,
                    'pulled_quantity' => 1,
                    'pulled_serial_number' => 'SN-OLD-111',
                    'defective_reason' => 'Motherboard terbakar',
                ],
            ],
        ];

        $response = $this->actingAs($this->technician)->postJson('/api/v1/store-allocations', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.store_id', $this->store->id)
            ->assertJsonPath('data.technician_user_id', $this->technician->id);

        // 1. Assert GOOD units reduced from 5 to 3
        $goodBalance = InventoryBalance::where('product_id', $this->productA->id)
            ->where('location_id', $this->technicianLocation->id)
            ->where('condition', 'GOOD')
            ->first();
        $this->assertNotNull($goodBalance);
        $this->assertEquals('3.0000', $goodBalance->quantity);

        // 2. Assert DEFECTIVE units increased from 0 to 1
        $defectiveBalance = InventoryBalance::where('product_id', $this->productB->id)
            ->where('location_id', $this->technicianLocation->id)
            ->where('condition', 'DEFECTIVE')
            ->first();
        $this->assertNotNull($defectiveBalance);
        $this->assertEquals('1.0000', $defectiveBalance->quantity);

        // 3. Assert Stock Movements recorded
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->productA->id,
            'location_id' => $this->technicianLocation->id,
            'condition' => 'GOOD',
            'movement_type' => 'STORE_ALLOCATION',
            'quantity' => '2.0000',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->productB->id,
            'location_id' => $this->technicianLocation->id,
            'condition' => 'DEFECTIVE',
            'movement_type' => 'REPLACEMENT_PULL',
            'quantity' => '1.0000',
        ]);
    }

    public function test_cannot_create_store_allocation_for_location_of_another_technician(): void
    {
        $otherTechnician = User::factory()->create(['is_active' => true]);
        $foreignLocation = Location::create([
            'code' => 'LOC-FOREIGN-'.substr(uniqid(), -5),
            'name' => 'Bagasi Teknisi Lain',
            'type' => 'FIELD_PERSONNEL',
            'user_id' => $otherTechnician->id,
            'is_active' => true,
        ]);

        $payload = [
            'store_id' => $this->store->id,
            'technician_user_id' => $this->technician->id,
            'technician_location_id' => $foreignLocation->id,
            'allocated_at' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->technician)->postJson('/api/v1/store-allocations', $payload);

        $response->assertStatus(422);
    }

    public function test_cannot_create_store_allocation_on_damaged_storage_location(): void
    {
        $damagedStorage = Location::create([
            'code' => 'LOC-DMG-'.substr(uniqid(), -5),
            'name' => 'Gudang Rusak',
            'type' => 'DAMAGED_STORAGE',
            'is_active' => true,
        ]);

        $payload = [
            'store_id' => $this->store->id,
            'technician_user_id' => $this->technician->id,
            'technician_location_id' => $damagedStorage->id,
            'allocated_at' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->technician)->postJson('/api/v1/store-allocations', $payload);

        $response->assertStatus(422);
    }

    public function test_can_create_store_allocation_from_main_warehouse_location(): void
    {
        $warehouse = Location::create([
            'code' => 'LOC-WH-'.substr(uniqid(), -5),
            'name' => 'Gudang Induk',
            'type' => 'MAIN_WAREHOUSE',
            'is_active' => true,
        ]);

        \App\Features\Inventory\Models\InventoryBalance::create([
            'product_id' => $this->productA->id,
            'location_id' => $warehouse->id,
            'quantity' => 10,
            'condition' => 'GOOD',
        ]);

        $payload = [
            'store_id' => $this->store->id,
            'technician_user_id' => $this->technician->id,
            'technician_location_id' => $warehouse->id,
            'allocated_at' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 2,
                    'serial_number' => 'SN-WH-001',
                ],
            ],
        ];

        $response = $this->actingAs($this->technician)->postJson('/api/v1/store-allocations', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('store_allocations', [
            'technician_location_id' => $warehouse->id,
            'store_id' => $this->store->id,
        ]);

        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $this->productA->id,
            'location_id' => $warehouse->id,
            'quantity' => 8,
            'condition' => 'GOOD',
        ]);
    }
}
