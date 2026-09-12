<?php

namespace Tests\Feature\Dashboard;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
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
use App\Features\Location\Models\Location;
use App\Features\Supplier\Models\Supplier;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReadOnlyIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);
    }

    public function test_dashboard_endpoint_causes_zero_delta_in_balances_movements_and_transaction_statuses(): void
    {
        $location = Location::create(['code' => 'LOC-RO', 'name' => 'Loc RO', 'is_active' => true]);
        $loc2 = Location::create(['code' => 'LOC-RO2', 'name' => 'Loc RO2', 'is_active' => true]);
        $supplier = Supplier::create(['code' => 'SUP-RO', 'name' => 'Sup RO', 'is_active' => true]);

        $receipt = StockReceipt::create([
            'receipt_number' => 'RC-RO-001',
            'date' => now()->toDateString(),
            'supplier_id' => $supplier->id,
            'status' => ReceiptStatus::DRAFT->value,
            'created_by' => $this->admin->id,
        ]);
        $issue = StockIssue::create([
            'issue_number' => 'IS-RO-001',
            'date' => now()->toDateString(),
            'purpose' => 'Test Issue RO',
            'status' => IssueStatus::DRAFT->value,
            'created_by' => $this->admin->id,
        ]);
        $transfer = StockTransfer::create([
            'transfer_number' => 'TR-RO-001',
            'transfer_date' => now()->toDateString(),
            'date' => now()->toDateString(),
            'origin_location_id' => $location->id,
            'destination_location_id' => $loc2->id,
            'status' => TransferStatus::SENT->value,
            'created_by' => $this->admin->id,
        ]);
        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-RO-001',
            'adjustment_date' => now()->toDateString(),
            'date' => now()->toDateString(),
            'direction' => 'IN',
            'reason_code' => 'RECORDING_ERROR',
            'location_id' => $location->id,
            'status' => AdjustmentStatus::DRAFT->value,
            'created_by' => $this->admin->id,
        ]);
        $opname = StockOpname::create([
            'opname_number' => 'OP-RO-001',
            'opname_date' => now()->toDateString(),
            'location_id' => $location->id,
            'status' => OpnameStatus::IN_PROGRESS->value,
            'created_by' => $this->admin->id,
        ]);

        $movementCountBefore = StockMovement::count();
        $balanceCountBefore = InventoryBalance::count();

        // Call Dashboard 5 times
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($this->admin)->getJson('/api/v1/dashboard');
            $response->assertOk();
        }

        // Verify zero deltas
        $this->assertSame($movementCountBefore, StockMovement::count());
        $this->assertSame($balanceCountBefore, InventoryBalance::count());

        $this->assertSame(ReceiptStatus::DRAFT->value, $receipt->fresh()->status->value);
        $this->assertSame(IssueStatus::DRAFT->value, $issue->fresh()->status->value);
        $this->assertSame(TransferStatus::SENT->value, $transfer->fresh()->status->value);
        $this->assertSame(AdjustmentStatus::DRAFT->value, $adjustment->fresh()->status->value);
        $this->assertSame(OpnameStatus::IN_PROGRESS->value, $opname->fresh()->status->value);
    }
}
