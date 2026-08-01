<?php

namespace Tests\Feature\Inventory;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\AdjustmentReason;
use App\Features\Inventory\Enums\AdjustmentStatus;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockAdjustment;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use DatabaseMigrations;

    private User $admin;

    private User $warehouseOfficer1;

    private User $warehouseOfficer2;

    private User $supervisor;

    private Location $location;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();

        $this->admin = User::factory()->create();
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole->id);

        $this->warehouseOfficer1 = User::factory()->create();
        $this->warehouseOfficer2 = User::factory()->create();
        $warehouseRole = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();
        $this->warehouseOfficer1->roles()->attach($warehouseRole->id);
        $this->warehouseOfficer2->roles()->attach($warehouseRole->id);

        $this->supervisor = User::factory()->create();
        $supervisorRole = Role::where('code', RoleCode::INVENTORY_SUPERVISOR->value)->first();
        $this->supervisor->roles()->attach($supervisorRole->id);

        $this->location = Location::factory()->create(['is_active' => true]);
        $this->product = Product::factory()->create(['is_active' => true]);

        // Attach location access
        $this->admin->locations()->attach($this->location->id);
        $this->warehouseOfficer1->locations()->attach($this->location->id);
        $this->warehouseOfficer2->locations()->attach($this->location->id);
        $this->supervisor->locations()->attach($this->location->id);
    }

    private function seedPermissions(): void
    {
        $permissions = [
            PermissionCode::STOCK_ADJUSTMENTS_VIEW->value => 'view',
            PermissionCode::STOCK_ADJUSTMENTS_CREATE->value => 'create',
            PermissionCode::STOCK_ADJUSTMENTS_UPDATE->value => 'update',
            PermissionCode::STOCK_ADJUSTMENTS_POST->value => 'post',
            PermissionCode::STOCK_ADJUSTMENTS_CANCEL->value => 'cancel',
            PermissionCode::INVENTORY_BALANCES_VIEW->value => 'balance_view',
        ];

        foreach ($permissions as $code => $name) {
            Permission::firstOrCreate(['code' => $code], ['name' => $name, 'group' => 'stock_adjustments']);
        }

        $adminRole = Role::firstOrCreate(['code' => RoleCode::ADMIN->value], ['name' => 'Admin']);
        $adminRole->permissions()->sync(Permission::pluck('id'));

        $warehouseRole = Role::firstOrCreate(['code' => RoleCode::WAREHOUSE_OFFICER->value], ['name' => 'Officer']);
        $warehousePerms = Permission::whereIn('code', [
            PermissionCode::STOCK_ADJUSTMENTS_VIEW->value,
            PermissionCode::STOCK_ADJUSTMENTS_CREATE->value,
            PermissionCode::STOCK_ADJUSTMENTS_UPDATE->value,
            PermissionCode::STOCK_ADJUSTMENTS_CANCEL->value,
        ])->pluck('id');
        $warehouseRole->permissions()->sync($warehousePerms);

        $supervisorRole = Role::firstOrCreate(['code' => RoleCode::INVENTORY_SUPERVISOR->value], ['name' => 'Supervisor']);
        $supervisorPerms = Permission::whereIn('code', [
            PermissionCode::STOCK_ADJUSTMENTS_VIEW->value,
            PermissionCode::STOCK_ADJUSTMENTS_CREATE->value,
            PermissionCode::STOCK_ADJUSTMENTS_UPDATE->value,
            PermissionCode::STOCK_ADJUSTMENTS_POST->value,
            PermissionCode::STOCK_ADJUSTMENTS_CANCEL->value,
        ])->pluck('id');
        $supervisorRole->permissions()->sync($supervisorPerms);
    }

    public function test_petugas_gudang_can_create_draft_adjustment()
    {
        $payload = [
            'location_id' => $this->location->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'notes' => 'Barang ditemukan saat pembersihan',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => '10.0000', 'item_notes' => 'Rak A'],
            ],
        ];

        $response = $this->actingAs($this->warehouseOfficer1)
            ->postJson('/api/v1/stock-adjustments', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'DRAFT')
            ->assertJsonPath('data.direction', 'INCREASE');

        $this->assertDatabaseHas('stock_adjustments', [
            'location_id' => $this->location->id,
            'status' => 'DRAFT',
            'created_by' => $this->warehouseOfficer1->id,
        ]);
    }

    public function test_draft_adjustment_does_not_change_balance()
    {
        $payload = [
            'location_id' => $this->location->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => '10.0000'],
            ],
        ];

        $this->actingAs($this->warehouseOfficer1)
            ->postJson('/api/v1/stock-adjustments', $payload)
            ->assertStatus(201);

        $balance = InventoryBalance::where('location_id', $this->location->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertTrue($balance === null || bccomp((string) $balance->quantity, '0.0000', 4) === 0);
        $this->assertEquals(0, StockMovement::count());
    }

    public function test_petugas_gudang_can_update_own_draft_only()
    {
        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-202608-0001',
            'location_id' => $this->location->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'status' => AdjustmentStatus::DRAFT,
            'created_by' => $this->warehouseOfficer1->id,
        ]);
        $adjustment->items()->create(['product_id' => $this->product->id, 'quantity' => '5.0000']);

        // Update own draft -> SUCCESS
        $response = $this->actingAs($this->warehouseOfficer1)
            ->patchJson("/api/v1/stock-adjustments/{$adjustment->id}", [
                'location_id' => $this->location->id,
                'adjustment_date' => now()->format('Y-m-d'),
                'direction' => 'INCREASE',
                'reason_code' => AdjustmentReason::FOUND->value,
                'items' => [['product_id' => $this->product->id, 'quantity' => '15.0000']],
            ]);
        $response->assertStatus(200);

        // Officer 2 tries to update Officer 1's draft -> DENIED 403
        $response2 = $this->actingAs($this->warehouseOfficer2)
            ->patchJson("/api/v1/stock-adjustments/{$adjustment->id}", [
                'location_id' => $this->location->id,
                'adjustment_date' => now()->format('Y-m-d'),
                'direction' => 'INCREASE',
                'reason_code' => AdjustmentReason::FOUND->value,
                'items' => [['product_id' => $this->product->id, 'quantity' => '20.0000']],
            ]);
        $response2->assertStatus(403);
    }

    public function test_petugas_gudang_cannot_post_adjustment()
    {
        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-202608-0002',
            'location_id' => $this->location->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'status' => AdjustmentStatus::DRAFT,
            'created_by' => $this->warehouseOfficer1->id,
        ]);
        $adjustment->items()->create(['product_id' => $this->product->id, 'quantity' => '5.0000']);

        $this->actingAs($this->warehouseOfficer1)
            ->postJson("/api/v1/stock-adjustments/{$adjustment->id}/post")
            ->assertStatus(403);
    }

    public function test_supervisor_can_post_draft_created_by_another_user()
    {
        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-202608-0003',
            'location_id' => $this->location->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'status' => AdjustmentStatus::DRAFT,
            'created_by' => $this->warehouseOfficer1->id,
        ]);
        $adjustment->items()->create(['product_id' => $this->product->id, 'quantity' => '10.0000']);

        $response = $this->actingAs($this->supervisor)
            ->postJson("/api/v1/stock-adjustments/{$adjustment->id}/post");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'POSTED');

        $this->assertDatabaseHas('stock_adjustments', [
            'id' => $adjustment->id,
            'status' => 'POSTED',
            'posted_by' => $this->supervisor->id,
        ]);

        $balance = InventoryBalance::where('location_id', $this->location->id)
            ->where('product_id', $this->product->id)
            ->first();
        $this->assertEquals('10.0000', $balance->quantity);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'movement_type' => MovementType::ADJUSTMENT_IN->value,
            'quantity' => '10.0000',
        ]);
    }

    public function test_maker_checker_supervisor_cannot_post_own_created_adjustment()
    {
        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-202608-0004',
            'location_id' => $this->location->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'status' => AdjustmentStatus::DRAFT,
            'created_by' => $this->supervisor->id,
        ]);
        $adjustment->items()->create(['product_id' => $this->product->id, 'quantity' => '10.0000']);

        // Supervisor tries to post their own creation -> DENIED 403 (Maker-Checker violation)
        $this->actingAs($this->supervisor)
            ->postJson("/api/v1/stock-adjustments/{$adjustment->id}/post")
            ->assertStatus(403);
    }

    public function test_maker_checker_admin_cannot_post_own_created_adjustment()
    {
        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-202608-0005',
            'location_id' => $this->location->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'status' => AdjustmentStatus::DRAFT,
            'created_by' => $this->admin->id,
        ]);
        $adjustment->items()->create(['product_id' => $this->product->id, 'quantity' => '10.0000']);

        // Admin tries to post their own creation -> DENIED 403 (Maker-Checker violation)
        $this->actingAs($this->admin)
            ->postJson("/api/v1/stock-adjustments/{$adjustment->id}/post")
            ->assertStatus(403);

        // Supervisor posts Admin's creation -> SUCCESS
        $this->actingAs($this->supervisor)
            ->postJson("/api/v1/stock-adjustments/{$adjustment->id}/post")
            ->assertStatus(200);
    }

    public function test_reason_direction_compatibility_validation()
    {
        // FOUND + DECREASE -> REJECT 422
        $this->actingAs($this->warehouseOfficer1)
            ->postJson('/api/v1/stock-adjustments', [
                'location_id' => $this->location->id,
                'adjustment_date' => now()->format('Y-m-d'),
                'direction' => 'DECREASE',
                'reason_code' => AdjustmentReason::FOUND->value,
                'items' => [['product_id' => $this->product->id, 'quantity' => '5.0000']],
            ])->assertStatus(422);

        // DAMAGED + INCREASE -> REJECT 422
        $this->actingAs($this->warehouseOfficer1)
            ->postJson('/api/v1/stock-adjustments', [
                'location_id' => $this->location->id,
                'adjustment_date' => now()->format('Y-m-d'),
                'direction' => 'INCREASE',
                'reason_code' => AdjustmentReason::DAMAGED->value,
                'items' => [['product_id' => $this->product->id, 'quantity' => '5.0000']],
            ])->assertStatus(422);

        // EXPIRED + INCREASE -> REJECT 422
        $this->actingAs($this->warehouseOfficer1)
            ->postJson('/api/v1/stock-adjustments', [
                'location_id' => $this->location->id,
                'adjustment_date' => now()->format('Y-m-d'),
                'direction' => 'INCREASE',
                'reason_code' => AdjustmentReason::EXPIRED->value,
                'items' => [['product_id' => $this->product->id, 'quantity' => '5.0000']],
            ])->assertStatus(422);

        // LOST + INCREASE -> REJECT 422
        $this->actingAs($this->warehouseOfficer1)
            ->postJson('/api/v1/stock-adjustments', [
                'location_id' => $this->location->id,
                'adjustment_date' => now()->format('Y-m-d'),
                'direction' => 'INCREASE',
                'reason_code' => AdjustmentReason::LOST->value,
                'items' => [['product_id' => $this->product->id, 'quantity' => '5.0000']],
            ])->assertStatus(422);
    }

    public function test_other_reason_requires_non_whitespace_notes()
    {
        // OTHER with empty notes -> REJECT 422
        $this->actingAs($this->warehouseOfficer1)
            ->postJson('/api/v1/stock-adjustments', [
                'location_id' => $this->location->id,
                'adjustment_date' => now()->format('Y-m-d'),
                'direction' => 'INCREASE',
                'reason_code' => AdjustmentReason::OTHER->value,
                'notes' => '   ', // whitespace only
                'items' => [['product_id' => $this->product->id, 'quantity' => '5.0000']],
            ])->assertStatus(422);

        // OTHER with valid notes -> SUCCESS 201
        $this->actingAs($this->warehouseOfficer1)
            ->postJson('/api/v1/stock-adjustments', [
                'location_id' => $this->location->id,
                'adjustment_date' => now()->format('Y-m-d'),
                'direction' => 'INCREASE',
                'reason_code' => AdjustmentReason::OTHER->value,
                'notes' => 'Koreksi khusus pameran',
                'items' => [['product_id' => $this->product->id, 'quantity' => '5.0000']],
            ])->assertStatus(201);
    }

    public function test_future_adjustment_date_rejected()
    {
        $futureDate = now()->addDays(2)->format('Y-m-d');

        $this->actingAs($this->warehouseOfficer1)
            ->postJson('/api/v1/stock-adjustments', [
                'location_id' => $this->location->id,
                'adjustment_date' => $futureDate,
                'direction' => 'INCREASE',
                'reason_code' => AdjustmentReason::FOUND->value,
                'items' => [['product_id' => $this->product->id, 'quantity' => '5.0000']],
            ])->assertStatus(422);
    }

    public function test_adjustment_out_fails_if_insufficient_stock()
    {
        InventoryBalance::create([
            'location_id' => $this->location->id,
            'product_id' => $this->product->id,
            'quantity' => '5.0000',
        ]);

        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-202608-0006',
            'location_id' => $this->location->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'DECREASE',
            'reason_code' => AdjustmentReason::DAMAGED->value,
            'status' => AdjustmentStatus::DRAFT,
            'created_by' => $this->warehouseOfficer1->id,
        ]);
        $adjustment->items()->create(['product_id' => $this->product->id, 'quantity' => '10.0000']);

        // Post DECREASE 10 when balance is 5 -> REJECT 422
        $this->actingAs($this->supervisor)
            ->postJson("/api/v1/stock-adjustments/{$adjustment->id}/post")
            ->assertStatus(422);

        // Balance untouched
        $balance = InventoryBalance::where('location_id', $this->location->id)->where('product_id', $this->product->id)->first();
        $this->assertEquals('5.0000', $balance->quantity);
    }

    public function test_multi_item_adjustment_rollback_on_failure()
    {
        $product2 = Product::factory()->create(['is_active' => true]);

        InventoryBalance::create([
            'location_id' => $this->location->id,
            'product_id' => $this->product->id,
            'quantity' => '50.0000',
        ]);

        InventoryBalance::create([
            'location_id' => $this->location->id,
            'product_id' => $product2->id,
            'quantity' => '2.0000', // insufficient
        ]);

        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-202608-0007',
            'location_id' => $this->location->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'DECREASE',
            'reason_code' => AdjustmentReason::DAMAGED->value,
            'status' => AdjustmentStatus::DRAFT,
            'created_by' => $this->warehouseOfficer1->id,
        ]);
        $adjustment->items()->createMany([
            ['product_id' => $this->product->id, 'quantity' => '10.0000'],
            ['product_id' => $product2->id, 'quantity' => '5.0000'], // will fail
        ]);

        $this->actingAs($this->supervisor)
            ->postJson("/api/v1/stock-adjustments/{$adjustment->id}/post")
            ->assertStatus(422);

        // Entire transaction rolled back: product 1 balance remains 50, no movements
        $b1 = InventoryBalance::where('location_id', $this->location->id)->where('product_id', $this->product->id)->first();
        $this->assertEquals('50.0000', $b1->quantity);

        $this->assertEquals(0, StockMovement::count());
        $adjustment->refresh();
        $this->assertEquals(AdjustmentStatus::DRAFT, $adjustment->status);
    }

    public function test_cannot_post_twice_idempotency()
    {
        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-202608-0008',
            'location_id' => $this->location->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'status' => AdjustmentStatus::DRAFT,
            'created_by' => $this->warehouseOfficer1->id,
        ]);
        $adjustment->items()->create(['product_id' => $this->product->id, 'quantity' => '10.0000']);

        // First post -> 200
        $this->actingAs($this->supervisor)
            ->postJson("/api/v1/stock-adjustments/{$adjustment->id}/post")
            ->assertStatus(200);

        // Second post -> 409 Conflict
        $this->actingAs($this->admin)
            ->postJson("/api/v1/stock-adjustments/{$adjustment->id}/post")
            ->assertStatus(409);
    }

    public function test_cancel_draft_retains_header_and_items()
    {
        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-202608-0009',
            'location_id' => $this->location->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'status' => AdjustmentStatus::DRAFT,
            'created_by' => $this->warehouseOfficer1->id,
        ]);
        $item = $adjustment->items()->create(['product_id' => $this->product->id, 'quantity' => '10.0000']);

        $response = $this->actingAs($this->warehouseOfficer1)
            ->postJson("/api/v1/stock-adjustments/{$adjustment->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'CANCELED');

        $this->assertDatabaseHas('stock_adjustments', [
            'id' => $adjustment->id,
            'status' => 'CANCELED',
            'canceled_by' => $this->warehouseOfficer1->id,
        ]);

        // Items and Header still exist in DB
        $this->assertDatabaseHas('stock_adjustment_items', [
            'id' => $item->id,
        ]);
    }

    public function test_inactive_product_or_location_fails_posting()
    {
        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-202608-0010',
            'location_id' => $this->location->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'status' => AdjustmentStatus::DRAFT,
            'created_by' => $this->warehouseOfficer1->id,
        ]);
        $adjustment->items()->create(['product_id' => $this->product->id, 'quantity' => '10.0000']);

        // Deactivate product before post
        $this->product->update(['is_active' => false]);

        $this->actingAs($this->supervisor)
            ->postJson("/api/v1/stock-adjustments/{$adjustment->id}/post")
            ->assertStatus(422);

        $adjustment->refresh();
        $this->assertEquals(AdjustmentStatus::DRAFT, $adjustment->status);
    }
}
