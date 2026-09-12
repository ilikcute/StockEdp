<?php

namespace Tests\Feature\Integrity;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Actions\CompleteStockOpnameAction;
use App\Features\Inventory\Actions\CreateStockAdjustmentAction;
use App\Features\Inventory\Actions\CreateStockIssueAction;
use App\Features\Inventory\Actions\CreateStockOpnameAction;
use App\Features\Inventory\Actions\CreateStockReceiptAction;
use App\Features\Inventory\Actions\CreateStockTransferAction;
use App\Features\Inventory\Actions\InputCountAction;
use App\Features\Inventory\Actions\PostStockAdjustmentAction;
use App\Features\Inventory\Actions\PostStockIssueAction;
use App\Features\Inventory\Actions\PostStockOpnameAction;
use App\Features\Inventory\Actions\PostStockReceiptAction;
use App\Features\Inventory\Actions\ReceiveStockTransferAction;
use App\Features\Inventory\Actions\SendStockTransferAction;
use App\Features\Inventory\Actions\StartStockOpnameAction;
use App\Features\Inventory\Enums\AdjustmentReason;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Reporting\Helpers\DecimalQuantity;
use App\Features\Supplier\Models\Supplier;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceMovementReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $supervisor;

    private Location $loc1;

    private Location $loc2;

    private Product $prod1;

    private Product $prod2;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $supervisorRole = Role::where('code', RoleCode::INVENTORY_SUPERVISOR->value)->first();

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->attach($adminRole->id);

        $this->supervisor = User::factory()->create(['is_active' => true]);
        $this->supervisor->roles()->attach($supervisorRole->id);

        $this->loc1 = Location::create([
            'name' => 'Gudang Utama',
            'code' => 'LOC-01',
            'is_active' => true,
        ]);

        $this->loc2 = Location::create([
            'name' => 'Gudang Cabang',
            'code' => 'LOC-02',
            'is_active' => true,
        ]);

        $this->admin->locations()->attach([$this->loc1->id, $this->loc2->id]);
        $this->supervisor->locations()->attach([$this->loc1->id, $this->loc2->id]);

        $cat = Category::create(['name' => 'General', 'code' => 'GEN', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Pcs', 'code' => 'PCS', 'symbol' => 'pcs', 'is_active' => true]);

        $this->supplier = Supplier::create([
            'name' => 'Supplier Utama',
            'code' => 'SUP-01',
            'is_active' => true,
        ]);

        $this->prod1 = Product::create([
            'name' => 'Produk A',
            'sku' => 'SKU-001',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'is_active' => true,
            'minimum_stock' => '5.0000',
        ]);

        $this->prod2 = Product::create([
            'name' => 'Produk B',
            'sku' => 'SKU-002',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'is_active' => true,
            'minimum_stock' => '5.0000',
        ]);
    }

    public function test_balance_matches_movement_delta_aggregate_across_all_transaction_types(): void
    {
        // 1. RECEIPT: Prod1 @ Loc1 (+100.0000)
        $receipt = app(CreateStockReceiptAction::class)->execute([
            'supplier_id' => $this->supplier->id,
            'date' => now()->toDateString(),
            'notes' => 'Penerimaan Awal',
            'items' => [
                ['product_id' => $this->prod1->id, 'location_id' => $this->loc1->id, 'quantity' => '100.0000'],
            ],
        ], $this->admin->id);
        app(PostStockReceiptAction::class)->execute($receipt, $this->admin->id);

        // 2. ISSUE: Prod1 @ Loc1 (-20.0000)
        $issue = app(CreateStockIssueAction::class)->execute([
            'purpose' => 'Pengeluaran Operasional',
            'date' => now()->toDateString(),
            'notes' => 'Pengeluaran Produk',
            'items' => [
                ['product_id' => $this->prod1->id, 'location_id' => $this->loc1->id, 'quantity' => '20.0000'],
            ],
        ], $this->admin->id);
        app(PostStockIssueAction::class)->execute($issue, $this->admin->id);

        // 3. TRANSFER: Prod1 @ Loc1 -> Loc2 (30.0000)
        $transfer = app(CreateStockTransferAction::class)->execute([
            'origin_location_id' => $this->loc1->id,
            'destination_location_id' => $this->loc2->id,
            'transfer_date' => now()->toDateString(),
            'notes' => 'Transfer Cabang',
            'items' => [
                ['product_id' => $this->prod1->id, 'quantity' => '30.0000'],
            ],
        ], $this->admin->id);
        app(SendStockTransferAction::class)->execute($transfer, $this->admin->id);
        app(ReceiveStockTransferAction::class)->execute($transfer, $this->admin->id);

        // 4. ADJUSTMENT IN: Prod1 @ Loc1 (+5.0000)
        $adjIn = app(CreateStockAdjustmentAction::class)->execute([
            'location_id' => $this->loc1->id,
            'adjustment_date' => now()->toDateString(),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'notes' => 'Penyesuaian Masuk',
            'items' => [
                ['product_id' => $this->prod1->id, 'quantity' => '5.0000'],
            ],
        ], $this->admin->id);
        app(PostStockAdjustmentAction::class)->execute($adjIn, $this->supervisor->id);

        // 5. ADJUSTMENT OUT: Prod2 @ Loc2 (-2.0000) - First create receipt so balance exists
        $receiptProd2 = app(CreateStockReceiptAction::class)->execute([
            'supplier_id' => $this->supplier->id,
            'date' => now()->toDateString(),
            'notes' => 'Penerimaan Prod 2',
            'items' => [
                ['product_id' => $this->prod2->id, 'location_id' => $this->loc2->id, 'quantity' => '10.0000'],
            ],
        ], $this->admin->id);
        app(PostStockReceiptAction::class)->execute($receiptProd2, $this->admin->id);

        $adjOut = app(CreateStockAdjustmentAction::class)->execute([
            'location_id' => $this->loc2->id,
            'adjustment_date' => now()->toDateString(),
            'direction' => 'DECREASE',
            'reason_code' => AdjustmentReason::DAMAGED->value,
            'notes' => 'Penyesuaian Rusak',
            'items' => [
                ['product_id' => $this->prod2->id, 'quantity' => '2.0000'],
            ],
        ], $this->admin->id);
        app(PostStockAdjustmentAction::class)->execute($adjOut, $this->supervisor->id);

        // 6. OPNAME SURPLUS: Prod1 @ Loc1 (+3.0000)
        $opname1 = app(CreateStockOpnameAction::class)->execute([
            'location_id' => $this->loc1->id,
            'opname_date' => now()->toDateString(),
            'notes' => 'Opname Surplus',
        ], $this->admin->id);
        $opname1 = app(StartStockOpnameAction::class)->execute($opname1, $this->admin->id);
        foreach ($opname1->items as $item) {
            $count = $item->product_id === $this->prod1->id ? '58.0000' : (string) $item->snapshot_quantity;
            app(InputCountAction::class)->execute($opname1, $item->id, ['counted_quantity' => $count], $this->admin->id);
        }
        app(CompleteStockOpnameAction::class)->execute($opname1, $this->admin->id);
        app(PostStockOpnameAction::class)->execute($opname1, $this->supervisor->id);

        // 7. OPNAME SHORTAGE (OPNAME_OUT): Prod2 @ Loc2 (-3.0000) (Snapshot: 8.0000, Counted: 5.0000)
        $opname2 = app(CreateStockOpnameAction::class)->execute([
            'location_id' => $this->loc2->id,
            'opname_date' => now()->toDateString(),
            'notes' => 'Opname Shortage',
        ], $this->admin->id);
        $opname2 = app(StartStockOpnameAction::class)->execute($opname2, $this->admin->id);
        foreach ($opname2->items as $item) {
            $count = $item->product_id === $this->prod2->id ? '5.0000' : (string) $item->snapshot_quantity;
            app(InputCountAction::class)->execute($opname2, $item->id, ['counted_quantity' => $count], $this->admin->id);
        }
        app(CompleteStockOpnameAction::class)->execute($opname2, $this->admin->id);
        app(PostStockOpnameAction::class)->execute($opname2, $this->supervisor->id);

        // VERIFIKASI REKONSILIASI UNTUK SEMUA PAIR (PRODUCT, LOCATION)
        $balances = InventoryBalance::all();
        $this->assertGreaterThan(0, $balances->count());

        foreach ($balances as $balance) {
            $movements = StockMovement::where('product_id', $balance->product_id)
                ->where('location_id', $balance->location_id)
                ->orderBy('id', 'asc')
                ->get();

            $aggregateDelta = '0.0000';
            foreach ($movements as $m) {
                $delta = bcsub((string) $m->quantity_after, (string) $m->quantity_before, 4);
                $aggregateDelta = bcadd($aggregateDelta, $delta, 4);
            }

            $normalizedBalance = DecimalQuantity::normalize($balance->quantity);
            $normalizedAggregate = DecimalQuantity::normalize($aggregateDelta);

            $this->assertEquals(
                $normalizedBalance,
                $normalizedAggregate,
                "Saldo pada InventoryBalance ({$normalizedBalance}) tidak cocok dengan agregat movement ({$normalizedAggregate}) untuk product_id={$balance->product_id}, location_id={$balance->location_id}"
            );
        }
    }

    public function test_opname_shortage_creates_opname_out_movement(): void
    {
        // 1. Initial Receipt: Prod1 @ Loc1 = 10.0000
        $receipt = app(CreateStockReceiptAction::class)->execute([
            'supplier_id' => $this->supplier->id,
            'date' => now()->toDateString(),
            'notes' => 'Penerimaan Awal',
            'items' => [
                ['product_id' => $this->prod1->id, 'location_id' => $this->loc1->id, 'quantity' => '10.0000'],
            ],
        ], $this->admin->id);
        app(PostStockReceiptAction::class)->execute($receipt, $this->admin->id);

        // 2. Start Opname (Snapshot = 10.0000)
        $opname = app(CreateStockOpnameAction::class)->execute([
            'location_id' => $this->loc1->id,
            'opname_date' => now()->toDateString(),
            'notes' => 'Opname Tes Shortage',
        ], $this->admin->id);
        $opname = app(StartStockOpnameAction::class)->execute($opname, $this->admin->id);

        foreach ($opname->items as $item) {
            $count = $item->product_id === $this->prod1->id ? '7.0000' : (string) $item->snapshot_quantity;
            app(InputCountAction::class)->execute($opname, $item->id, ['counted_quantity' => $count], $this->admin->id);
        }

        app(CompleteStockOpnameAction::class)->execute($opname, $this->admin->id);
        app(PostStockOpnameAction::class)->execute($opname, $this->supervisor->id);

        // 4. Assert OPNAME_OUT StockMovement created with negative delta
        $movement = StockMovement::where('product_id', $this->prod1->id)
            ->where('location_id', $this->loc1->id)
            ->where('movement_type', MovementType::OPNAME_OUT->value)
            ->first();

        $this->assertNotNull($movement, 'StockMovement dengan movement_type OPNAME_OUT harus dibuat saat opname shortage');
        $this->assertEquals('10.0000', DecimalQuantity::normalize($movement->quantity_before));
        $this->assertEquals('7.0000', DecimalQuantity::normalize($movement->quantity_after));

        $delta = bcsub((string) $movement->quantity_after, (string) $movement->quantity_before, 4);
        $this->assertEquals('-3.0000', DecimalQuantity::normalize($delta));

        // 5. Assert final balance updated to 7.0000
        $balance = InventoryBalance::where('product_id', $this->prod1->id)
            ->where('location_id', $this->loc1->id)
            ->first();
        $this->assertEquals('7.0000', DecimalQuantity::normalize($balance->quantity));
    }

    public function test_movement_continuity_across_all_distinct_ledgers(): void
    {
        // Fixture: Multi-product and multi-location transactions
        $receipt1 = app(CreateStockReceiptAction::class)->execute([
            'supplier_id' => $this->supplier->id,
            'date' => now()->toDateString(),
            'notes' => 'Penerimaan Multi 1',
            'items' => [
                ['product_id' => $this->prod1->id, 'location_id' => $this->loc1->id, 'quantity' => '50.0000'],
                ['product_id' => $this->prod2->id, 'location_id' => $this->loc2->id, 'quantity' => '30.0000'],
            ],
        ], $this->admin->id);
        app(PostStockReceiptAction::class)->execute($receipt1, $this->admin->id);

        $issue1 = app(CreateStockIssueAction::class)->execute([
            'purpose' => 'Pengeluaran Test Multi',
            'date' => now()->toDateString(),
            'notes' => 'Pengeluaran 1',
            'items' => [
                ['product_id' => $this->prod1->id, 'location_id' => $this->loc1->id, 'quantity' => '15.0000'],
                ['product_id' => $this->prod2->id, 'location_id' => $this->loc2->id, 'quantity' => '5.0000'],
            ],
        ], $this->admin->id);
        app(PostStockIssueAction::class)->execute($issue1, $this->admin->id);

        $transfer = app(CreateStockTransferAction::class)->execute([
            'origin_location_id' => $this->loc1->id,
            'destination_location_id' => $this->loc2->id,
            'transfer_date' => now()->toDateString(),
            'notes' => 'Transfer Multi',
            'items' => [
                ['product_id' => $this->prod1->id, 'quantity' => '10.0000'],
            ],
        ], $this->admin->id);
        app(SendStockTransferAction::class)->execute($transfer, $this->admin->id);
        app(ReceiveStockTransferAction::class)->execute($transfer, $this->admin->id);

        // Ambil seluruh pasangan unik (product_id, location_id)
        $pairs = StockMovement::query()
            ->select('product_id', 'location_id')
            ->distinct()
            ->get();

        $this->assertGreaterThan(0, $pairs->count());

        foreach ($pairs as $pair) {
            $this->assertLedgerContinuity((int) $pair->product_id, (int) $pair->location_id);
        }
    }

    public function test_drift_detection_identifies_corrupted_balance(): void
    {
        // Setup valid balance
        $receipt = app(CreateStockReceiptAction::class)->execute([
            'supplier_id' => $this->supplier->id,
            'date' => now()->toDateString(),
            'notes' => 'Penerimaan Tes Drift',
            'items' => [
                ['product_id' => $this->prod1->id, 'location_id' => $this->loc1->id, 'quantity' => '40.0000'],
            ],
        ], $this->admin->id);
        app(PostStockReceiptAction::class)->execute($receipt, $this->admin->id);

        $balance = InventoryBalance::where('product_id', $this->prod1->id)
            ->where('location_id', $this->loc1->id)
            ->first();

        // Sengaja merusak saldo (drift corruption)
        $balance->update(['quantity' => '999.0000']);

        // Verifikasi detector mendeteksi perbedaan
        $movements = StockMovement::where('product_id', $balance->product_id)
            ->where('location_id', $balance->location_id)
            ->get();

        $aggregateDelta = '0.0000';
        foreach ($movements as $m) {
            $delta = bcsub((string) $m->quantity_after, (string) $m->quantity_before, 4);
            $aggregateDelta = bcadd($aggregateDelta, $delta, 4);
        }

        $corruptedBalance = DecimalQuantity::normalize($balance->fresh()->quantity);
        $normalizedAggregate = DecimalQuantity::normalize($aggregateDelta);

        $this->assertNotEquals(
            $corruptedBalance,
            $normalizedAggregate,
            'Drift detector harus mendeteksi perbedaan saldo terkorupsi dengan agregat movement'
        );
    }

    private function assertLedgerContinuity(int $productId, int $locationId): void
    {
        $movements = StockMovement::where('product_id', $productId)
            ->where('location_id', $locationId)
            ->orderBy('id', 'asc')
            ->get();

        if ($movements->isEmpty()) {
            return;
        }

        // 1. Movement pertama: quantity_before = 0.0000
        $this->assertEquals('0.0000', DecimalQuantity::normalize($movements[0]->quantity_before));

        // 2. Rantai kontinuitas: current.quantity_before = previous.quantity_after
        for ($i = 1; $i < count($movements); $i++) {
            $prevAfter = DecimalQuantity::normalize($movements[$i - 1]->quantity_after);
            $currBefore = DecimalQuantity::normalize($movements[$i]->quantity_before);

            $this->assertEquals(
                $prevAfter,
                $currBefore,
                "Kontinuitas ledger terputus pada movement index {$i} untuk product_id={$productId}, location_id={$locationId}"
            );
        }

        // 3. Movement terakhir: last.quantity_after = balance.quantity
        $lastMovement = $movements->last();
        $balance = InventoryBalance::where('product_id', $productId)
            ->where('location_id', $locationId)
            ->first();

        $this->assertNotNull($balance);
        $this->assertEquals(
            DecimalQuantity::normalize($lastMovement->quantity_after),
            DecimalQuantity::normalize($balance->quantity)
        );
    }
}
