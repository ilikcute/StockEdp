<?php

namespace Tests\Feature\Inventory;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\OpnameStatus;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockOpname;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class StockOpnameTest extends TestCase
{
    use DatabaseMigrations;

    private User $officer1;

    private User $officer2;

    private User $supervisor;

    private Location $location;

    private Product $product1;

    private Product $product2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->officer1 = User::factory()->create();
        $this->officer2 = User::factory()->create();
        $this->supervisor = User::factory()->create();

        $officerRole = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();
        $supervisorRole = Role::where('code', RoleCode::INVENTORY_SUPERVISOR->value)->first();

        $this->officer1->roles()->attach($officerRole);
        $this->officer2->roles()->attach($officerRole);
        $this->supervisor->roles()->attach($supervisorRole);

        $this->location = Location::factory()->create(['is_active' => true]);

        $this->officer1->locations()->attach($this->location->id);
        $this->officer2->locations()->attach($this->location->id);
        $this->supervisor->locations()->attach($this->location->id);

        $this->product1 = Product::factory()->create(['is_active' => true]);
        $this->product2 = Product::factory()->create(['is_active' => true]);

        // Seed initial balances
        InventoryBalance::create(['location_id' => $this->location->id, 'product_id' => $this->product1->id, 'quantity' => '100.0000']);
        InventoryBalance::create(['location_id' => $this->location->id, 'product_id' => $this->product2->id, 'quantity' => '0.0000']);
    }

    public function test_officer_can_create_draft_opname()
    {
        $payload = [
            'location_id' => $this->location->id,
            'opname_date' => now()->format('Y-m-d'),
            'notes' => 'Opname bulanan',
        ];

        $response = $this->actingAs($this->officer1)
            ->postJson('/api/v1/stock-opnames', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'DRAFT')
            ->assertJsonPath('data.opname_number', fn ($val) => str_contains($val, 'SOP-'));

        $this->assertDatabaseHas('stock_opnames', [
            'location_id' => $this->location->id,
            'status' => 'DRAFT',
            'created_by' => $this->officer1->id,
        ]);
    }

    public function test_future_opname_date_rejected()
    {
        $payload = [
            'location_id' => $this->location->id,
            'opname_date' => now()->addDay()->format('Y-m-d'),
        ];

        $this->actingAs($this->officer1)
            ->postJson('/api/v1/stock-opnames', $payload)
            ->assertStatus(422);
    }

    public function test_start_opname_creates_snapshot_and_freezes_location()
    {
        $opname = StockOpname::create([
            'opname_number' => 'SOP-202608-0001',
            'location_id' => $this->location->id,
            'opname_date' => now()->format('Y-m-d'),
            'status' => OpnameStatus::DRAFT,
            'created_by' => $this->officer1->id,
        ]);

        $response = $this->actingAs($this->supervisor)
            ->postJson("/api/v1/stock-opnames/{$opname->id}/start");

        $response->assertOk()
            ->assertJsonPath('data.status', 'IN_PROGRESS');

        // Location should be frozen
        $this->assertDatabaseHas('inventory_location_locks', [
            'location_id' => $this->location->id,
            'is_frozen' => true,
            'frozen_by_opname_id' => $opname->id,
        ]);

        // Items snapshot verified (product1=100.0000, product2=0.0000)
        $this->assertDatabaseHas('stock_opname_items', [
            'stock_opname_id' => $opname->id,
            'product_id' => $this->product1->id,
            'snapshot_quantity' => '100.0000',
        ]);
        $this->assertDatabaseHas('stock_opname_items', [
            'stock_opname_id' => $opname->id,
            'product_id' => $this->product2->id,
            'snapshot_quantity' => '0.0000',
        ]);
    }

    public function test_cannot_start_second_active_opname_on_same_location()
    {
        $opname1 = StockOpname::create([
            'opname_number' => 'SOP-202608-0002',
            'location_id' => $this->location->id,
            'opname_date' => now()->format('Y-m-d'),
            'status' => OpnameStatus::DRAFT,
            'created_by' => $this->officer1->id,
        ]);
        $opname2 = StockOpname::create([
            'opname_number' => 'SOP-202608-0003',
            'location_id' => $this->location->id,
            'opname_date' => now()->format('Y-m-d'),
            'status' => OpnameStatus::DRAFT,
            'created_by' => $this->officer1->id,
        ]);

        // Start first opname
        $this->actingAs($this->supervisor)
            ->postJson("/api/v1/stock-opnames/{$opname1->id}/start")
            ->assertOk();

        // Start second opname on same location should return 409
        $this->actingAs($this->supervisor)
            ->postJson("/api/v1/stock-opnames/{$opname2->id}/start")
            ->assertStatus(409);
    }

    public function test_blind_count_hides_snapshot_and_variance_during_in_progress()
    {
        $opname = StockOpname::create([
            'opname_number' => 'SOP-202608-0004',
            'location_id' => $this->location->id,
            'opname_date' => now()->format('Y-m-d'),
            'status' => OpnameStatus::DRAFT,
            'created_by' => $this->officer1->id,
        ]);

        $this->actingAs($this->supervisor)
            ->postJson("/api/v1/stock-opnames/{$opname->id}/start");

        $response = $this->actingAs($this->officer1)
            ->getJson("/api/v1/stock-opnames/{$opname->id}");

        $response->assertOk()
            ->assertJsonPath('data.items.0.snapshot_quantity', null)
            ->assertJsonPath('data.items.0.variance_quantity', null);
    }

    public function test_count_input_records_immutable_log_and_increments_version()
    {
        $opname = StockOpname::create([
            'opname_number' => 'SOP-202608-0005',
            'location_id' => $this->location->id,
            'opname_date' => now()->format('Y-m-d'),
            'status' => OpnameStatus::DRAFT,
            'created_by' => $this->officer1->id,
        ]);
        $this->actingAs($this->supervisor)->postJson("/api/v1/stock-opnames/{$opname->id}/start");

        $item = $opname->items()->where('product_id', $this->product1->id)->first();

        $response = $this->actingAs($this->officer2)
            ->patchJson("/api/v1/stock-opnames/{$opname->id}/items/{$item->id}/count", [
                'counted_quantity' => '95.0000',
                'expected_version' => 0,
                'item_notes' => '5 hilang',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.counted_quantity', '95.0000')
            ->assertJsonPath('data.count_version', 1);

        $this->assertDatabaseHas('stock_opname_count_logs', [
            'stock_opname_item_id' => $item->id,
            'user_id' => $this->officer2->id,
            'new_quantity' => '95.0000',
            'count_version' => 1,
        ]);
    }

    public function test_stale_expected_version_rejected_with_409()
    {
        $opname = StockOpname::create([
            'opname_number' => 'SOP-202608-0006',
            'location_id' => $this->location->id,
            'opname_date' => now()->format('Y-m-d'),
            'status' => OpnameStatus::DRAFT,
            'created_by' => $this->officer1->id,
        ]);
        $this->actingAs($this->supervisor)->postJson("/api/v1/stock-opnames/{$opname->id}/start");

        $item = $opname->items()->where('product_id', $this->product1->id)->first();

        // First count
        $this->actingAs($this->officer2)
            ->patchJson("/api/v1/stock-opnames/{$opname->id}/items/{$item->id}/count", [
                'counted_quantity' => '95.0000',
                'expected_version' => 0,
            ])->assertOk();

        // Stale count with expected_version = 0 should return 409
        $this->actingAs($this->officer1)
            ->patchJson("/api/v1/stock-opnames/{$opname->id}/items/{$item->id}/count", [
                'counted_quantity' => '90.0000',
                'expected_version' => 0,
            ])->assertStatus(409);
    }

    public function test_complete_calculates_variance_and_retains_freeze()
    {
        $opname = StockOpname::create([
            'opname_number' => 'SOP-202608-0007',
            'location_id' => $this->location->id,
            'opname_date' => now()->format('Y-m-d'),
            'status' => OpnameStatus::DRAFT,
            'created_by' => $this->officer1->id,
        ]);
        $this->actingAs($this->supervisor)->postJson("/api/v1/stock-opnames/{$opname->id}/start");

        // Count all items
        $item1 = $opname->items()->where('product_id', $this->product1->id)->first();
        $item2 = $opname->items()->where('product_id', $this->product2->id)->first();

        $this->actingAs($this->officer2)->patchJson("/api/v1/stock-opnames/{$opname->id}/items/{$item1->id}/count", ['counted_quantity' => '105.0000']);
        $this->actingAs($this->officer2)->patchJson("/api/v1/stock-opnames/{$opname->id}/items/{$item2->id}/count", ['counted_quantity' => '0.0000']);

        $response = $this->actingAs($this->supervisor)
            ->postJson("/api/v1/stock-opnames/{$opname->id}/complete");

        $response->assertOk()
            ->assertJsonPath('data.status', 'COUNTED');

        // Check variance calculated
        $this->assertDatabaseHas('stock_opname_items', [
            'id' => $item1->id,
            'variance_quantity' => '5.0000', // 105 - 100
        ]);

        // Location stays frozen
        $this->assertDatabaseHas('inventory_location_locks', [
            'location_id' => $this->location->id,
            'is_frozen' => true,
        ]);
    }

    public function test_post_creates_movements_updates_balances_and_unfreezes()
    {
        $opname = StockOpname::create([
            'opname_number' => 'SOP-202608-0008',
            'location_id' => $this->location->id,
            'opname_date' => now()->format('Y-m-d'),
            'status' => OpnameStatus::DRAFT,
            'created_by' => $this->officer1->id,
        ]);
        $this->actingAs($this->supervisor)->postJson("/api/v1/stock-opnames/{$opname->id}/start");

        $item1 = $opname->items()->where('product_id', $this->product1->id)->first();
        $item2 = $opname->items()->where('product_id', $this->product2->id)->first();

        // Officer 2 counts
        $this->actingAs($this->officer2)->patchJson("/api/v1/stock-opnames/{$opname->id}/items/{$item1->id}/count", ['counted_quantity' => '105.0000']);
        $this->actingAs($this->officer2)->patchJson("/api/v1/stock-opnames/{$opname->id}/items/{$item2->id}/count", ['counted_quantity' => '0.0000']);

        $this->actingAs($this->supervisor)->postJson("/api/v1/stock-opnames/{$opname->id}/complete");

        // Supervisor (non-creator, non-counter) posts opname
        $response = $this->actingAs($this->supervisor)
            ->postJson("/api/v1/stock-opnames/{$opname->id}/post");

        $response->assertOk()
            ->assertJsonPath('data.status', 'POSTED');

        // OPNAME_IN movement created for item 1
        $this->assertDatabaseHas('stock_movements', [
            'reference_id' => $opname->id,
            'product_id' => $this->product1->id,
            'movement_type' => 'OPNAME_IN',
            'quantity' => '5.0000',
        ]);

        // Balance updated to 105.0000
        $this->assertDatabaseHas('inventory_balances', [
            'location_id' => $this->location->id,
            'product_id' => $this->product1->id,
            'quantity' => '105.0000',
        ]);

        // Location unfrozen
        $this->assertDatabaseHas('inventory_location_locks', [
            'location_id' => $this->location->id,
            'is_frozen' => false,
        ]);
    }

    public function test_maker_checker_creator_or_counter_cannot_post()
    {
        $opname = StockOpname::create([
            'opname_number' => 'SOP-202608-0009',
            'location_id' => $this->location->id,
            'opname_date' => now()->format('Y-m-d'),
            'status' => OpnameStatus::DRAFT,
            'created_by' => $this->officer1->id,
        ]);
        $this->actingAs($this->supervisor)->postJson("/api/v1/stock-opnames/{$opname->id}/start");

        $item1 = $opname->items()->where('product_id', $this->product1->id)->first();
        $item2 = $opname->items()->where('product_id', $this->product2->id)->first();

        // Supervisor counts item 1
        $this->actingAs($this->supervisor)->patchJson("/api/v1/stock-opnames/{$opname->id}/items/{$item1->id}/count", ['counted_quantity' => '100.0000']);
        $this->actingAs($this->officer2)->patchJson("/api/v1/stock-opnames/{$opname->id}/items/{$item2->id}/count", ['counted_quantity' => '0.0000']);

        $this->actingAs($this->supervisor)->postJson("/api/v1/stock-opnames/{$opname->id}/complete");

        // 1. Creator Officer1 attempts post -> 403
        $this->actingAs($this->officer1)
            ->postJson("/api/v1/stock-opnames/{$opname->id}/post")
            ->assertStatus(403);

        // 2. Supervisor who participated in count attempts post -> 403
        $this->actingAs($this->supervisor)
            ->postJson("/api/v1/stock-opnames/{$opname->id}/post")
            ->assertStatus(403);
    }

    public function test_reopen_resets_variance_records_reopen_log_and_retains_freeze()
    {
        $opname = StockOpname::create([
            'opname_number' => 'SOP-202608-0010',
            'location_id' => $this->location->id,
            'opname_date' => now()->format('Y-m-d'),
            'status' => OpnameStatus::DRAFT,
            'created_by' => $this->officer1->id,
        ]);
        $this->actingAs($this->supervisor)->postJson("/api/v1/stock-opnames/{$opname->id}/start");

        $item1 = $opname->items()->where('product_id', $this->product1->id)->first();
        $item2 = $opname->items()->where('product_id', $this->product2->id)->first();

        $this->actingAs($this->officer2)->patchJson("/api/v1/stock-opnames/{$opname->id}/items/{$item1->id}/count", ['counted_quantity' => '90.0000']);
        $this->actingAs($this->officer2)->patchJson("/api/v1/stock-opnames/{$opname->id}/items/{$item2->id}/count", ['counted_quantity' => '0.0000']);
        $this->actingAs($this->supervisor)->postJson("/api/v1/stock-opnames/{$opname->id}/complete");

        // Supervisor reopens opname
        $response = $this->actingAs($this->supervisor)
            ->postJson("/api/v1/stock-opnames/{$opname->id}/reopen", [
                'reason' => 'Perhitungan ulang rak B',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'IN_PROGRESS');

        $this->assertDatabaseHas('stock_opname_reopen_logs', [
            'stock_opname_id' => $opname->id,
            'reopened_by' => $this->supervisor->id,
            'reason' => 'Perhitungan ulang rak B',
        ]);

        // Location stays frozen
        $this->assertDatabaseHas('inventory_location_locks', [
            'location_id' => $this->location->id,
            'is_frozen' => true,
        ]);
    }

    public function test_cancel_in_progress_unfreezes_location_and_requires_reason()
    {
        $opname = StockOpname::create([
            'opname_number' => 'SOP-202608-0011',
            'location_id' => $this->location->id,
            'opname_date' => now()->format('Y-m-d'),
            'status' => OpnameStatus::DRAFT,
            'created_by' => $this->officer1->id,
        ]);
        $this->actingAs($this->supervisor)->postJson("/api/v1/stock-opnames/{$opname->id}/start");

        // Cancel with empty reason rejected
        $this->actingAs($this->supervisor)
            ->postJson("/api/v1/stock-opnames/{$opname->id}/cancel", ['cancel_reason' => ''])
            ->assertStatus(422);

        // Cancel with valid reason
        $response = $this->actingAs($this->supervisor)
            ->postJson("/api/v1/stock-opnames/{$opname->id}/cancel", ['cancel_reason' => 'Sesi dibatalkan karena kesalahan lokasi']);

        $response->assertOk()
            ->assertJsonPath('data.status', 'CANCELED');

        // Location unfrozen
        $this->assertDatabaseHas('inventory_location_locks', [
            'location_id' => $this->location->id,
            'is_frozen' => false,
        ]);
    }
}
