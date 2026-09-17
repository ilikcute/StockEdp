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

class StoreAllocationSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $technicianA;

    protected Location $locationA;

    protected User $technicianB;

    protected Location $locationB;

    protected User $supervisor;

    protected User $admin;

    protected Store $store;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $uid = substr(uniqid(), -5);
        $category = Category::firstOrCreate(['code' => 'CAT-SEC-'.$uid], ['name' => 'Security Cat', 'is_active' => true]);
        $unit = Unit::firstOrCreate(['code' => 'PCS-SEC-'.$uid], ['name' => 'Pieces', 'symbol' => 'pcs', 'is_active' => true]);

        $this->product = Product::create([
            'sku' => 'PRD-SEC-'.$uid,
            'name' => 'EDC Terminal PAX',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $this->store = Store::create([
            'code' => 'TK-SEC-'.$uid,
            'name' => 'Toko Security Branch',
            'address' => 'Jl. Security No. 1',
            'is_active' => true,
        ]);

        // Permissions
        $permView = Permission::firstOrCreate(['code' => PermissionCode::STORE_ALLOCATIONS_VIEW->value], ['name' => 'Melihat Alokasi Toko', 'group' => 'store_allocations']);
        $permCreateOwn = Permission::firstOrCreate(['code' => PermissionCode::STORE_ALLOCATIONS_CREATE_OWN->value], ['name' => 'Membuat Alokasi Toko Sendiri', 'group' => 'store_allocations']);
        $permCreateOthers = Permission::firstOrCreate(['code' => PermissionCode::STORE_ALLOCATIONS_CREATE_FOR_OTHERS->value], ['name' => 'Membuat Alokasi Toko Orang Lain', 'group' => 'store_allocations']);
        $permPost = Permission::firstOrCreate(['code' => PermissionCode::STORE_ALLOCATIONS_POST->value], ['name' => 'Memposting Alokasi Toko', 'group' => 'store_allocations']);

        // Roles
        $techRole = Role::firstOrCreate(['code' => RoleCode::FIELD_TECHNICIAN->value], ['name' => 'Teknisi Lapangan']);
        $techRole->permissions()->syncWithoutDetaching([$permView->id, $permCreateOwn->id, $permPost->id]);

        $spvRole = Role::firstOrCreate(['code' => RoleCode::INVENTORY_SUPERVISOR->value], ['name' => 'Supervisor Inventory']);
        $spvRole->permissions()->syncWithoutDetaching([$permView->id, $permCreateOwn->id, $permCreateOthers->id, $permPost->id]);

        $adminRole = Role::firstOrCreate(['code' => RoleCode::ADMIN->value], ['name' => 'Administrator']);
        $adminRole->permissions()->syncWithoutDetaching([$permView->id, $permCreateOwn->id, $permCreateOthers->id, $permPost->id]);

        // Users & Locations
        $this->technicianA = User::factory()->create(['is_active' => true]);
        $this->technicianA->roles()->attach($techRole->id);
        $this->locationA = Location::create([
            'code' => 'LOC-TECH-A-'.$uid,
            'name' => 'Bagasi Teknisi A',
            'type' => 'FIELD_PERSONNEL',
            'user_id' => $this->technicianA->id,
            'is_active' => true,
        ]);
        $this->technicianA->locations()->attach($this->locationA->id);

        $this->technicianB = User::factory()->create(['is_active' => true]);
        $this->technicianB->roles()->attach($techRole->id);
        $this->locationB = Location::create([
            'code' => 'LOC-TECH-B-'.$uid,
            'name' => 'Bagasi Teknisi B',
            'type' => 'FIELD_PERSONNEL',
            'user_id' => $this->technicianB->id,
            'is_active' => true,
        ]);
        $this->technicianB->locations()->attach($this->locationB->id);

        $this->supervisor = User::factory()->create(['is_active' => true]);
        $this->supervisor->roles()->attach($spvRole->id);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole->id);

        // Seed stock for both technicians
        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->locationA->id,
            'condition' => 'GOOD',
            'quantity' => '10.0000',
        ]);

        InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->locationB->id,
            'condition' => 'GOOD',
            'quantity' => '10.0000',
        ]);
    }

    public function test_technician_can_create_store_allocation_for_self(): void
    {
        $payload = [
            'store_id' => $this->store->id,
            'technician_user_id' => $this->technicianA->id,
            'technician_location_id' => $this->locationA->id,
            'allocated_at' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'serial_number' => 'SN-MY-001',
                ],
            ],
        ];

        $response = $this->actingAs($this->technicianA)->postJson('/api/v1/store-allocations', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('store_allocations', [
            'technician_user_id' => $this->technicianA->id,
            'technician_location_id' => $this->locationA->id,
            'store_id' => $this->store->id,
        ]);

        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $this->product->id,
            'location_id' => $this->locationA->id,
            'quantity' => '8.0000',
            'condition' => 'GOOD',
        ]);
    }

    public function test_technician_cannot_impersonate_another_technician(): void
    {
        // Technician A tries to submit an allocation for Technician B and Location B
        $payload = [
            'store_id' => $this->store->id,
            'technician_user_id' => $this->technicianB->id,
            'technician_location_id' => $this->locationB->id,
            'allocated_at' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'serial_number' => 'SN-HACK-001',
                ],
            ],
        ];

        $response = $this->actingAs($this->technicianA)->postJson('/api/v1/store-allocations', $payload);

        // Must be rejected with 403 Forbidden
        $response->assertStatus(403);

        // Victim technician B's stock MUST remain untouched
        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $this->product->id,
            'location_id' => $this->locationB->id,
            'quantity' => '10.0000',
            'condition' => 'GOOD',
        ]);

        // No allocation record created
        $this->assertDatabaseMissing('store_allocations', [
            'technician_user_id' => $this->technicianB->id,
            'store_id' => $this->store->id,
        ]);
    }

    public function test_technician_cannot_allocate_from_another_technician_location_with_own_user_id(): void
    {
        // Technician A tries to submit own user ID but targets Technician B's location
        $payload = [
            'store_id' => $this->store->id,
            'technician_user_id' => $this->technicianA->id,
            'technician_location_id' => $this->locationB->id,
            'allocated_at' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 3,
                ],
            ],
        ];

        $response = $this->actingAs($this->technicianA)->postJson('/api/v1/store-allocations', $payload);

        // Must be rejected (either 422 data mismatch or 403 location ownership check)
        $this->assertTrue(in_array($response->status(), [403, 422], true));

        // Victim technician B's stock MUST remain untouched
        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $this->product->id,
            'location_id' => $this->locationB->id,
            'quantity' => '10.0000',
            'condition' => 'GOOD',
        ]);
    }

    public function test_supervisor_can_create_store_allocation_on_behalf_of_technician(): void
    {
        $payload = [
            'store_id' => $this->store->id,
            'technician_user_id' => $this->technicianB->id,
            'technician_location_id' => $this->locationB->id,
            'allocated_at' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 4,
                    'serial_number' => 'SN-SPV-001',
                ],
            ],
        ];

        $response = $this->actingAs($this->supervisor)->postJson('/api/v1/store-allocations', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('store_allocations', [
            'technician_user_id' => $this->technicianB->id,
            'technician_location_id' => $this->locationB->id,
            'created_by' => $this->supervisor->id,
        ]);

        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $this->product->id,
            'location_id' => $this->locationB->id,
            'quantity' => '6.0000',
            'condition' => 'GOOD',
        ]);
    }

    public function test_admin_can_create_store_allocation_on_behalf_of_technician(): void
    {
        $payload = [
            'store_id' => $this->store->id,
            'technician_user_id' => $this->technicianB->id,
            'technician_location_id' => $this->locationB->id,
            'allocated_at' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'serial_number' => 'SN-ADM-001',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/v1/store-allocations', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('store_allocations', [
            'technician_user_id' => $this->technicianB->id,
            'technician_location_id' => $this->locationB->id,
            'created_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $this->product->id,
            'location_id' => $this->locationB->id,
            'quantity' => '9.0000',
            'condition' => 'GOOD',
        ]);
    }

    public function test_missing_technician_user_id_returns_validation_error_not_forbidden(): void
    {
        $payload = [
            'store_id' => $this->store->id,
            'technician_location_id' => $this->locationA->id,
            'allocated_at' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->technicianA)->postJson('/api/v1/store-allocations', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['technician_user_id']);
    }
}
