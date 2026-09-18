<?php

namespace Tests\Feature\Inventory;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\AdjustmentReason;
use App\Features\Inventory\Enums\AdjustmentStatus;
use App\Features\Inventory\Enums\IssueStatus;
use App\Features\Inventory\Enums\ReceiptStatus;
use App\Features\Inventory\Enums\StockCondition;
use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockAdjustment;
use App\Features\Inventory\Models\StockAdjustmentItem;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Models\StockIssueItem;
use App\Features\Inventory\Models\StockReceipt;
use App\Features\Inventory\Models\StockReceiptItem;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Models\StockTransferItem;
use App\Features\Location\Enums\LocationType;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DeleteInventoryDocumentsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected User $regularUser;

    protected Location $locationA;

    protected Location $locationB;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $uid = substr(uniqid(), -5);
        $category = Category::firstOrCreate(['code' => 'CAT-INVDEL-'.$uid], ['name' => 'Cat Del', 'is_active' => true]);
        $unit = Unit::firstOrCreate(['code' => 'PCS-INVDEL-'.$uid], ['name' => 'Pieces', 'symbol' => 'PCS', 'is_active' => true]);

        $this->product = Product::create([
            'sku' => 'SKU-INVDEL-'.$uid,
            'name' => 'Item Inv Del',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $adminRole = Role::firstOrCreate(
            ['code' => RoleCode::ADMIN->value],
            ['name' => 'Administrator']
        );

        $this->admin = User::factory()->create();
        $this->admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $this->regularUser = User::factory()->create();

        $this->locationA = Location::create([
            'code' => 'LOC-A-'.$uid,
            'name' => 'Gudang A '.$uid,
            'type' => LocationType::MAIN_WAREHOUSE->value,
            'is_active' => true,
        ]);

        $this->locationB = Location::create([
            'code' => 'LOC-B-'.$uid,
            'name' => 'Gudang B '.$uid,
            'type' => LocationType::MAIN_WAREHOUSE->value,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_delete_posted_stock_receipt_and_balance_is_deducted(): void
    {
        // Balance in Location A is 10.0000
        $balance = InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->locationA->id,
            'condition' => StockCondition::GOOD->value,
            'quantity' => '10.0000',
        ]);

        $receipt = StockReceipt::create([
            'receipt_number' => 'RCP-TEST-'.uniqid(),
            'date' => now()->toDateString(),
            'status' => ReceiptStatus::POSTED,
            'created_by' => $this->admin->id,
        ]);

        StockReceiptItem::create([
            'stock_receipt_id' => $receipt->id,
            'product_id' => $this->product->id,
            'location_id' => $this->locationA->id,
            'quantity' => '4.0000',
        ]);

        $response = $this->actingAs($this->admin)->deleteJson("/api/v1/stock-receipts/{$receipt->id}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('stock_receipts', ['id' => $receipt->id]);
        $balance->refresh();
        $this->assertEquals('6.0000', $balance->quantity);
    }

    public function test_admin_can_delete_posted_stock_issue_and_balance_is_refunded(): void
    {
        // Balance in Location A is 5.0000
        $balance = InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->locationA->id,
            'condition' => StockCondition::GOOD->value,
            'quantity' => '5.0000',
        ]);

        $issue = StockIssue::create([
            'issue_number' => 'ISS-TEST-'.uniqid(),
            'date' => now()->toDateString(),
            'purpose' => 'Test Pengeluaran',
            'status' => IssueStatus::POSTED,
            'created_by' => $this->admin->id,
        ]);

        StockIssueItem::create([
            'stock_issue_id' => $issue->id,
            'product_id' => $this->product->id,
            'location_id' => $this->locationA->id,
            'quantity' => '3.0000',
        ]);

        $response = $this->actingAs($this->admin)->deleteJson("/api/v1/stock-issues/{$issue->id}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('stock_issues', ['id' => $issue->id]);
        $balance->refresh();
        $this->assertEquals('8.0000', $balance->quantity);
    }

    public function test_admin_can_delete_received_stock_transfer_and_balances_are_reverted(): void
    {
        // Origin location had stock sent, Destination location received 2
        $origBalance = InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->locationA->id,
            'condition' => StockCondition::GOOD->value,
            'quantity' => '3.0000',
        ]);

        $destBalance = InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->locationB->id,
            'condition' => StockCondition::GOOD->value,
            'quantity' => '2.0000',
        ]);

        $transfer = StockTransfer::create([
            'transfer_number' => 'TRF-TEST-'.uniqid(),
            'transfer_date' => now()->toDateString(),
            'origin_location_id' => $this->locationA->id,
            'destination_location_id' => $this->locationB->id,
            'status' => TransferStatus::RECEIVED,
            'created_by' => $this->admin->id,
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $this->product->id,
            'quantity' => '2.0000',
            'received_quantity' => '2.0000',
        ]);

        $response = $this->actingAs($this->admin)->deleteJson("/api/v1/stock-transfers/{$transfer->id}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('stock_transfers', ['id' => $transfer->id]);
        $origBalance->refresh();
        $destBalance->refresh();

        $this->assertEquals('5.0000', $origBalance->quantity, 'Origin gets 2 back');
        $this->assertEquals('0.0000', $destBalance->quantity, 'Destination has 2 deducted');
    }

    public function test_admin_can_delete_posted_stock_adjustment(): void
    {
        // Adjustment was an INCREASE of 4, current balance 6
        $balance = InventoryBalance::create([
            'product_id' => $this->product->id,
            'location_id' => $this->locationA->id,
            'condition' => StockCondition::GOOD->value,
            'quantity' => '6.0000',
        ]);

        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-TEST-'.uniqid(),
            'location_id' => $this->locationA->id,
            'adjustment_date' => now()->toDateString(),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'status' => AdjustmentStatus::POSTED,
            'created_by' => $this->admin->id,
        ]);

        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $this->product->id,
            'quantity' => '4.0000',
        ]);

        $response = $this->actingAs($this->admin)->deleteJson("/api/v1/stock-adjustments/{$adjustment->id}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('stock_adjustments', ['id' => $adjustment->id]);
        $balance->refresh();
        $this->assertEquals('2.0000', $balance->quantity);
    }

    public function test_non_admin_cannot_delete_inventory_documents(): void
    {
        $receipt = StockReceipt::create([
            'receipt_number' => 'RCP-NA-'.uniqid(),
            'date' => now()->toDateString(),
            'status' => ReceiptStatus::DRAFT,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->regularUser)->deleteJson("/api/v1/stock-receipts/{$receipt->id}");
        $response->assertStatus(403);
    }
}
