<?php

namespace Tests\Feature\Reporting;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\InventoryLocationLock;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportingPhase8A1Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $restrictedUser;

    private Location $loc1;

    private Location $loc2;

    private Category $category;

    private Unit $unit;

    private Product $prod1;

    private Product $prod2;

    private Product $inactiveProd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create(['username' => 'admin_user']);
        $adminRole = Role::where('code', 'ADMIN')->first();
        $this->admin->roles()->attach($adminRole->id);

        $this->loc1 = Location::factory()->create(['code' => 'LOC01', 'name' => 'Gudang Utama', 'is_active' => true]);
        $this->loc2 = Location::factory()->create(['code' => 'LOC02', 'name' => 'Gudang Cadangan', 'is_active' => true]);

        // Attach locations to admin
        $this->admin->locations()->attach([$this->loc1->id, $this->loc2->id]);

        $this->restrictedUser = User::factory()->create(['username' => 'staff_user']);
        $staffRole = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();
        $this->restrictedUser->roles()->attach($staffRole->id);

        $perm1 = Permission::where('code', PermissionCode::REPORTS_INVENTORY_BALANCE_VIEW->value)->first();
        $perm2 = Permission::where('code', PermissionCode::REPORTS_LOW_STOCK_VIEW->value)->first();
        $perm3 = Permission::where('code', PermissionCode::REPORTS_STOCK_CARD_VIEW->value)->first();

        if ($perm1 && $perm2 && $perm3) {
            $staffRole->permissions()->syncWithoutDetaching([$perm1->id, $perm2->id, $perm3->id]);
        }

        // Restrict user to loc1 only
        $this->restrictedUser->locations()->attach([$this->loc1->id]);

        $this->category = Category::factory()->create(['name' => 'Elektronik']);
        $this->unit = Unit::factory()->create(['name' => 'Pcs', 'symbol' => 'Pcs']);

        $this->prod1 = Product::factory()->create([
            'sku' => 'PROD-001',
            'barcode' => '880112233',
            'name' => 'Laptop Gaming',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'minimum_stock' => 10.00,
            'is_active' => true,
        ]);

        $this->prod2 = Product::factory()->create([
            'sku' => 'PROD-002',
            'barcode' => '880112244',
            'name' => 'Mouse Wireless',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'minimum_stock' => 20.00,
            'is_active' => true,
        ]);

        $this->inactiveProd = Product::factory()->create([
            'sku' => 'PROD-999',
            'name' => 'Keyboard Old',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'minimum_stock' => 5.00,
            'is_active' => false,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // INVENTORY BALANCE REPORT TESTS
    // ─────────────────────────────────────────────────────────────

    public function test_inventory_balance_report_returns_correct_balances(): void
    {
        InventoryBalance::create([
            'product_id' => $this->prod1->id,
            'location_id' => $this->loc1->id,
            'quantity' => '15.0000',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/reports/inventory-balances?location_id='.$this->loc1->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.product_sku', 'PROD-001')
            ->assertJsonPath('data.data.0.on_hand_quantity', '15.0000')
            ->assertJsonPath('data.data.0.is_below_minimum', false);
    }

    public function test_inventory_balance_report_respects_location_scope(): void
    {
        InventoryBalance::create([
            'product_id' => $this->prod1->id,
            'location_id' => $this->loc1->id,
            'quantity' => '10.0000',
        ]);
        InventoryBalance::create([
            'product_id' => $this->prod1->id,
            'location_id' => $this->loc2->id,
            'quantity' => '50.0000',
        ]);

        $response = $this->actingAs($this->restrictedUser)
            ->getJson('/api/v1/reports/inventory-balances');

        $response->assertStatus(200);
        $data = $response->json('data.data');

        $locationIds = array_column($data, 'location_id');
        $this->assertContains($this->loc1->id, $locationIds);
        $this->assertNotContains($this->loc2->id, $locationIds);
    }

    public function test_inventory_balance_report_includes_inactive_products_with_balances(): void
    {
        InventoryBalance::create([
            'product_id' => $this->inactiveProd->id,
            'location_id' => $this->loc1->id,
            'quantity' => '5.0000',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/reports/inventory-balances?is_active=0');

        $response->assertStatus(200)
            ->assertJsonPath('data.data.0.product_sku', 'PROD-999')
            ->assertJsonPath('data.data.0.is_product_active', false);
    }

    public function test_inventory_balance_quantity_returned_as_decimal_string(): void
    {
        InventoryBalance::create([
            'product_id' => $this->prod1->id,
            'location_id' => $this->loc1->id,
            'quantity' => '12.3456',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/reports/inventory-balances?product_id='.$this->prod1->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.data.0.on_hand_quantity', '12.3456');
    }

    public function test_inventory_balance_report_positive_and_zero_stock_filters(): void
    {
        InventoryBalance::create(['product_id' => $this->prod1->id, 'location_id' => $this->loc1->id, 'quantity' => '10.0000']);
        InventoryBalance::create(['product_id' => $this->prod2->id, 'location_id' => $this->loc1->id, 'quantity' => '0.0000']);

        // Positive stock only
        $resPos = $this->actingAs($this->admin)->getJson('/api/v1/reports/inventory-balances?location_id='.$this->loc1->id.'&positive_stock=1');
        $resPos->assertStatus(200);
        $this->assertCount(1, $resPos->json('data.data'));
        $this->assertEquals('PROD-001', $resPos->json('data.data.0.product_sku'));

        // Zero stock only
        $resZero = $this->actingAs($this->admin)->getJson('/api/v1/reports/inventory-balances?location_id='.$this->loc1->id.'&zero_stock=1');
        $resZero->assertStatus(200);
        $this->assertCount(1, $resZero->json('data.data'));
        $this->assertEquals('PROD-002', $resZero->json('data.data.0.product_sku'));
    }

    public function test_inventory_balance_report_shows_frozen_location_status(): void
    {
        InventoryBalance::create(['product_id' => $this->prod1->id, 'location_id' => $this->loc1->id, 'quantity' => '10.0000']);
        InventoryLocationLock::where('location_id', $this->loc1->id)->update(['is_frozen' => true, 'frozen_at' => now()]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/reports/inventory-balances?location_id='.$this->loc1->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.data.0.is_location_frozen', true);
    }

    // ─────────────────────────────────────────────────────────────
    // LOW STOCK REPORT TESTS
    // ─────────────────────────────────────────────────────────────

    public function test_low_stock_report_requires_location_id(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/reports/low-stock');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['location_id']);
    }

    public function test_low_stock_report_treats_missing_balance_as_zero(): void
    {
        // prod1 minimum_stock = 10.00, no balance row -> on_hand = 0.0000, shortage = 10.0000
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/reports/low-stock?location_id='.$this->loc1->id);

        $response->assertStatus(200);
        $skus = array_column($response->json('data.data'), 'product_sku');
        $this->assertContains('PROD-001', $skus);
    }

    public function test_low_stock_report_includes_only_items_strictly_below_minimum(): void
    {
        // prod1 minimum_stock = 10.00
        // Case A: quantity = 5.0000 (< 10.00) -> Should appear
        InventoryBalance::create(['product_id' => $this->prod1->id, 'location_id' => $this->loc1->id, 'quantity' => '5.0000']);

        // Case B: quantity = 20.0000 (>= 20.00) -> Should NOT appear
        InventoryBalance::create(['product_id' => $this->prod2->id, 'location_id' => $this->loc1->id, 'quantity' => '20.0000']);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/reports/low-stock?location_id='.$this->loc1->id);

        $response->assertStatus(200);
        $skus = array_column($response->json('data.data'), 'product_sku');
        $this->assertContains('PROD-001', $skus);
        $this->assertNotContains('PROD-002', $skus);
    }

    public function test_low_stock_report_calculates_shortage_quantity_without_float(): void
    {
        // prod1 min = 10.00, on_hand = 4.2500 -> shortage = 5.7500
        InventoryBalance::create(['product_id' => $this->prod1->id, 'location_id' => $this->loc1->id, 'quantity' => '4.2500']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/reports/low-stock?location_id='.$this->loc1->id.'&search=Laptop');

        $response->assertStatus(200)
            ->assertJsonPath('data.data.0.on_hand_quantity', '4.2500')
            ->assertJsonPath('data.data.0.minimum_stock', '10.0000')
            ->assertJsonPath('data.data.0.shortage_quantity', '5.7500');
    }

    public function test_low_stock_report_forbidden_for_unauthorized_location(): void
    {
        // restrictedUser only has access to loc1, so requesting loc2 must return empty data or forbidden
        $response = $this->actingAs($this->restrictedUser)
            ->getJson('/api/v1/reports/low-stock?location_id='.$this->loc2->id);

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data.data'));
    }

    // ─────────────────────────────────────────────────────────────
    // STOCK CARD REPORT TESTS
    // ─────────────────────────────────────────────────────────────

    public function test_stock_card_report_requires_mandatory_filters(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/reports/stock-card');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id', 'location_id', 'start_date', 'end_date']);
    }

    public function test_stock_card_report_rejects_date_range_exceeding_366_days(): void
    {
        $response = $this->actingAs($this->admin)->getJson(
            '/api/v1/reports/stock-card?product_id='.$this->prod1->id.'&location_id='.$this->loc1->id.'&start_date=2025-01-01&end_date=2026-06-01'
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_stock_card_opening_closing_balance_and_movements_in_period(): void
    {
        // Movement 1: Before period (2026-07-25) -> sets opening balance to 50.0000
        StockMovement::create([
            'movement_id' => Str::uuid()->toString(),
            'product_id' => $this->prod1->id,
            'location_id' => $this->loc1->id,
            'movement_type' => MovementType::RECEIPT->value,
            'quantity' => '50.0000',
            'quantity_before' => '0.0000',
            'quantity_after' => '50.0000',
            'reference_type' => 'App\Features\Inventory\Models\StockReceipt',
            'reference_id' => 1,
            'reference_number' => 'REC-001',
            'occurred_at' => '2026-07-25 10:00:00',
            'created_at' => '2026-07-25 10:00:00',
            'created_by' => $this->admin->id,
        ]);

        // Movement 2: Inside period (2026-08-01) -> RECEIPT +20.0000
        StockMovement::create([
            'movement_id' => Str::uuid()->toString(),
            'product_id' => $this->prod1->id,
            'location_id' => $this->loc1->id,
            'movement_type' => MovementType::RECEIPT->value,
            'quantity' => '20.0000',
            'quantity_before' => '50.0000',
            'quantity_after' => '70.0000',
            'reference_type' => 'App\Features\Inventory\Models\StockReceipt',
            'reference_id' => 2,
            'reference_number' => 'REC-002',
            'occurred_at' => '2026-08-01 09:00:00',
            'created_at' => '2026-08-01 09:00:00',
            'created_by' => $this->admin->id,
        ]);

        // Movement 3: Inside period (2026-08-02) -> ISSUE -15.0000
        StockMovement::create([
            'movement_id' => Str::uuid()->toString(),
            'product_id' => $this->prod1->id,
            'location_id' => $this->loc1->id,
            'movement_type' => MovementType::ISSUE->value,
            'quantity' => '15.0000',
            'quantity_before' => '70.0000',
            'quantity_after' => '55.0000',
            'reference_type' => 'App\Features\Inventory\Models\StockIssue',
            'reference_id' => 1,
            'reference_number' => 'ISS-001',
            'occurred_at' => '2026-08-02 14:00:00',
            'created_at' => '2026-08-02 14:00:00',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson(
            '/api/v1/reports/stock-card?product_id='.$this->prod1->id.'&location_id='.$this->loc1->id.'&start_date=2026-08-01&end_date=2026-08-03'
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.meta.date_basis', 'POSTED_AT')
            ->assertJsonPath('data.meta.opening_balance', '50.0000')
            ->assertJsonPath('data.meta.closing_balance', '55.0000')
            ->assertJsonPath('data.meta.total_quantity_in', '20.0000')
            ->assertJsonPath('data.meta.total_quantity_out', '15.0000')
            ->assertJsonPath('data.meta.movement_count', 2);
    }

    public function test_stock_card_reversal_direction_is_determined_authoritatively_by_delta(): void
    {
        // Reversal that reduces balance (delta < 0)
        StockMovement::create([
            'movement_id' => Str::uuid()->toString(),
            'product_id' => $this->prod1->id,
            'location_id' => $this->loc1->id,
            'movement_type' => MovementType::REVERSAL->value,
            'quantity' => '10.0000',
            'quantity_before' => '30.0000',
            'quantity_after' => '20.0000',
            'reference_type' => 'App\Features\Inventory\Models\StockReceipt',
            'reference_id' => 99,
            'reference_number' => 'REC-REV-01',
            'occurred_at' => '2026-08-01 11:00:00',
            'created_at' => '2026-08-01 11:00:00',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson(
            '/api/v1/reports/stock-card?product_id='.$this->prod1->id.'&location_id='.$this->loc1->id.'&start_date=2026-08-01&end_date=2026-08-01'
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.data.0.movement_type', 'REVERSAL')
            ->assertJsonPath('data.data.0.direction', 'OUT')
            ->assertJsonPath('data.data.0.quantity_out', '10.0000');
    }

    public function test_stock_card_backdated_movement_ledger_ordering_is_deterministic(): void
    {
        // Movement A: executed first (posted 2026-08-01 10:00:00)
        $movA = StockMovement::create([
            'movement_id' => Str::uuid()->toString(),
            'product_id' => $this->prod1->id,
            'location_id' => $this->loc1->id,
            'movement_type' => MovementType::RECEIPT->value,
            'quantity' => '100.0000',
            'quantity_before' => '0.0000',
            'quantity_after' => '100.0000',
            'reference_type' => 'App\Features\Inventory\Models\StockReceipt',
            'reference_id' => 10,
            'reference_number' => 'REC-010',
            'occurred_at' => '2026-08-01 10:00:00',
            'created_at' => '2026-08-01 10:00:00',
            'created_by' => $this->admin->id,
        ]);

        // Movement B: executed second (posted 2026-08-02 10:00:00), but occurred_at backdated to 2026-07-25 08:00:00
        $movB = StockMovement::create([
            'movement_id' => Str::uuid()->toString(),
            'product_id' => $this->prod1->id,
            'location_id' => $this->loc1->id,
            'movement_type' => MovementType::ADJUSTMENT_OUT->value,
            'quantity' => '10.0000',
            'quantity_before' => '100.0000',
            'quantity_after' => '90.0000',
            'reference_type' => 'App\Features\Inventory\Models\StockAdjustment',
            'reference_id' => 11,
            'reference_number' => 'ADJ-011',
            'occurred_at' => '2026-07-25 08:00:00',
            'created_at' => '2026-08-02 10:00:00',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson(
            '/api/v1/reports/stock-card?product_id='.$this->prod1->id.'&location_id='.$this->loc1->id.'&start_date=2026-08-01&end_date=2026-08-05'
        );

        $response->assertStatus(200);
        $data = $response->json('data.data');

        // Order is created_at ASC, id ASC -> movA (posted 1 Aug) then movB (posted 2 Aug)
        $this->assertCount(2, $data);
        $this->assertEquals($movA->id, $data[0]['id']);
        $this->assertEquals('100.0000', $data[0]['quantity_after']);

        $this->assertEquals($movB->id, $data[1]['id']);
        $this->assertEquals('90.0000', $data[1]['quantity_after']);

        // Balance chain invariant check: row[0].quantity_after == row[1].quantity_before
        $this->assertEquals($data[0]['quantity_after'], $data[1]['quantity_before']);

        // Backdated document date is preserved in document_date / occurred_at
        $this->assertEquals('2026-07-25 08:00:00', $data[1]['document_date']);
        $this->assertEquals('2026-08-02 10:00:00', $data[1]['posted_at']);
    }

    public function test_reporting_endpoints_are_strictly_read_only(): void
    {
        $this->actingAs($this->admin)->postJson('/api/v1/reports/inventory-balances')->assertStatus(405);
        $this->actingAs($this->admin)->postJson('/api/v1/reports/low-stock')->assertStatus(405);
        $this->actingAs($this->admin)->postJson('/api/v1/reports/stock-card')->assertStatus(405);
    }
}
