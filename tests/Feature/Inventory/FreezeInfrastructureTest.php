<?php

namespace Tests\Feature\Inventory;

use App\Features\Auth\Models\User;
use App\Features\Inventory\Actions\PostStockAdjustmentAction;
use App\Features\Inventory\Actions\PostStockIssueAction;
use App\Features\Inventory\Actions\PostStockReceiptAction;
use App\Features\Inventory\Actions\ReceiveStockTransferAction;
use App\Features\Inventory\Actions\SendStockTransferAction;
use App\Features\Inventory\Enums\AdjustmentReason;
use App\Features\Inventory\Enums\AdjustmentStatus;
use App\Features\Inventory\Enums\IssueStatus;
use App\Features\Inventory\Enums\OpnameStatus;
use App\Features\Inventory\Enums\ReceiptStatus;
use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockAdjustment;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Inventory\Models\StockOpname;
use App\Features\Inventory\Models\StockReceipt;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Services\InventoryFreezeService;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Supplier\Models\Supplier;
use App\Shared\Exceptions\DomainException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FreezeInfrastructureTest extends TestCase
{
    use DatabaseMigrations;

    private InventoryFreezeService $freezeService;

    private Location $locationA;

    private Location $locationB;

    private Product $product;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeService = app(InventoryFreezeService::class);
        $this->locationA = Location::factory()->create(['is_active' => true]);
        $this->locationB = Location::factory()->create(['is_active' => true]);
        $this->product = Product::factory()->create(['is_active' => true]);
        $this->user = User::factory()->create();
        $this->user->locations()->attach([$this->locationA->id, $this->locationB->id]);

        // Create balance for location A
        InventoryBalance::create([
            'location_id' => $this->locationA->id,
            'product_id' => $this->product->id,
            'quantity' => '100.0000',
        ]);
    }

    private function createTestOpname(Location $location): StockOpname
    {
        static $seq = 1;

        return StockOpname::create([
            'opname_number' => 'SOP-FI-'.str_pad((string) ($seq++), 4, '0', STR_PAD_LEFT),
            'location_id' => $location->id,
            'opname_date' => now()->format('Y-m-d'),
            'status' => OpnameStatus::IN_PROGRESS,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_lock_row_auto_created_for_existing_and_new_locations()
    {
        $this->assertDatabaseHas('inventory_location_locks', ['location_id' => $this->locationA->id]);

        // Create brand new location
        $newLoc = Location::factory()->create(['is_active' => true]);

        $this->assertDatabaseHas('inventory_location_locks', ['location_id' => $newLoc->id]);
    }

    public function test_ensure_lock_rows_exist_is_safe_and_idempotent()
    {
        $newLoc = Location::factory()->create(['is_active' => true]);
        DB::table('inventory_location_locks')->where('location_id', $newLoc->id)->delete();

        $this->freezeService->ensureLockRowsExist([$newLoc->id]);
        $this->assertDatabaseHas('inventory_location_locks', ['location_id' => $newLoc->id, 'is_frozen' => false]);

        // Duplicate call should not fail
        $this->freezeService->ensureLockRowsExist([$newLoc->id]);
        $this->assertTrue(true);
    }

    public function test_freeze_and_unfreeze_by_owner_succeeds()
    {
        $opname = $this->createTestOpname($this->locationA);

        $this->freezeService->freezeLocation($this->locationA->id, $opname->id);
        $this->assertDatabaseHas('inventory_location_locks', [
            'location_id' => $this->locationA->id,
            'is_frozen' => true,
            'frozen_by_opname_id' => $opname->id,
        ]);

        // Unfreeze by correct owner
        $this->freezeService->unfreezeLocation($this->locationA->id, $opname->id);
        $this->assertDatabaseHas('inventory_location_locks', [
            'location_id' => $this->locationA->id,
            'is_frozen' => false,
            'frozen_by_opname_id' => null,
        ]);
    }

    public function test_unfreeze_by_different_owner_fails()
    {
        $opname1 = $this->createTestOpname($this->locationA);
        $opname2 = $this->createTestOpname($this->locationB);

        $this->freezeService->freezeLocation($this->locationA->id, $opname1->id);

        try {
            $this->freezeService->unfreezeLocation($this->locationA->id, $opname2->id);
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertEquals(409, $e->status());
        }
    }

    public function test_receipt_post_rejected_on_frozen_location()
    {
        $supplier = Supplier::factory()->create(['is_active' => true]);
        $opname = $this->createTestOpname($this->locationA);

        $receipt = StockReceipt::create([
            'receipt_number' => 'RCP-TEST-001',
            'supplier_id' => $supplier->id,
            'date' => now()->format('Y-m-d'),
            'status' => ReceiptStatus::DRAFT,
            'created_by' => $this->user->id,
        ]);
        $receipt->items()->create(['location_id' => $this->locationA->id, 'product_id' => $this->product->id, 'quantity' => '10.0000']);

        // Freeze location A
        $this->freezeService->freezeLocation($this->locationA->id, $opname->id);

        try {
            app(PostStockReceiptAction::class)->execute($receipt, $this->user->id);
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertEquals(409, $e->status());
            $this->assertEquals('LOCATION_FROZEN', $e->errors()['code'] ?? null);
        }

        // Verify balance unchanged and no movements created
        $balance = InventoryBalance::where('location_id', $this->locationA->id)->where('product_id', $this->product->id)->first();
        $this->assertEquals('100.0000', $balance->quantity);
        $this->assertEquals(0, StockMovement::count());
    }

    public function test_issue_post_rejected_on_frozen_location()
    {
        $opname = $this->createTestOpname($this->locationA);

        $issue = StockIssue::create([
            'issue_number' => 'ISS-TEST-001',
            'date' => now()->format('Y-m-d'),
            'purpose' => 'Operational',
            'status' => IssueStatus::DRAFT,
            'created_by' => $this->user->id,
        ]);
        $issue->items()->create(['location_id' => $this->locationA->id, 'product_id' => $this->product->id, 'quantity' => '10.0000']);

        $this->freezeService->freezeLocation($this->locationA->id, $opname->id);

        try {
            app(PostStockIssueAction::class)->execute($issue, $this->user->id);
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertEquals(409, $e->status());
            $this->assertEquals('LOCATION_FROZEN', $e->errors()['code'] ?? null);
        }
    }

    public function test_transfer_send_rejected_if_origin_frozen()
    {
        $opname = $this->createTestOpname($this->locationA);

        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-001',
            'origin_location_id' => $this->locationA->id,
            'destination_location_id' => $this->locationB->id,
            'transfer_date' => now()->format('Y-m-d'),
            'status' => TransferStatus::DRAFT,
            'created_by' => $this->user->id,
        ]);
        $transfer->items()->create(['product_id' => $this->product->id, 'quantity' => '10.0000']);

        // Freeze origin location A
        $this->freezeService->freezeLocation($this->locationA->id, $opname->id);

        try {
            app(SendStockTransferAction::class)->execute($transfer, $this->user->id);
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertEquals(409, $e->status());
            $this->assertEquals('LOCATION_FROZEN', $e->errors()['code'] ?? null);
        }
    }

    public function test_transfer_receive_rejected_if_destination_frozen()
    {
        $opname = $this->createTestOpname($this->locationB);

        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-002',
            'origin_location_id' => $this->locationA->id,
            'destination_location_id' => $this->locationB->id,
            'transfer_date' => now()->format('Y-m-d'),
            'status' => TransferStatus::SENT,
            'created_by' => $this->user->id,
        ]);
        $transfer->items()->create(['product_id' => $this->product->id, 'quantity' => '10.0000']);

        // Freeze destination location B
        $this->freezeService->freezeLocation($this->locationB->id, $opname->id);

        try {
            app(ReceiveStockTransferAction::class)->execute($transfer, $this->user->id);
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertEquals(409, $e->status());
            $this->assertEquals('LOCATION_FROZEN', $e->errors()['code'] ?? null);
        }
    }

    public function test_adjustment_post_rejected_on_frozen_location()
    {
        $creator = User::factory()->create();
        $opname = $this->createTestOpname($this->locationA);

        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-TEST-001',
            'location_id' => $this->locationA->id,
            'adjustment_date' => now()->format('Y-m-d'),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'status' => AdjustmentStatus::DRAFT,
            'created_by' => $creator->id,
        ]);
        $adjustment->items()->create(['product_id' => $this->product->id, 'quantity' => '10.0000']);

        $this->freezeService->freezeLocation($this->locationA->id, $opname->id);

        try {
            app(PostStockAdjustmentAction::class)->execute($adjustment, $this->user->id);
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertEquals(409, $e->status());
            $this->assertEquals('LOCATION_FROZEN', $e->errors()['code'] ?? null);
        }
    }

    public function test_unfrozen_location_can_transact_normally()
    {
        $supplier = Supplier::factory()->create(['is_active' => true]);
        $opname = $this->createTestOpname($this->locationA);

        $receipt = StockReceipt::create([
            'receipt_number' => 'RCP-TEST-002',
            'supplier_id' => $supplier->id,
            'date' => now()->format('Y-m-d'),
            'status' => ReceiptStatus::DRAFT,
            'created_by' => $this->user->id,
        ]);
        $receipt->items()->create(['location_id' => $this->locationA->id, 'product_id' => $this->product->id, 'quantity' => '10.0000']);

        // Freeze then unfreeze location A
        $this->freezeService->freezeLocation($this->locationA->id, $opname->id);
        $this->freezeService->unfreezeLocation($this->locationA->id, $opname->id);

        // Transaction should succeed
        app(PostStockReceiptAction::class)->execute($receipt, $this->user->id);

        $balance = InventoryBalance::where('location_id', $this->locationA->id)->where('product_id', $this->product->id)->first();
        $this->assertEquals('110.0000', $balance->quantity);
    }
}
