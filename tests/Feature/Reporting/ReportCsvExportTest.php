<?php

namespace Tests\Feature\Reporting;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockAdjustment;
use App\Features\Inventory\Models\StockAdjustmentItem;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Models\StockIssueItem;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Inventory\Models\StockOpname;
use App\Features\Inventory\Models\StockOpnameItem;
use App\Features\Inventory\Models\StockReceipt;
use App\Features\Inventory\Models\StockReceiptItem;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Models\StockTransferItem;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Supplier\Models\Supplier;
use App\Features\Unit\Models\Unit;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportCsvExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staffLoc1;

    private User $staffEmptyLoc;

    private User $unauthorizedUser;

    private Location $loc1;

    private Location $loc2;

    private Category $category;

    private Unit $unit;

    private Product $product;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create(['username' => 'admin_csv']);
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole->id);

        $this->loc1 = Location::factory()->create(['code' => 'LCSV-01', 'name' => 'Gudang Utama']);
        $this->loc2 = Location::factory()->create(['code' => 'LCSV-02', 'name' => 'Gudang Cabang']);
        $this->admin->locations()->attach([$this->loc1->id, $this->loc2->id]);

        $this->staffLoc1 = User::factory()->create(['username' => 'staff_csv']);
        $this->staffEmptyLoc = User::factory()->create(['username' => 'staff_noloc']);
        $staffRole = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();
        $this->staffLoc1->roles()->attach($staffRole->id);
        $this->staffEmptyLoc->roles()->attach($staffRole->id);

        $perms = Permission::whereIn('code', [
            PermissionCode::REPORTS_INVENTORY_BALANCE_VIEW->value,
            PermissionCode::REPORTS_LOW_STOCK_VIEW->value,
            PermissionCode::REPORTS_STOCK_CARD_VIEW->value,
            PermissionCode::REPORTS_STOCK_RECEIPTS_VIEW->value,
            PermissionCode::REPORTS_STOCK_ISSUES_VIEW->value,
            PermissionCode::REPORTS_STOCK_TRANSFERS_VIEW->value,
            PermissionCode::REPORTS_STOCK_ADJUSTMENTS_VIEW->value,
            PermissionCode::REPORTS_STOCK_OPNAMES_VIEW->value,
        ])->get();
        $staffRole->permissions()->syncWithoutDetaching($perms->pluck('id')->toArray());
        $this->staffLoc1->locations()->attach([$this->loc1->id]);

        $this->unauthorizedUser = User::factory()->create(['username' => 'unauth_csv']);

        $this->category = Category::factory()->create(['name' => 'Kategori CSV']);
        $this->unit = Unit::factory()->create(['name' => 'Pcs', 'symbol' => 'Pcs']);
        $this->product = Product::factory()->create([
            'sku' => 'SKU-CSV-001',
            'name' => '=SUM(A1:A2) Item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'minimum_stock' => 100.0000,
        ]);
        $this->supplier = Supplier::factory()->create(['name' => 'Supplier CSV']);
    }

    public function test_export_endpoints_require_authentication()
    {
        $endpoints = [
            '/api/v1/reports/inventory-balances/export',
            '/api/v1/reports/low-stock/export',
            '/api/v1/reports/stock-card/export',
            '/api/v1/reports/stock-receipts/export',
            '/api/v1/reports/stock-issues/export',
            '/api/v1/reports/stock-transfers/export',
            '/api/v1/reports/stock-adjustments/export',
            '/api/v1/reports/stock-opnames/export',
        ];

        foreach ($endpoints as $url) {
            $this->getJson($url)->assertStatus(401);
        }
    }

    public function test_export_endpoints_require_authorization()
    {
        $endpoints = [
            '/api/v1/reports/inventory-balances/export',
            '/api/v1/reports/low-stock/export?location_id='.$this->loc1->id,
            '/api/v1/reports/stock-card/export?product_id='.$this->product->id.'&location_id='.$this->loc1->id.'&start_date=2026-08-01&end_date=2026-08-05',
            '/api/v1/reports/stock-receipts/export',
            '/api/v1/reports/stock-issues/export',
            '/api/v1/reports/stock-transfers/export',
            '/api/v1/reports/stock-adjustments/export',
            '/api/v1/reports/stock-opnames/export',
        ];

        foreach ($endpoints as $url) {
            $this->actingAs($this->unauthorizedUser, 'sanctum')
                ->getJson($url)
                ->assertStatus(403);
        }
    }

    public function test_happy_path_export_all_eight_endpoints()
    {
        // Fixture setup
        InventoryBalance::create(['product_id' => $this->product->id, 'location_id' => $this->loc1->id, 'quantity' => 10.0000]);

        $receipt = StockReceipt::create(['receipt_number' => 'RC-001', 'date' => '2026-08-05', 'supplier_id' => $this->supplier->id, 'status' => 'POSTED', 'posted_at' => now(), 'created_by' => $this->admin->id]);
        StockReceiptItem::create(['stock_receipt_id' => $receipt->id, 'product_id' => $this->product->id, 'location_id' => $this->loc1->id, 'quantity' => 50.0000]);

        $issue = StockIssue::create(['issue_number' => 'IS-001', 'date' => '2026-08-05', 'purpose' => 'Production', 'status' => 'POSTED', 'posted_at' => now(), 'created_by' => $this->admin->id]);
        StockIssueItem::create(['stock_issue_id' => $issue->id, 'product_id' => $this->product->id, 'location_id' => $this->loc1->id, 'quantity' => 10.0000]);

        $transfer = StockTransfer::create(['transfer_number' => 'TR-001', 'origin_location_id' => $this->loc1->id, 'destination_location_id' => $this->loc2->id, 'status' => 'SENT', 'sent_at' => now(), 'created_by' => $this->admin->id, 'sent_by' => $this->admin->id, 'transfer_date' => now()->toDateString()]);
        StockTransferItem::create(['stock_transfer_id' => $transfer->id, 'product_id' => $this->product->id, 'quantity' => 20.0000]);

        $adj = StockAdjustment::create(['adjustment_number' => 'ADJ-001', 'adjustment_date' => '2026-08-05', 'location_id' => $this->loc1->id, 'direction' => 'INCREASE', 'reason_code' => 'FOUND', 'status' => 'POSTED', 'posted_at' => now(), 'created_by' => $this->admin->id, 'posted_by' => $this->admin->id]);
        StockAdjustmentItem::create(['stock_adjustment_id' => $adj->id, 'product_id' => $this->product->id, 'quantity' => 5.0000]);

        $op = StockOpname::create(['opname_number' => 'OP-001', 'location_id' => $this->loc1->id, 'opname_date' => now()->toDateString(), 'status' => 'POSTED', 'posted_at' => now(), 'created_by' => $this->admin->id]);
        StockOpnameItem::create(['stock_opname_id' => $op->id, 'product_id' => $this->product->id, 'snapshot_quantity' => 10.0000, 'counted_quantity' => 12.0000, 'variance_quantity' => 2.0000, 'is_unexpected' => false]);

        StockMovement::create([
            'product_id' => $this->product->id,
            'location_id' => $this->loc1->id,
            'movement_type' => 'RECEIPT',
            'reference_type' => StockReceipt::class,
            'reference_id' => $receipt->id,
            'movement_id' => 'RC-001',
            'quantity' => '50.0000',
            'quantity_before' => '0.0000',
            'quantity_after' => '50.0000',
            'occurred_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        $endpointConfigs = [
            'inventory-balances' => '/api/v1/reports/inventory-balances/export',
            'low-stock' => '/api/v1/reports/low-stock/export?location_id='.$this->loc1->id,
            'stock-card' => '/api/v1/reports/stock-card/export?product_id='.$this->product->id.'&location_id='.$this->loc1->id.'&start_date=2026-08-01&end_date=2026-08-05',
            'stock-receipts' => '/api/v1/reports/stock-receipts/export',
            'stock-issues' => '/api/v1/reports/stock-issues/export',
            'stock-transfers' => '/api/v1/reports/stock-transfers/export',
            'stock-adjustments' => '/api/v1/reports/stock-adjustments/export',
            'stock-opnames' => '/api/v1/reports/stock-opnames/export',
        ];

        foreach ($endpointConfigs as $slug => $url) {
            $response = $this->actingAs($this->staffLoc1, 'sanctum')->get($url);

            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            $response->assertHeader('Cache-Control', 'no-store, private');
            $response->assertHeader('X-Content-Type-Options', 'nosniff');

            $content = $response->streamedContent();
            $this->assertStringStartsWith("\xEF\xBB\xBF", $content, "Missing BOM for {$slug}");

            $lines = explode("\n", trim($content));
            $this->assertGreaterThanOrEqual(2, count($lines), "Expected header + data row for {$slug}");
        }
    }

    public function test_stock_card_export_is_scoped_to_allowed_locations()
    {
        // Movement in loc1 (allowed for staffLoc1)
        StockMovement::create([
            'product_id' => $this->product->id,
            'location_id' => $this->loc1->id,
            'movement_type' => 'RECEIPT',
            'reference_type' => StockReceipt::class,
            'reference_id' => 1,
            'movement_id' => 'RC-LOC1',
            'quantity' => '10.0000',
            'quantity_before' => '0.0000',
            'quantity_after' => '10.0000',
            'occurred_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        // Movement in loc2 (NOT allowed for staffLoc1)
        StockMovement::create([
            'product_id' => $this->product->id,
            'location_id' => $this->loc2->id,
            'movement_type' => 'RECEIPT',
            'reference_type' => StockReceipt::class,
            'reference_id' => 2,
            'movement_id' => 'RC-LOC2',
            'quantity' => '999.0000',
            'quantity_before' => '0.0000',
            'quantity_after' => '999.0000',
            'occurred_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        $urlLoc1 = '/api/v1/reports/stock-card/export?product_id='.$this->product->id.'&location_id='.$this->loc1->id.'&start_date=2026-08-01&end_date=2026-08-05';
        $resLoc1 = $this->actingAs($this->staffLoc1, 'sanctum')->get($urlLoc1)->assertStatus(200);
        $this->assertStringContainsString('RC-LOC1', $resLoc1->streamedContent());

        $urlLoc2 = '/api/v1/reports/stock-card/export?product_id='.$this->product->id.'&location_id='.$this->loc2->id.'&start_date=2026-08-01&end_date=2026-08-05';
        $resLoc2 = $this->actingAs($this->staffLoc1, 'sanctum')->get($urlLoc2)->assertStatus(200);
        $contentLoc2 = $resLoc2->streamedContent();
        $this->assertStringNotContainsString('RC-LOC2', $contentLoc2);
        $this->assertCount(1, explode("\n", trim($contentLoc2)));
    }

    public function test_stock_card_export_with_empty_location_scope_returns_header_only()
    {
        $url = '/api/v1/reports/stock-card/export?product_id='.$this->product->id.'&location_id='.$this->loc1->id.'&start_date=2026-08-01&end_date=2026-08-05';

        $response = $this->actingAs($this->staffEmptyLoc, 'sanctum')->get($url)->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $lines = explode("\n", trim($content));
        $this->assertCount(1, $lines);
    }

    public function test_stock_card_export_calculates_quantity_in_and_out_using_bcmath_delta()
    {
        // 0.0000 -> 10.0000 => In: 10.0000, Out: 0.0000
        StockMovement::create([
            'product_id' => $this->product->id,
            'location_id' => $this->loc1->id,
            'movement_type' => 'RECEIPT',
            'reference_type' => StockReceipt::class,
            'reference_id' => 1,
            'reference_number' => 'REF-001',
            'movement_id' => 'MOV-001',
            'quantity' => '10.0000',
            'quantity_before' => '0.0000',
            'quantity_after' => '10.0000',
            'created_at' => '2026-08-05 10:00:00',
            'occurred_at' => '2026-08-05 10:00:00',
            'created_by' => $this->admin->id,
        ]);

        // 10.0000 -> 7.5000 => In: 0.0000, Out: 2.5000
        StockMovement::create([
            'product_id' => $this->product->id,
            'location_id' => $this->loc1->id,
            'movement_type' => 'ISSUE',
            'reference_type' => StockIssue::class,
            'reference_id' => 1,
            'reference_number' => 'REF-002',
            'movement_id' => 'MOV-002',
            'quantity' => '2.5000',
            'quantity_before' => '10.0000',
            'quantity_after' => '7.5000',
            'created_at' => '2026-08-05 11:00:00',
            'occurred_at' => '2026-08-05 11:00:00',
            'created_by' => $this->admin->id,
        ]);

        // 0.0000 -> 0.0001 => In: 0.0001, Out: 0.0000
        StockMovement::create([
            'product_id' => $this->product->id,
            'location_id' => $this->loc1->id,
            'movement_type' => 'RECEIPT',
            'reference_type' => StockReceipt::class,
            'reference_id' => 2,
            'reference_number' => 'REF-003',
            'movement_id' => 'MOV-003',
            'quantity' => '0.0001',
            'quantity_before' => '0.0000',
            'quantity_after' => '0.0001',
            'created_at' => '2026-08-05 12:00:00',
            'occurred_at' => '2026-08-05 12:00:00',
            'created_by' => $this->admin->id,
        ]);

        $url = '/api/v1/reports/stock-card/export?product_id='.$this->product->id.'&location_id='.$this->loc1->id.'&start_date=2026-08-05&end_date=2026-08-05';
        $response = $this->actingAs($this->staffLoc1, 'sanctum')->get($url)->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('REF-001', $content);
        $this->assertStringContainsString('REF-002', $content);
        $this->assertStringContainsString('REF-003', $content);
        $this->assertStringContainsString('10.0000', $content);
        $this->assertStringContainsString('2.5000', $content);
        $this->assertStringContainsString('0.0001', $content);
    }

    public function test_stock_card_export_uses_half_open_date_interval()
    {
        // Boundary 1: start 00:00:00 (Included)
        StockMovement::create([
            'product_id' => $this->product->id,
            'location_id' => $this->loc1->id,
            'movement_type' => 'RECEIPT',
            'reference_type' => StockReceipt::class,
            'reference_id' => 1,
            'reference_number' => 'BO-START',
            'movement_id' => 'MOV-B1',
            'quantity' => '1.0000',
            'quantity_before' => '0.0000',
            'quantity_after' => '1.0000',
            'created_at' => '2026-08-05 00:00:00',
            'occurred_at' => '2026-08-05 00:00:00',
            'created_by' => $this->admin->id,
        ]);

        // Boundary 2: end 23:59:59 (Included)
        StockMovement::create([
            'product_id' => $this->product->id,
            'location_id' => $this->loc1->id,
            'movement_type' => 'RECEIPT',
            'reference_type' => StockReceipt::class,
            'reference_id' => 2,
            'reference_number' => 'BO-END',
            'movement_id' => 'MOV-B2',
            'quantity' => '1.0000',
            'quantity_before' => '1.0000',
            'quantity_after' => '2.0000',
            'created_at' => '2026-08-05 23:59:59',
            'occurred_at' => '2026-08-05 23:59:59',
            'created_by' => $this->admin->id,
        ]);

        // Boundary 3: next day 00:00:00 (EXCLUDED)
        StockMovement::create([
            'product_id' => $this->product->id,
            'location_id' => $this->loc1->id,
            'movement_type' => 'RECEIPT',
            'reference_type' => StockReceipt::class,
            'reference_id' => 3,
            'reference_number' => 'BO-NEXTDAY',
            'movement_id' => 'MOV-B3',
            'quantity' => '1.0000',
            'quantity_before' => '2.0000',
            'quantity_after' => '3.0000',
            'created_at' => '2026-08-06 00:00:00',
            'occurred_at' => '2026-08-06 00:00:00',
            'created_by' => $this->admin->id,
        ]);

        $url = '/api/v1/reports/stock-card/export?product_id='.$this->product->id.'&location_id='.$this->loc1->id.'&start_date=2026-08-05&end_date=2026-08-05';
        $response = $this->actingAs($this->staffLoc1, 'sanctum')->get($url)->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('BO-START', $content);
        $this->assertStringContainsString('BO-END', $content);
        $this->assertStringNotContainsString('BO-NEXTDAY', $content);
    }

    public function test_stock_card_export_uses_reference_number()
    {
        StockMovement::create([
            'product_id' => $this->product->id,
            'location_id' => $this->loc1->id,
            'movement_type' => 'RECEIPT',
            'reference_type' => StockReceipt::class,
            'reference_id' => 10,
            'reference_number' => 'DOC-REF-001',
            'movement_id' => 'MOVEMENT-INTERNAL-001',
            'quantity' => '5.0000',
            'quantity_before' => '0.0000',
            'quantity_after' => '5.0000',
            'created_at' => '2026-08-05 10:00:00',
            'occurred_at' => '2026-08-05 10:00:00',
            'created_by' => $this->admin->id,
        ]);

        $url = '/api/v1/reports/stock-card/export?product_id='.$this->product->id.'&location_id='.$this->loc1->id.'&start_date=2026-08-05&end_date=2026-08-05';
        $response = $this->actingAs($this->staffLoc1, 'sanctum')->get($url)->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('DOC-REF-001', $content);
        $this->assertStringNotContainsString('MOVEMENT-INTERNAL-001', $content);
    }

    public function test_transfer_csv_default_sort_follows_date_basis()
    {
        $trA = StockTransfer::create([
            'transfer_number' => 'TR-A',
            'origin_location_id' => $this->loc1->id,
            'destination_location_id' => $this->loc2->id,
            'status' => 'RECEIVED',
            'sent_at' => '2026-08-05 10:00:00',
            'received_at' => '2026-08-05 15:00:00',
            'created_by' => $this->admin->id,
            'sent_by' => $this->admin->id,
            'received_by' => $this->admin->id,
            'transfer_date' => '2026-08-05',
        ]);
        StockTransferItem::create(['stock_transfer_id' => $trA->id, 'product_id' => $this->product->id, 'quantity' => 10.0000]);

        $trB = StockTransfer::create([
            'transfer_number' => 'TR-B',
            'origin_location_id' => $this->loc1->id,
            'destination_location_id' => $this->loc2->id,
            'status' => 'RECEIVED',
            'sent_at' => '2026-08-05 11:00:00',
            'received_at' => '2026-08-05 14:00:00',
            'created_by' => $this->admin->id,
            'sent_by' => $this->admin->id,
            'received_by' => $this->admin->id,
            'transfer_date' => '2026-08-05',
        ]);
        StockTransferItem::create(['stock_transfer_id' => $trB->id, 'product_id' => $this->product->id, 'quantity' => 20.0000]);

        // Scenario A: date_basis=SENT_AT & sort_order=asc (Default sort should be sent_at) -> TR-A (10:00) before TR-B (11:00)
        $resSent = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-transfers/export?date_basis=SENT_AT&sort_order=asc')
            ->assertStatus(200);
        $contentSent = $resSent->streamedContent();
        $posA_Sent = strpos($contentSent, 'TR-A');
        $posB_Sent = strpos($contentSent, 'TR-B');
        $this->assertLessThan($posB_Sent, $posA_Sent);

        // Scenario B: date_basis=RECEIVED_AT & sort_order=asc (Default sort should be received_at) -> TR-B (14:00) before TR-A (15:00)
        $resRec = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-transfers/export?date_basis=RECEIVED_AT&sort_order=asc')
            ->assertStatus(200);
        $contentRec = $resRec->streamedContent();
        $posA_Rec = strpos($contentRec, 'TR-A');
        $posB_Rec = strpos($contentRec, 'TR-B');
        $this->assertLessThan($posA_Rec, $posB_Rec);

        // Scenario C: date_basis=RECEIVED_AT & sort_by=transfer_number & sort_order=asc -> Explicit sort authoritative (TR-A before TR-B)
        $resExplicit = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-transfers/export?date_basis=RECEIVED_AT&sort_by=transfer_number&sort_order=asc')
            ->assertStatus(200);
        $contentExplicit = $resExplicit->streamedContent();
        $posA_Exp = strpos($contentExplicit, 'TR-A');
        $posB_Exp = strpos($contentExplicit, 'TR-B');
        $this->assertLessThan($posB_Exp, $posA_Exp);
    }

    public function test_transfer_json_and_csv_order_parity()
    {
        $trA = StockTransfer::create([
            'transfer_number' => 'TR-P-01',
            'origin_location_id' => $this->loc1->id,
            'destination_location_id' => $this->loc2->id,
            'status' => 'RECEIVED',
            'sent_at' => '2026-08-05 10:00:00',
            'received_at' => '2026-08-05 15:00:00',
            'created_by' => $this->admin->id,
            'sent_by' => $this->admin->id,
            'received_by' => $this->admin->id,
            'transfer_date' => '2026-08-05',
        ]);
        StockTransferItem::create(['stock_transfer_id' => $trA->id, 'product_id' => $this->product->id, 'quantity' => 10.0000]);

        $trB = StockTransfer::create([
            'transfer_number' => 'TR-P-02',
            'origin_location_id' => $this->loc1->id,
            'destination_location_id' => $this->loc2->id,
            'status' => 'RECEIVED',
            'sent_at' => '2026-08-05 11:00:00',
            'received_at' => '2026-08-05 14:00:00',
            'created_by' => $this->admin->id,
            'sent_by' => $this->admin->id,
            'received_by' => $this->admin->id,
            'transfer_date' => '2026-08-05',
        ]);
        StockTransferItem::create(['stock_transfer_id' => $trB->id, 'product_id' => $this->product->id, 'quantity' => 20.0000]);

        // 1. date_basis=RECEIVED_AT & sort_order=asc
        $jsonRes = $this->actingAs($this->staffLoc1, 'sanctum')
            ->getJson('/api/v1/reports/stock-transfers?date_basis=RECEIVED_AT&sort_order=asc')
            ->assertStatus(200);
        $jsonNumbers = array_map(fn ($item) => $item['transfer_number'], $jsonRes->json('data'));

        $csvRes = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-transfers/export?date_basis=RECEIVED_AT&sort_order=asc')
            ->assertStatus(200);
        $csvContent = $csvRes->streamedContent();
        $posP1 = strpos($csvContent, 'TR-P-01');
        $posP2 = strpos($csvContent, 'TR-P-02');
        $csvNumbers = $posP2 < $posP1 ? ['TR-P-02', 'TR-P-01'] : ['TR-P-01', 'TR-P-02'];

        $this->assertEquals($jsonNumbers, $csvNumbers);

        // 2. date_basis=SENT_AT & sort_order=desc
        $jsonResDesc = $this->actingAs($this->staffLoc1, 'sanctum')
            ->getJson('/api/v1/reports/stock-transfers?date_basis=SENT_AT&sort_order=desc')
            ->assertStatus(200);
        $jsonNumbersDesc = array_map(fn ($item) => $item['transfer_number'], $jsonResDesc->json('data'));

        $csvResDesc = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-transfers/export?date_basis=SENT_AT&sort_order=desc')
            ->assertStatus(200);
        $csvContentDesc = $csvResDesc->streamedContent();
        $posP1Desc = strpos($csvContentDesc, 'TR-P-01');
        $posP2Desc = strpos($csvContentDesc, 'TR-P-02');
        $csvNumbersDesc = $posP2Desc < $posP1Desc ? ['TR-P-02', 'TR-P-01'] : ['TR-P-01', 'TR-P-02'];

        $this->assertEquals($jsonNumbersDesc, $csvNumbersDesc);
    }

    public function test_transaction_exports_respect_sort_by_and_sort_order()
    {
        // 1. Receipt test
        $rc1 = StockReceipt::create(['receipt_number' => 'RC-SORT-AAA', 'date' => '2026-08-01', 'supplier_id' => $this->supplier->id, 'status' => 'POSTED', 'posted_at' => '2026-08-05 10:00:00', 'created_by' => $this->admin->id]);
        StockReceiptItem::create(['stock_receipt_id' => $rc1->id, 'product_id' => $this->product->id, 'location_id' => $this->loc1->id, 'quantity' => 10.0000]);

        $rc2 = StockReceipt::create(['receipt_number' => 'RC-SORT-ZZZ', 'date' => '2026-08-05', 'supplier_id' => $this->supplier->id, 'status' => 'POSTED', 'posted_at' => '2026-08-05 12:00:00', 'created_by' => $this->admin->id]);
        StockReceiptItem::create(['stock_receipt_id' => $rc2->id, 'product_id' => $this->product->id, 'location_id' => $this->loc1->id, 'quantity' => 20.0000]);

        $resAsc = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-receipts/export?sort_by=receipt_number&sort_order=asc')
            ->assertStatus(200);
        $contentAsc = $resAsc->streamedContent();
        $this->assertLessThan(strpos($contentAsc, 'RC-SORT-ZZZ'), strpos($contentAsc, 'RC-SORT-AAA'));

        $resDesc = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-receipts/export?sort_by=receipt_number&sort_order=desc')
            ->assertStatus(200);
        $contentDesc = $resDesc->streamedContent();
        $this->assertLessThan(strpos($contentDesc, 'RC-SORT-AAA'), strpos($contentDesc, 'RC-SORT-ZZZ'));

        // 2. Issue test
        $is1 = StockIssue::create(['issue_number' => 'IS-SORT-AAA', 'date' => '2026-08-01', 'purpose' => 'Test', 'status' => 'POSTED', 'posted_at' => now(), 'created_by' => $this->admin->id]);
        StockIssueItem::create(['stock_issue_id' => $is1->id, 'product_id' => $this->product->id, 'location_id' => $this->loc1->id, 'quantity' => 5.0000]);

        $is2 = StockIssue::create(['issue_number' => 'IS-SORT-ZZZ', 'date' => '2026-08-05', 'purpose' => 'Test', 'status' => 'POSTED', 'posted_at' => now(), 'created_by' => $this->admin->id]);
        StockIssueItem::create(['stock_issue_id' => $is2->id, 'product_id' => $this->product->id, 'location_id' => $this->loc1->id, 'quantity' => 5.0000]);

        $resIssueAsc = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-issues/export?sort_by=issue_number&sort_order=asc')
            ->assertStatus(200);
        $contentIssueAsc = $resIssueAsc->streamedContent();
        $this->assertLessThan(strpos($contentIssueAsc, 'IS-SORT-ZZZ'), strpos($contentIssueAsc, 'IS-SORT-AAA'));

        // 3. Adjustment test
        $adj1 = StockAdjustment::create(['adjustment_number' => 'ADJ-SORT-AAA', 'adjustment_date' => '2026-08-01', 'location_id' => $this->loc1->id, 'direction' => 'INCREASE', 'reason_code' => 'FOUND', 'status' => 'POSTED', 'posted_at' => now(), 'created_by' => $this->admin->id, 'posted_by' => $this->admin->id]);
        StockAdjustmentItem::create(['stock_adjustment_id' => $adj1->id, 'product_id' => $this->product->id, 'quantity' => 1.0000]);

        $adj2 = StockAdjustment::create(['adjustment_number' => 'ADJ-SORT-ZZZ', 'adjustment_date' => '2026-08-05', 'location_id' => $this->loc1->id, 'direction' => 'INCREASE', 'reason_code' => 'FOUND', 'status' => 'POSTED', 'posted_at' => now(), 'created_by' => $this->admin->id, 'posted_by' => $this->admin->id]);
        StockAdjustmentItem::create(['stock_adjustment_id' => $adj2->id, 'product_id' => $this->product->id, 'quantity' => 1.0000]);

        $resAdjAsc = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-adjustments/export?sort_by=adjustment_number&sort_order=asc')
            ->assertStatus(200);
        $contentAdjAsc = $resAdjAsc->streamedContent();
        $this->assertLessThan(strpos($contentAdjAsc, 'ADJ-SORT-ZZZ'), strpos($contentAdjAsc, 'ADJ-SORT-AAA'));

        // 4. Opname test
        $op1 = StockOpname::create(['opname_number' => 'OP-SORT-AAA', 'location_id' => $this->loc1->id, 'opname_date' => now()->toDateString(), 'status' => 'POSTED', 'posted_at' => now(), 'created_by' => $this->admin->id]);
        StockOpnameItem::create(['stock_opname_id' => $op1->id, 'product_id' => $this->product->id, 'snapshot_quantity' => 10.0000, 'counted_quantity' => 10.0000, 'variance_quantity' => 0.0000, 'is_unexpected' => false]);

        $op2 = StockOpname::create(['opname_number' => 'OP-SORT-ZZZ', 'location_id' => $this->loc1->id, 'opname_date' => now()->toDateString(), 'status' => 'POSTED', 'posted_at' => now(), 'created_by' => $this->admin->id]);
        StockOpnameItem::create(['stock_opname_id' => $op2->id, 'product_id' => $this->product->id, 'snapshot_quantity' => 10.0000, 'counted_quantity' => 10.0000, 'variance_quantity' => 0.0000, 'is_unexpected' => false]);

        $resOpAsc = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-opnames/export?sort_by=opname_number&sort_order=asc')
            ->assertStatus(200);
        $contentOpAsc = $resOpAsc->streamedContent();
        $this->assertLessThan(strpos($contentOpAsc, 'OP-SORT-ZZZ'), strpos($contentOpAsc, 'OP-SORT-AAA'));
    }

    public function test_exports_do_not_trigger_lazy_loading()
    {
        Model::preventLazyLoading(true);

        $endpoints = [
            '/api/v1/reports/inventory-balances/export',
            '/api/v1/reports/low-stock/export?location_id='.$this->loc1->id,
            '/api/v1/reports/stock-card/export?product_id='.$this->product->id.'&location_id='.$this->loc1->id.'&start_date=2026-08-01&end_date=2026-08-05',
            '/api/v1/reports/stock-receipts/export',
            '/api/v1/reports/stock-issues/export',
            '/api/v1/reports/stock-transfers/export',
            '/api/v1/reports/stock-adjustments/export',
            '/api/v1/reports/stock-opnames/export',
        ];

        foreach ($endpoints as $url) {
            $response = $this->actingAs($this->staffLoc1, 'sanctum')->get($url);
            $response->assertStatus(200);
            $response->streamedContent();
        }

        Model::preventLazyLoading(false);
    }

    public function test_low_stock_required_filter_validation()
    {
        // Missing location_id -> 422
        $this->actingAs($this->staffLoc1, 'sanctum')
            ->getJson('/api/v1/reports/low-stock/export')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['location_id']);

        // Allowed location_id -> 200
        $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/low-stock/export?location_id='.$this->loc1->id)
            ->assertStatus(200);
    }

    public function test_stock_card_required_filter_validation()
    {
        // Missing product_id & location_id & start_date & end_date -> 422
        $this->actingAs($this->staffLoc1, 'sanctum')
            ->getJson('/api/v1/reports/stock-card/export')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product_id', 'location_id', 'start_date', 'end_date']);

        // Exceeding 366 days date range -> 422
        $this->actingAs($this->staffLoc1, 'sanctum')
            ->getJson('/api/v1/reports/stock-card/export?product_id='.$this->product->id.'&location_id='.$this->loc1->id.'&start_date=2024-01-01&end_date=2026-01-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_valid_stock_transfer_export_scenarios()
    {
        $transferSent = StockTransfer::create([
            'transfer_number' => 'TR-SENT-01',
            'origin_location_id' => $this->loc1->id,
            'destination_location_id' => $this->loc2->id,
            'status' => 'SENT',
            'sent_at' => '2026-08-05 10:00:00',
            'created_by' => $this->admin->id,
            'sent_by' => $this->admin->id,
            'transfer_date' => '2026-08-05',
        ]);
        StockTransferItem::create(['stock_transfer_id' => $transferSent->id, 'product_id' => $this->product->id, 'quantity' => 15.0000]);

        $transferRec = StockTransfer::create([
            'transfer_number' => 'TR-REC-01',
            'origin_location_id' => $this->loc1->id,
            'destination_location_id' => $this->loc2->id,
            'status' => 'RECEIVED',
            'sent_at' => '2026-08-05 09:00:00',
            'received_at' => '2026-08-05 11:00:00',
            'created_by' => $this->admin->id,
            'sent_by' => $this->admin->id,
            'received_by' => $this->admin->id,
            'transfer_date' => '2026-08-05',
        ]);
        StockTransferItem::create(['stock_transfer_id' => $transferRec->id, 'product_id' => $this->product->id, 'quantity' => 25.0000]);

        // status=SENT & date_basis=SENT_AT -> 200
        $resSent = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-transfers/export?status=SENT&date_basis=SENT_AT')
            ->assertStatus(200);
        $contentSent = $resSent->streamedContent();
        $this->assertStringContainsString('TR-SENT-01', $contentSent);
        $this->assertStringNotContainsString('TR-REC-01', $contentSent);

        // status=RECEIVED & date_basis=RECEIVED_AT -> 200
        $resRec = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-transfers/export?status=RECEIVED&date_basis=RECEIVED_AT')
            ->assertStatus(200);
        $contentRec = $resRec->streamedContent();
        $this->assertStringContainsString('TR-REC-01', $contentRec);

        // Invalid status=SENT & date_basis=RECEIVED_AT -> 422
        $this->actingAs($this->staffLoc1, 'sanctum')
            ->getJson('/api/v1/reports/stock-transfers/export?status=SENT&date_basis=RECEIVED_AT')
            ->assertStatus(422);
    }

    public function test_location_scoping_and_empty_allowed_locations()
    {
        // Item in loc1 (allowed) and loc2 (forbidden for staffLoc1)
        InventoryBalance::create(['product_id' => $this->product->id, 'location_id' => $this->loc1->id, 'quantity' => 10.0000]);
        InventoryBalance::create(['product_id' => $this->product->id, 'location_id' => $this->loc2->id, 'quantity' => 999.0000]);

        $response = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/inventory-balances/export')
            ->assertStatus(200);

        $content = $response->streamedContent();
        $this->assertStringContainsString('LCSV-01', $content);
        $this->assertStringNotContainsString('LCSV-02', $content);
        $this->assertStringNotContainsString('999.0000', $content);

        // User with empty allowed locations -> HTTP 200, BOM, Header line, 0 data rows
        $responseEmpty = $this->actingAs($this->staffEmptyLoc, 'sanctum')
            ->get('/api/v1/reports/inventory-balances/export')
            ->assertStatus(200);

        $contentEmpty = $responseEmpty->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $contentEmpty);
        $lines = explode("\n", trim($contentEmpty));
        $this->assertCount(1, $lines);
    }

    public function test_filter_parity_between_json_and_csv()
    {
        $rc1 = StockReceipt::create(['receipt_number' => 'PAR-001', 'date' => '2026-08-05', 'supplier_id' => $this->supplier->id, 'status' => 'POSTED', 'posted_at' => now(), 'created_by' => $this->admin->id]);
        StockReceiptItem::create(['stock_receipt_id' => $rc1->id, 'product_id' => $this->product->id, 'location_id' => $this->loc1->id, 'quantity' => 11.0000]);

        $rc2 = StockReceipt::create(['receipt_number' => 'PAR-002', 'date' => '2026-08-05', 'supplier_id' => $this->supplier->id, 'status' => 'POSTED', 'posted_at' => now(), 'created_by' => $this->admin->id]);
        StockReceiptItem::create(['stock_receipt_id' => $rc2->id, 'product_id' => $this->product->id, 'location_id' => $this->loc1->id, 'quantity' => 22.0000]);

        // JSON response count
        $jsonRes = $this->actingAs($this->staffLoc1, 'sanctum')
            ->getJson('/api/v1/reports/stock-receipts?supplier_id='.$this->supplier->id)
            ->assertStatus(200);
        $jsonTotal = $jsonRes->json('pagination.total');

        // CSV response row count
        $csvRes = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-receipts/export?supplier_id='.$this->supplier->id)
            ->assertStatus(200);
        $csvLines = explode("\n", trim($csvRes->streamedContent()));
        $csvDataRowCount = count($csvLines) - 1;

        $this->assertEquals($jsonTotal, $csvDataRowCount);
    }

    public function test_sorting_and_tie_breaker_stability()
    {
        $postedAt = '2026-08-05 12:00:00';

        $rc1 = StockReceipt::create(['receipt_number' => 'TIE-001', 'date' => '2026-08-05', 'supplier_id' => $this->supplier->id, 'status' => 'POSTED', 'posted_at' => $postedAt, 'created_by' => $this->admin->id]);
        $item1 = StockReceiptItem::create(['stock_receipt_id' => $rc1->id, 'product_id' => $this->product->id, 'location_id' => $this->loc1->id, 'quantity' => 5.0000]);

        $rc2 = StockReceipt::create(['receipt_number' => 'TIE-002', 'date' => '2026-08-05', 'supplier_id' => $this->supplier->id, 'status' => 'POSTED', 'posted_at' => $postedAt, 'created_by' => $this->admin->id]);
        $item2 = StockReceiptItem::create(['stock_receipt_id' => $rc2->id, 'product_id' => $this->product->id, 'location_id' => $this->loc1->id, 'quantity' => 10.0000]);

        $response = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-receipts/export?sort_order=desc')
            ->assertStatus(200);

        $content = $response->streamedContent();
        $pos1 = strpos($content, 'TIE-001');
        $pos2 = strpos($content, 'TIE-002');

        // In descending tie-breaker order, higher ID (TIE-002) appears before lower ID (TIE-001)
        $this->assertLessThan($pos1, $pos2);
    }

    public function test_pagination_parameters_are_ignored_by_export()
    {
        for ($i = 2; $i <= 5; $i++) {
            $p = Product::factory()->create([
                'sku' => "SKU-PAG-00{$i}",
                'name' => "Product Pag {$i}",
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
            ]);

            InventoryBalance::create(['product_id' => $p->id, 'location_id' => $this->loc1->id, 'quantity' => 10.0000]);
        }

        $response = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/inventory-balances/export?page=1&per_page=1')
            ->assertStatus(200);

        $lines = explode("\n", trim($response->streamedContent()));
        // Header line + 4 newly created + 1 initial product = at least 5 lines
        $this->assertGreaterThanOrEqual(5, count($lines));
    }

    public function test_empty_export_matrix_returns_header_only_csv_for_all_reports()
    {
        $endpoints = [
            '/api/v1/reports/inventory-balances/export?search=NONEXISTENT_SEARCH_TERM',
            '/api/v1/reports/low-stock/export?location_id='.$this->loc1->id.'&search=NONEXISTENT_SEARCH_TERM',
            '/api/v1/reports/stock-card/export?product_id='.$this->product->id.'&location_id='.$this->loc1->id.'&start_date=2020-01-01&end_date=2020-01-02',
            '/api/v1/reports/stock-receipts/export?search=NONEXISTENT_SEARCH_TERM',
            '/api/v1/reports/stock-issues/export?search=NONEXISTENT_SEARCH_TERM',
            '/api/v1/reports/stock-transfers/export?search=NONEXISTENT_SEARCH_TERM',
            '/api/v1/reports/stock-adjustments/export?search=NONEXISTENT_SEARCH_TERM',
            '/api/v1/reports/stock-opnames/export?search=NONEXISTENT_SEARCH_TERM',
        ];

        foreach ($endpoints as $url) {
            $response = $this->actingAs($this->staffLoc1, 'sanctum')->get($url);

            $response->assertStatus(200);
            $content = $response->streamedContent();

            $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
            $lines = explode("\n", trim($content));
            $this->assertCount(1, $lines, "Expected exactly 1 header line for empty export: {$url}");
        }
    }

    public function test_filename_timestamp_format_and_jakarta_timezone()
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-05 14:30:45', 'Asia/Jakarta'));

        $endpoints = [
            'inventory-balances' => '/api/v1/reports/inventory-balances/export',
            'low-stock' => '/api/v1/reports/low-stock/export?location_id='.$this->loc1->id,
            'stock-card' => '/api/v1/reports/stock-card/export?product_id='.$this->product->id.'&location_id='.$this->loc1->id.'&start_date=2026-08-01&end_date=2026-08-05',
            'stock-receipts' => '/api/v1/reports/stock-receipts/export',
            'stock-issues' => '/api/v1/reports/stock-issues/export',
            'stock-transfers' => '/api/v1/reports/stock-transfers/export',
            'stock-adjustments' => '/api/v1/reports/stock-adjustments/export',
            'stock-opnames' => '/api/v1/reports/stock-opnames/export',
        ];

        foreach ($endpoints as $slug => $url) {
            $response = $this->actingAs($this->staffLoc1, 'sanctum')->get($url);

            $response->assertStatus(200);
            $expectedFilename = "{$slug}-20260805-143045.csv";
            $disposition = $response->headers->get('Content-Disposition');
            $this->assertStringContainsString($expectedFilename, $disposition);
        }

        CarbonImmutable::setTestNow(null);
    }

    public function test_no_n_plus_one_query_count_does_not_scale_linearly()
    {
        Model::preventLazyLoading(true);

        // Create 10 receipt records
        for ($i = 1; $i <= 10; $i++) {
            $rc = StockReceipt::create(['receipt_number' => "N1-RC-00{$i}", 'date' => '2026-08-05', 'supplier_id' => $this->supplier->id, 'status' => 'POSTED', 'posted_at' => now(), 'created_by' => $this->admin->id]);
            StockReceiptItem::create(['stock_receipt_id' => $rc->id, 'product_id' => $this->product->id, 'location_id' => $this->loc1->id, 'quantity' => 10.0000]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->staffLoc1, 'sanctum')
            ->get('/api/v1/reports/stock-receipts/export')
            ->assertStatus(200);

        // Fetch streamed content to trigger iterator execution
        $content = $response->streamedContent();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();
        Model::preventLazyLoading(false);

        // 10 items processed in stream with direct SQL join query should execute exactly 1 SQL query
        $this->assertLessThanOrEqual(5, $queryCount);
    }
}
