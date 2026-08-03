<?php

namespace Tests\Feature\Reporting;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\MovementType;
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
use App\Features\Reporting\Helpers\DecimalQuantity;
use App\Features\Supplier\Models\Supplier;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ReportingPhase8A2Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staffLoc1;

    private Location $loc1;

    private Location $loc2;

    private Category $category;

    private Unit $unit;

    private Product $prod1;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create(['username' => 'admin_8a2']);
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole->id);

        $this->loc1 = Location::factory()->create(['code' => 'L8A2-01', 'name' => 'Loc 1']);
        $this->loc2 = Location::factory()->create(['code' => 'L8A2-02', 'name' => 'Loc 2']);
        $this->admin->locations()->attach([$this->loc1->id, $this->loc2->id]);

        $this->staffLoc1 = User::factory()->create(['username' => 'staff_loc1']);
        $staffRole = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();
        $this->staffLoc1->roles()->attach($staffRole->id);

        // Sync Phase 8A2 permissions to staff
        $perms = Permission::whereIn('code', [
            PermissionCode::REPORTS_STOCK_RECEIPTS_VIEW->value,
            PermissionCode::REPORTS_STOCK_ISSUES_VIEW->value,
            PermissionCode::REPORTS_STOCK_TRANSFERS_VIEW->value,
            PermissionCode::REPORTS_STOCK_ADJUSTMENTS_VIEW->value,
            PermissionCode::REPORTS_STOCK_OPNAMES_VIEW->value,
        ])->get();
        $staffRole->permissions()->syncWithoutDetaching($perms->pluck('id')->toArray());
        $this->staffLoc1->locations()->attach([$this->loc1->id]);

        $this->category = Category::factory()->create(['name' => 'Electronics']);
        $this->unit = Unit::factory()->create(['name' => 'Box', 'symbol' => 'Box']);
        $this->prod1 = Product::factory()->create([
            'sku' => 'P8A2-001',
            'name' => 'Test Item 8A2',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        $this->supplier = Supplier::factory()->create(['name' => 'Vendor ABC']);
    }

    public function test_returns_receipt_report_filtered_by_location_and_scoping()
    {
        $receipt = StockReceipt::create([
            'receipt_number' => 'RC-8A2-001',
            'supplier_id' => $this->supplier->id,
            'location_id' => $this->loc1->id,
            'date' => '2026-08-01',
            'status' => 'POSTED',
            'created_by' => $this->admin->id,
        ]);

        $item = StockReceiptItem::create([
            'stock_receipt_id' => $receipt->id,
            'location_id' => $this->loc1->id,
            'product_id' => $this->prod1->id,
            'quantity' => 50.0000,
        ]);

        StockMovement::create([
            'movement_id' => 'MOV-8A2-001',
            'movement_type' => MovementType::RECEIPT->value,
            'reference_type' => StockReceipt::class,
            'reference_id' => $receipt->id,
            'location_id' => $this->loc1->id,
            'product_id' => $this->prod1->id,
            'quantity' => 50.0000,
            'quantity_change' => 50.0000,
            'quantity_before' => 0.0000,
            'quantity_after' => 50.0000,
            'occurred_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        // Unauthenticated access
        $this->getJson('/api/v1/reports/stock-receipts')->assertStatus(401);

        // Authenticated loc1 staff access
        $response = $this->actingAs($this->staffLoc1, 'sanctum')
            ->getJson('/api/v1/reports/stock-receipts')
            ->assertStatus(200)
            ->assertJsonStructure([
                'meta' => ['summary' => ['total_rows', 'total_documents', 'quantity_by_unit']],
                'data' => [
                    '*' => [
                        'item_id', 'receipt_id', 'receipt_number', 'document_date',
                        'posted_at', 'location', 'supplier', 'product',
                        'quantity', 'created_by', 'posted_by', 'notes',
                    ],
                ],
                'pagination',
            ]);

        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('50.0000', $response->json('data.0.quantity'));
    }

    public function test_returns_issue_report_filtered_by_location_scoping()
    {
        $issue = StockIssue::create([
            'issue_number' => 'IS-8A2-001',
            'purpose' => 'Production Use',
            'date' => '2026-08-01',
            'status' => 'POSTED',
            'created_by' => $this->admin->id,
        ]);

        $item = StockIssueItem::create([
            'stock_issue_id' => $issue->id,
            'product_id' => $this->prod1->id,
            'location_id' => $this->loc1->id,
            'quantity' => 15.0000,
        ]);

        StockMovement::create([
            'movement_id' => 'MOV-8A2-002',
            'movement_type' => MovementType::ISSUE->value,
            'reference_type' => StockIssue::class,
            'reference_id' => $issue->id,
            'location_id' => $this->loc1->id,
            'product_id' => $this->prod1->id,
            'quantity' => -15.0000,
            'quantity_change' => -15.0000,
            'quantity_before' => 50.0000,
            'quantity_after' => 35.0000,
            'occurred_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->staffLoc1, 'sanctum')
            ->getJson('/api/v1/reports/stock-issues')
            ->assertStatus(200);

        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('15.0000', $response->json('data.0.quantity'));
    }

    public function test_returns_transfer_report_with_either_origin_or_destination_scope()
    {
        $transfer = StockTransfer::create([
            'transfer_number' => 'TR-8A2-001',
            'origin_location_id' => $this->loc1->id,
            'destination_location_id' => $this->loc2->id,
            'transfer_date' => '2026-08-01',
            'status' => 'SENT',
            'sent_at' => now(),
            'sent_by' => $this->admin->id,
            'created_by' => $this->admin->id,
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->prod1->id,
            'quantity' => 10.0000,
        ]);

        $response = $this->actingAs($this->staffLoc1, 'sanctum')
            ->getJson('/api/v1/reports/stock-transfers')
            ->assertStatus(200);

        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('10.0000', $response->json('data.0.quantity'));
        $this->assertEquals('SENT', $response->json('data.0.status'));
    }

    public function test_returns_adjustment_report_correctly()
    {
        $adj = StockAdjustment::create([
            'adjustment_number' => 'ADJ-8A2-001',
            'location_id' => $this->loc1->id,
            'adjustment_date' => '2026-08-01',
            'direction' => 'IN',
            'reason_code' => 'FOUND',
            'status' => 'POSTED',
            'posted_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adj->id,
            'product_id' => $this->prod1->id,
            'type' => 'ADDITION',
            'quantity' => 5.0000,
            'reason' => 'Found lost stock',
        ]);

        $response = $this->actingAs($this->staffLoc1, 'sanctum')
            ->getJson('/api/v1/reports/stock-adjustments')
            ->assertStatus(200);

        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('IN', $response->json('data.0.direction'));
        $this->assertEquals('5.0000', $response->json('data.0.quantity'));
    }

    public function test_returns_opname_report_correctly()
    {
        $opname = StockOpname::create([
            'opname_number' => 'OP-8A2-001',
            'location_id' => $this->loc1->id,
            'opname_date' => '2026-08-01',
            'status' => 'POSTED',
            'posted_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'product_id' => $this->prod1->id,
            'snapshot_quantity' => 35.0000,
            'counted_quantity' => 37.0000,
            'variance_quantity' => 2.0000,
            'item_notes' => 'Surplus item found',
        ]);

        $response = $this->actingAs($this->staffLoc1, 'sanctum')
            ->getJson('/api/v1/reports/stock-opnames')
            ->assertStatus(200);

        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('35.0000', $response->json('data.0.snapshot_quantity'));
        $this->assertEquals('37.0000', $response->json('data.0.counted_quantity'));
        $this->assertEquals('2.0000', $response->json('data.0.signed_variance'));
    }

    public function test_validates_max_date_range_limit_366_days()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/stock-receipts?start_date=2024-01-01&end_date=2025-06-01')
            ->assertStatus(422);

        $response->assertJsonValidationErrors(['end_date']);
    }

    public function test_decimal_precision_retains_extreme_scale_and_signed_variance()
    {
        $receipt = StockReceipt::create([
            'receipt_number' => 'REC-DEC-001',
            'supplier_id' => $this->supplier->id,
            'date' => '2026-08-01',
            'status' => 'POSTED',
            'created_by' => $this->admin->id,
        ]);

        StockReceiptItem::create([
            'stock_receipt_id' => $receipt->id,
            'product_id' => $this->prod1->id,
            'location_id' => $this->loc1->id,
            'quantity' => '9999999999.9999',
        ]);

        StockMovement::create([
            'movement_id' => (string) Str::uuid(),
            'product_id' => $this->prod1->id,
            'location_id' => $this->loc1->id,
            'movement_type' => MovementType::RECEIPT->value,
            'quantity' => '9999999999.9999',
            'quantity_before' => '0.0000',
            'quantity_after' => '9999999999.9999',
            'reference_type' => StockReceipt::class,
            'reference_id' => $receipt->id,
            'reference_number' => $receipt->receipt_number,
            'occurred_at' => now(),
            'created_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->staffLoc1, 'sanctum')
            ->getJson('/api/v1/reports/stock-receipts')
            ->assertStatus(200);

        $this->assertEquals('9999999999.9999', $response->json('data.0.quantity'));

        // Signed negative variance opname test
        $opname = StockOpname::create([
            'opname_number' => 'OP-NEG-001',
            'location_id' => $this->loc1->id,
            'opname_date' => '2026-08-01',
            'status' => 'POSTED',
            'posted_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'product_id' => $this->prod1->id,
            'snapshot_quantity' => '10.0000',
            'counted_quantity' => '0.0001',
            'variance_quantity' => '-9.9999',
            'item_notes' => 'Shortage',
        ]);

        $opnameResponse = $this->actingAs($this->staffLoc1, 'sanctum')
            ->getJson('/api/v1/reports/stock-opnames')
            ->assertStatus(200);

        $this->assertEquals('-9.9999', $opnameResponse->json('data.0.signed_variance'));
        $this->assertEquals('0.0001', $opnameResponse->json('data.0.counted_quantity'));
    }

    public function test_decimal_quantity_helper_strict_type_and_normalization_rules()
    {
        $this->assertSame('0.0000', DecimalQuantity::normalize(null));
        $this->assertSame('0.0000', DecimalQuantity::normalize('0'));
        $this->assertSame('0.0000', DecimalQuantity::normalize('0.0'));
        $this->assertSame('0.0000', DecimalQuantity::normalize('0.0000'));
        $this->assertSame('0.0000', DecimalQuantity::normalize('-0'));
        $this->assertSame('0.0000', DecimalQuantity::normalize('-0.0'));
        $this->assertSame('0.0000', DecimalQuantity::normalize('-0.0000'));
        $this->assertSame('0.0001', DecimalQuantity::normalize('0.0001'));
        $this->assertSame('-0.0001', DecimalQuantity::normalize('-0.0001'));
        $this->assertSame('-9.9999', DecimalQuantity::normalize('-9.9999'));
        $this->assertSame('9999999999.9999', DecimalQuantity::normalize('9999999999.9999'));
    }

    public function test_decimal_quantity_helper_rejects_empty_and_invalid_strings()
    {
        $invalidInputs = ['', ' ', '   ', 'abc', '1,25', '1.2.3', '--1', '+-1', 'NaN', 'INF', '1e4'];

        foreach ($invalidInputs as $invalid) {
            try {
                DecimalQuantity::normalize($invalid);
                $this->fail("Expected InvalidArgumentException for input [{$invalid}]");
            } catch (\InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_decimal_quantity_helper_rejects_float_and_non_string_types()
    {
        $invalidTypes = [0, 1, 0.1, true, false, [], new \stdClass];

        foreach ($invalidTypes as $invalid) {
            try {
                DecimalQuantity::normalize($invalid);
                $this->fail('Expected TypeError for non-string input of type '.gettype($invalid));
            } catch (\TypeError $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_decimal_quantity_helper_rejects_float_from_non_strict_caller_subprocess()
    {
        $process = new Process([
            PHP_BINARY,
            '-r',
            "require 'vendor/autoload.php'; try { \App\Features\Reporting\Helpers\DecimalQuantity::normalize(0.1); echo 'FAIL_ACCEPTED_FLOAT'; } catch (TypeError \$e) { echo 'PASS_TYPE_ERROR'; } catch (Throwable \$e) { echo 'OTHER_ERR:' . \$e->getMessage(); }",
        ], base_path());

        $process->run();

        $this->assertSame(0, $process->getExitCode());
        $this->assertSame('PASS_TYPE_ERROR', $process->getOutput());
    }

    public function test_decimal_quantity_helper_rejects_int_from_non_strict_caller_subprocess()
    {
        $process = new Process([
            PHP_BINARY,
            '-r',
            "require 'vendor/autoload.php'; try { \App\Features\Reporting\Helpers\DecimalQuantity::normalize(1); echo 'FAIL_ACCEPTED_INT'; } catch (TypeError \$e) { echo 'PASS_TYPE_ERROR'; } catch (Throwable \$e) { echo 'OTHER_ERR:' . \$e->getMessage(); }",
        ], base_path());

        $process->run();

        $this->assertSame(0, $process->getExitCode());
        $this->assertSame('PASS_TYPE_ERROR', $process->getOutput());
    }
}
