<?php

namespace Tests\Feature\Dashboard;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\AdjustmentStatus;
use App\Features\Inventory\Enums\IssueStatus;
use App\Features\Inventory\Enums\OpnameStatus;
use App\Features\Inventory\Enums\ReceiptStatus;
use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Inventory\Models\StockAdjustment;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Models\StockIssueItem;
use App\Features\Inventory\Models\StockOpname;
use App\Features\Inventory\Models\StockReceipt;
use App\Features\Inventory\Models\StockReceiptItem;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Supplier\Models\Supplier;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardOperationalQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Location $location;

    private Product $product;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->location = Location::create(['code' => 'LOC-QUEUE', 'name' => 'Loc Queue', 'is_active' => true]);

        $cat = Category::create(['code' => 'CAT-Q', 'name' => 'Cat Q', 'is_active' => true]);
        $unit = Unit::create(['code' => 'UNT-Q', 'name' => 'Unit Q', 'symbol' => 'uq', 'is_active' => true]);
        $this->product = Product::create([
            'sku' => 'PRD-QUEUE-001',
            'name' => 'Product Queue',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'code' => 'SUP-Q',
            'name' => 'Supplier Q',
            'is_active' => true,
        ]);
    }

    public function test_operational_queue_counts_transactions_in_correct_statuses(): void
    {
        // 1. Receipt: 1 Draft, 1 Posted
        $r1 = StockReceipt::create([
            'receipt_number' => 'RC-Q-001',
            'date' => now()->toDateString(),
            'supplier_id' => $this->supplier->id,
            'status' => ReceiptStatus::DRAFT->value,
            'created_by' => $this->admin->id,
        ]);
        StockReceiptItem::create(['stock_receipt_id' => $r1->id, 'product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => '10.0000']);

        $r2 = StockReceipt::create([
            'receipt_number' => 'RC-Q-002',
            'date' => now()->toDateString(),
            'supplier_id' => $this->supplier->id,
            'status' => ReceiptStatus::POSTED->value,
            'created_by' => $this->admin->id,
            'posted_at' => now(),
        ]);
        StockReceiptItem::create(['stock_receipt_id' => $r2->id, 'product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => '10.0000']);

        // 2. Issue: 1 Draft, 1 Posted
        $i1 = StockIssue::create([
            'issue_number' => 'IS-Q-001',
            'date' => now()->toDateString(),
            'purpose' => 'Test Issue 1',
            'status' => IssueStatus::DRAFT->value,
            'created_by' => $this->admin->id,
        ]);
        StockIssueItem::create(['stock_issue_id' => $i1->id, 'product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => '5.0000']);

        $i2 = StockIssue::create([
            'issue_number' => 'IS-Q-002',
            'date' => now()->toDateString(),
            'purpose' => 'Test Issue 2',
            'status' => IssueStatus::POSTED->value,
            'created_by' => $this->admin->id,
            'posted_at' => now(),
        ]);
        StockIssueItem::create(['stock_issue_id' => $i2->id, 'product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => '5.0000']);

        // 3. Transfer: 1 SENT (awaiting receipt), 1 RECEIVED
        $loc2 = Location::create(['code' => 'LOC-Q2', 'name' => 'Loc Q2', 'is_active' => true]);

        $this->admin->locations()->attach([$this->location->id, $loc2->id]);
        StockTransfer::create([
            'transfer_number' => 'TR-Q-001',
            'transfer_date' => now()->toDateString(),
            'date' => now()->toDateString(),
            'origin_location_id' => $this->location->id,
            'destination_location_id' => $loc2->id,
            'status' => TransferStatus::SENT->value,
            'created_by' => $this->admin->id,
        ]);
        StockTransfer::create([
            'transfer_number' => 'TR-Q-002',
            'transfer_date' => now()->toDateString(),
            'date' => now()->toDateString(),
            'origin_location_id' => $this->location->id,
            'destination_location_id' => $loc2->id,
            'status' => TransferStatus::RECEIVED->value,
            'created_by' => $this->admin->id,
            'received_at' => now(),
        ]);

        // 4. Adjustment: 1 DRAFT (pending), 1 POSTED
        StockAdjustment::create([
            'adjustment_number' => 'ADJ-Q-001',
            'adjustment_date' => now()->toDateString(),
            'date' => now()->toDateString(),
            'direction' => 'IN',
            'reason_code' => 'RECORDING_ERROR',
            'location_id' => $this->location->id,
            'status' => AdjustmentStatus::DRAFT->value,
            'created_by' => $this->admin->id,
        ]);
        StockAdjustment::create([
            'adjustment_number' => 'ADJ-Q-002',
            'adjustment_date' => now()->toDateString(),
            'date' => now()->toDateString(),
            'direction' => 'IN',
            'reason_code' => 'RECORDING_ERROR',
            'location_id' => $this->location->id,
            'status' => AdjustmentStatus::POSTED->value,
            'created_by' => $this->admin->id,
            'posted_at' => now(),
        ]);

        // 5. Opname: 1 IN_PROGRESS, 1 COUNTED (in loc2)
        StockOpname::create([
            'opname_number' => 'OP-Q-001',
            'opname_date' => now()->toDateString(),
            'location_id' => $this->location->id,
            'status' => OpnameStatus::IN_PROGRESS->value,
            'created_by' => $this->admin->id,
        ]);
        StockOpname::create([
            'opname_number' => 'OP-Q-002',
            'opname_date' => now()->toDateString(),
            'location_id' => $loc2->id,
            'status' => OpnameStatus::COUNTED->value,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/dashboard');
        $response->assertOk();

        $queue = $response->json('data.operational_queue');

        $this->assertSame(1, $queue['receipt_draft_count']);
        $this->assertSame(1, $queue['issue_draft_count']);
        $this->assertSame(1, $queue['transfer_awaiting_receipt_count']);
        $this->assertSame(1, $queue['adjustment_pending_count']);
        $this->assertSame(1, $queue['opname_in_progress_count']);
        $this->assertSame(1, $queue['opname_awaiting_post_count']);
    }
}
