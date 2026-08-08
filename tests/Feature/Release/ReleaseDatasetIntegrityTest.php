<?php

namespace Tests\Feature\Release;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
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
use App\Features\Inventory\Enums\AdjustmentStatus;
use App\Features\Inventory\Enums\IssueStatus;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Enums\OpnameStatus;
use App\Features\Inventory\Enums\ReceiptStatus;
use App\Features\Inventory\Enums\TransferStatus;
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
use App\Features\Reporting\Exports\CsvStreamWriter;
use App\Features\Reporting\Helpers\DecimalQuantity;
use App\Features\Supplier\Models\Supplier;
use App\Features\Unit\Models\Unit;
use Carbon\Carbon;
use Database\Seeders\ReleaseVerificationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class ReleaseDatasetIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_release_verification_seeder_rejects_production_environment(): void
    {
        $originalEnv = $this->app->environment();
        $this->app->detectEnvironment(fn () => 'production');

        try {
            $seeder = new ReleaseVerificationSeeder;
            $seeder->run();
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('ReleaseVerificationSeeder hanya boleh dijalankan pada environment local atau testing.', $e->getMessage());
        } finally {
            $this->app->detectEnvironment(fn () => $originalEnv);
        }
    }

    public function test_complete_dataset_verifier_accepts_enum_cast_statuses(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $this->assertTrue(
            (new ReleaseVerificationSeeder)->isDatasetCompleteAndValid()
        );
    }

    public function test_release_dataset_meets_minimum_volume_thresholds(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $this->assertEquals(20, Category::where('code', 'like', 'REL-CAT-%')->count());
        $this->assertEquals(10, Unit::where('code', 'like', 'REL-UNT-%')->count());
        $this->assertEquals(1000, Product::where('sku', 'like', 'REL-SKU-%')->count());
        $this->assertEquals(50, Supplier::where('code', 'like', 'REL-SUP-%')->count());
        $this->assertEquals(5, Location::where('code', 'like', 'REL-LOC-%')->count());
        $this->assertEquals(5000, InventoryBalance::count());
        $this->assertEquals(10000, StockMovement::count());
    }

    public function test_exact_draft_document_counts_per_model(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $this->assertEquals(5, StockReceipt::where('receipt_number', 'like', 'REL-DRAFT-REC-%')->where('status', ReceiptStatus::DRAFT->value)->count());
        $this->assertEquals(5, StockIssue::where('issue_number', 'like', 'REL-DRAFT-ISS-%')->where('status', IssueStatus::DRAFT->value)->count());
        $this->assertEquals(5, StockTransfer::where('transfer_number', 'like', 'REL-DRAFT-TRF-%')->where('status', TransferStatus::DRAFT->value)->count());
        $this->assertEquals(5, StockAdjustment::where('adjustment_number', 'like', 'REL-DRAFT-ADJ-%')->where('status', AdjustmentStatus::DRAFT->value)->count());
        $this->assertEquals(5, StockOpname::where('opname_number', 'like', 'REL-DRAFT-OPN-%')->where('status', OpnameStatus::DRAFT->value)->count());
    }

    public function test_release_dataset_covers_all_movement_types(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $expectedTypes = collect(MovementType::cases())
            ->map(fn (MovementType $m) => $m->value)
            ->filter(fn (string $v) => $v !== 'REVERSAL')
            ->sort()
            ->values();

        $actualTypes = StockMovement::query()
            ->distinct()
            ->pluck('movement_type')
            ->sort()
            ->values();

        $this->assertSame($expectedTypes->all(), $actualTypes->all());
    }

    public function test_release_dataset_balance_reconciliation_and_continuity_full(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $this->assertReleaseDatasetIntegrity();
    }

    public function test_release_dataset_has_no_negative_stock(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $negativeMovements = StockMovement::where('quantity_before', '<', 0)
            ->orWhere('quantity_after', '<', 0)
            ->count();
        $this->assertEquals(0, $negativeMovements);

        $negativeBalances = InventoryBalance::where('quantity', '<', 0)->count();
        $this->assertEquals(0, $negativeBalances);
    }

    public function test_release_seeder_is_idempotent_on_rerun(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $productCount1 = Product::count();
        $movementCount1 = StockMovement::count();
        $balanceCount1 = InventoryBalance::count();

        // Second run (complete no-op)
        $this->seed(ReleaseVerificationSeeder::class);

        $this->assertEquals($productCount1, Product::count());
        $this->assertEquals($movementCount1, StockMovement::count());
        $this->assertEquals($balanceCount1, InventoryBalance::count());
    }

    public function test_seeder_does_not_false_noop_when_only_release_products_exist(): void
    {
        // 1. Create 1,000 products with release prefix, but NO movements/balances
        $category = Category::factory()->create(['code' => 'REL-CAT-99']);
        $unit = Unit::factory()->create(['code' => 'REL-UNT-99']);

        for ($i = 1; $i <= 1000; $i++) {
            Product::factory()->create([
                'sku' => sprintf('REL-SKU-TEST-%04d', $i),
                'category_id' => $category->id,
                'unit_id' => $unit->id,
            ]);
        }

        // 2. Expect loud exception because dataset is partial
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Dataset release verification terdeteksi parsial atau tidak valid. Gunakan database rehearsal bersih.');

        $seeder = new ReleaseVerificationSeeder;
        $seeder->run();
    }

    public function test_seeder_rejects_partial_dataset_with_insufficient_movements(): void
    {
        // 1. Seed complete release dataset
        $this->seed(ReleaseVerificationSeeder::class);

        // 2. Delete 1 movement row (movements = 9999)
        StockMovement::query()->first()->delete();

        // 3. Expect loud exception on re-run
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Dataset release verification terdeteksi parsial atau tidak valid. Gunakan database rehearsal bersih.');

        $seeder = new ReleaseVerificationSeeder;
        $seeder->run();
    }

    public function test_release_dataset_is_fully_rolled_back_when_generation_fails(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        Location::where('code', 'like', 'REL-LOC-%')->delete();
        Category::where('code', 'like', 'REL-CAT-%')->delete();
        Unit::where('code', 'like', 'REL-UNT-%')->delete();
        Supplier::where('code', 'like', 'REL-SUP-%')->delete();
        Product::where('sku', 'like', 'REL-SKU-%')->delete();
        $relUserIds = User::where('username', 'like', 'rel_user_%')->pluck('id');
        DB::table('user_locations')->whereIn('user_id', $relUserIds)->delete();
        User::whereIn('id', $relUserIds)->delete();

        $caught = null;

        StockOpname::creating(function (): void {
            throw new RuntimeException(
                'Simulated release dataset failure'
            );
        });

        try {
            (new ReleaseVerificationSeeder)->run();
        } catch (\Throwable $exception) {
            $caught = $exception;
        } finally {
            StockOpname::flushEventListeners();
        }

        $this->assertInstanceOf(
            RuntimeException::class,
            $caught
        );

        $this->assertSame(
            'Simulated release dataset failure',
            $caught->getMessage()
        );

        // Assert all 15 release dataset artifacts are 0
        $this->assertEquals(0, Location::where('code', 'like', 'REL-LOC-%')->count());
        $this->assertEquals(0, Category::where('code', 'like', 'REL-CAT-%')->count());
        $this->assertEquals(0, Unit::where('code', 'like', 'REL-UNT-%')->count());
        $this->assertEquals(0, Supplier::where('code', 'like', 'REL-SUP-%')->count());
        $this->assertEquals(0, DB::table('users')->where('username', 'like', 'rel_user_%')->count());
        $this->assertEquals(0, Product::where('sku', 'like', 'REL-SKU-%')->count());
        $this->assertEquals(0, DB::table('stock_receipts')->where('receipt_number', 'like', 'REL-%')->count());
        $this->assertEquals(0, DB::table('stock_issues')->where('issue_number', 'like', 'REL-%')->count());
        $this->assertEquals(0, DB::table('stock_transfers')->where('transfer_number', 'like', 'REL-%')->count());
        $this->assertEquals(0, DB::table('stock_adjustments')->where('adjustment_number', 'like', 'REL-%')->count());
        $this->assertEquals(0, DB::table('stock_opnames')->where('opname_number', 'like', 'REL-%')->count());
        $this->assertEquals(0, StockMovement::where('reference_number', 'like', 'REL-%')->count());
        $this->assertEquals(0, InventoryBalance::count());
        $this->assertEquals(0, DB::table('role_user')->whereIn('user_id', DB::table('users')->where('username', 'like', 'rel_user_%')->pluck('id'))->count());
        $this->assertEquals(0, DB::table('user_locations')->whereIn('user_id', DB::table('users')->where('username', 'like', 'rel_user_%')->pluck('id'))->count());
    }

    public function test_full_integrity_audit_detects_corruption_on_last_release_pair(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $lastProduct = Product::where('sku', 'REL-SKU-1000')->first();
        $lastLocation = Location::where('code', 'REL-LOC-05')->first();

        $this->assertNotNull($lastProduct);
        $this->assertNotNull($lastLocation);

        // Corrupt balance quantity of last pair
        InventoryBalance::where('product_id', $lastProduct->id)
            ->where('location_id', $lastLocation->id)
            ->update(['quantity' => '9999.0000']);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_full_integrity_audit_detects_continuity_corruption_with_fixed_row_count(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $pair = StockMovement::query()
            ->select(
                'product_id',
                'location_id',
                DB::raw('COUNT(*) AS movement_count')
            )
            ->groupBy(
                'product_id',
                'location_id'
            )
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('movement_count')
            ->firstOrFail();

        $movements = StockMovement::query()
            ->where('product_id', $pair->product_id)
            ->where('location_id', $pair->location_id)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        $this->assertGreaterThanOrEqual(
            2,
            $movements->count()
        );

        $firstMovement = $movements->first();
        $secondMovement = $movements->skip(1)->first();

        $secondMovement->update([
            'occurred_at' => Carbon::parse($firstMovement->occurred_at)->subSecond()->toDateTimeString(),
        ]);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_release_seeder_does_not_create_administrator_accounts(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $adminUsersCount = DB::table('users')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('roles.code', RoleCode::ADMIN->value)
            ->where('users.username', 'like', 'rel_user_%')
            ->count();

        $this->assertEquals(0, $adminUsersCount);
    }

    public function test_integrity_audit_rejects_missing_pair_even_when_global_counts_are_preserved(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $lastProduct = Product::where('sku', 'REL-SKU-1000')->first();
        $lastLocation = Location::where('code', 'REL-LOC-05')->first();

        // Delete movements for the last pair
        StockMovement::where('product_id', $lastProduct->id)
            ->where('location_id', $lastLocation->id)
            ->delete();

        // Re-add 2 movements to pair 1 (REL-SKU-0001 + REL-LOC-01) so global movement count remains 10,000
        $firstProduct = Product::where('sku', 'REL-SKU-0001')->first();
        $firstLocation = Location::where('code', 'REL-LOC-01')->first();

        $sampleMovement = StockMovement::first();

        for ($i = 0; $i < 2; $i++) {
            StockMovement::create([
                'movement_id' => (string) Str::uuid(),
                'product_id' => $firstProduct->id,
                'location_id' => $firstLocation->id,
                'movement_type' => MovementType::RECEIPT->value,
                'quantity' => '10.0000',
                'quantity_before' => '40.0000',
                'quantity_after' => '50.0000',
                'reference_type' => $sampleMovement->reference_type,
                'reference_id' => 999999 + $i,
                'reference_number' => $sampleMovement->reference_number."-$i",
                'occurred_at' => now()->toDateTimeString(),
                'created_by' => $sampleMovement->created_by,
            ]);
        }

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_integrity_audit_rejects_non_release_location_substitution(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $nonReleaseLoc = Location::create([
            'code' => 'NON-REL-LOC',
            'name' => 'Non Release Location',
            'is_active' => true,
        ]);

        $lastProduct = Product::where('sku', 'REL-SKU-1000')->first();
        $lastLocation = Location::where('code', 'REL-LOC-05')->first();

        // Move 1 balance and movements to non-release location
        InventoryBalance::where('product_id', $lastProduct->id)
            ->where('location_id', $lastLocation->id)
            ->update(['location_id' => $nonReleaseLoc->id]);

        StockMovement::where('product_id', $lastProduct->id)
            ->where('location_id', $lastLocation->id)
            ->update(['location_id' => $nonReleaseLoc->id]);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_release_dataset_contains_active_and_inactive_products(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $activeCount = Product::where('sku', 'like', 'REL-SKU-%')->where('is_active', true)->count();
        $inactiveCount = Product::where('sku', 'like', 'REL-SKU-%')->where('is_active', false)->count();

        $this->assertEquals(900, $activeCount);
        $this->assertEquals(100, $inactiveCount);
    }

    public function test_release_dataset_contains_zero_low_and_normal_stock(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $releaseProductIds = Product::where('sku', 'like', 'REL-SKU-%')->pluck('id');

        $zeroCount = InventoryBalance::whereIn('product_id', $releaseProductIds)->where('quantity', '0.0000')->count();
        $lowCount = InventoryBalance::whereIn('product_id', $releaseProductIds)->where('quantity', '>', 0)->where('quantity', '<', '5.0000')->count();
        $minCount = InventoryBalance::whereIn('product_id', $releaseProductIds)->where('quantity', '5.0000')->count();
        $normalCount = InventoryBalance::whereIn('product_id', $releaseProductIds)->where('quantity', '>', '5.0000')->count();

        $this->assertGreaterThanOrEqual(100, $zeroCount);
        $this->assertGreaterThanOrEqual(100, $lowCount);
        $this->assertGreaterThanOrEqual(100, $minCount);
        $this->assertGreaterThanOrEqual(4000, $normalCount);
    }

    public function test_release_dataset_contains_historical_dates(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $releaseProductIds = Product::where('sku', 'like', 'REL-SKU-%')->pluck('id');

        $distinctDays = StockMovement::whereIn('product_id', $releaseProductIds)
            ->selectRaw('DATE(occurred_at) as m_date')
            ->distinct()
            ->pluck('m_date');

        $this->assertGreaterThanOrEqual(150, $distinctDays->count());
    }

    public function test_release_dataset_contains_representative_transaction_document_volume(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $this->assertEquals(100, StockReceipt::where('receipt_number', 'like', 'REL-REC-%')->where('status', ReceiptStatus::POSTED->value)->count());
        $this->assertEquals(100, StockIssue::where('issue_number', 'like', 'REL-ISS-%')->where('status', IssueStatus::POSTED->value)->count());
        $this->assertEquals(75, StockTransfer::where('transfer_number', 'like', 'REL-TRF-%')->where('status', TransferStatus::RECEIVED->value)->count());
        $this->assertEquals(75, StockAdjustment::where('adjustment_number', 'like', 'REL-ADJ-%')->where('status', AdjustmentStatus::POSTED->value)->count());
        $this->assertEquals(50, StockOpname::where('opname_number', 'like', 'REL-OPN-%')->where('status', OpnameStatus::POSTED->value)->count());

        // Draft document volume check (5 of each)
        $this->assertEquals(5, StockReceipt::where('receipt_number', 'like', 'REL-DRAFT-REC-%')->where('status', ReceiptStatus::DRAFT->value)->count());
        $this->assertEquals(5, StockIssue::where('issue_number', 'like', 'REL-DRAFT-ISS-%')->where('status', IssueStatus::DRAFT->value)->count());
        $this->assertEquals(5, StockTransfer::where('transfer_number', 'like', 'REL-DRAFT-TRF-%')->where('status', TransferStatus::DRAFT->value)->count());
        $this->assertEquals(5, StockAdjustment::where('adjustment_number', 'like', 'REL-DRAFT-ADJ-%')->where('status', AdjustmentStatus::DRAFT->value)->count());
        $this->assertEquals(5, StockOpname::where('opname_number', 'like', 'REL-DRAFT-OPN-%')->where('status', OpnameStatus::DRAFT->value)->count());
    }

    public function test_every_release_document_has_items(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $postedReceipts = StockReceipt::where('receipt_number', 'like', 'REL-REC-%')->get();
        foreach ($postedReceipts as $rec) {
            $this->assertGreaterThan(0, $rec->items()->count(), "StockReceipt {$rec->receipt_number} has 0 items");
        }

        $postedIssues = StockIssue::where('issue_number', 'like', 'REL-ISS-%')->get();
        foreach ($postedIssues as $iss) {
            $this->assertGreaterThan(0, $iss->items()->count(), "StockIssue {$iss->issue_number} has 0 items");
        }

        $postedTransfers = StockTransfer::where('transfer_number', 'like', 'REL-TRF-%')->get();
        foreach ($postedTransfers as $trf) {
            $this->assertGreaterThan(0, $trf->items()->count(), "StockTransfer {$trf->transfer_number} has 0 items");
        }

        $postedAdjustments = StockAdjustment::where('adjustment_number', 'like', 'REL-ADJ-%')->get();
        foreach ($postedAdjustments as $adj) {
            $this->assertGreaterThan(0, $adj->items()->count(), "StockAdjustment {$adj->adjustment_number} has 0 items");
        }

        $postedOpnames = StockOpname::where('opname_number', 'like', 'REL-OPN-%')->get();
        foreach ($postedOpnames as $opn) {
            $this->assertGreaterThan(0, $opn->items()->count(), "StockOpname {$opn->opname_number} has 0 items");
        }
    }

    public function test_every_received_transfer_item_has_exact_out_and_in_pair(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $transferItems = StockTransferItem::whereHas('transfer', function ($q) {
            $q->where('transfer_number', 'like', 'REL-TRF-%')->where('status', TransferStatus::RECEIVED->value);
        })->get();

        $this->assertGreaterThan(0, $transferItems->count());

        foreach ($transferItems as $item) {
            $outCount = StockMovement::where('reference_type', StockTransfer::class)
                ->where('reference_id', $item->stock_transfer_id)
                ->where('product_id', $item->product_id)
                ->where('movement_type', MovementType::TRANSFER_OUT->value)
                ->count();

            $inCount = StockMovement::where('reference_type', StockTransfer::class)
                ->where('reference_id', $item->stock_transfer_id)
                ->where('product_id', $item->product_id)
                ->where('movement_type', MovementType::TRANSFER_IN->value)
                ->count();

            $this->assertSame(1, $outCount, "Transfer Item {$item->id} missing TRANSFER_OUT");
            $this->assertSame(1, $inCount, "Transfer Item {$item->id} missing TRANSFER_IN");
        }
    }

    public function test_all_release_movements_match_document_and_item_semantics(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $releaseProductIds = Product::where('sku', 'like', 'REL-SKU-%')->pluck('id');

        $movementsCursor = StockMovement::whereIn('product_id', $releaseProductIds)->cursor();

        foreach ($movementsCursor as $m) {
            switch ($m->movement_type) {
                case MovementType::RECEIPT->value:
                    $this->assertSame(StockReceipt::class, $m->reference_type);
                    $doc = StockReceipt::find($m->reference_id);
                    $this->assertNotNull($doc, "Receipt doc {$m->reference_id} missing");
                    $item = StockReceiptItem::where('stock_receipt_id', $doc->id)
                        ->where('product_id', $m->product_id)
                        ->where('location_id', $m->location_id)
                        ->first();
                    $this->assertNotNull($item, "Receipt item missing for movement {$m->id}");
                    $this->assertEquals(DecimalQuantity::normalize($m->quantity), DecimalQuantity::normalize($item->quantity));
                    break;

                case MovementType::ISSUE->value:
                    $this->assertSame(StockIssue::class, $m->reference_type);
                    $doc = StockIssue::find($m->reference_id);
                    $this->assertNotNull($doc, "Issue doc {$m->reference_id} missing");
                    $item = StockIssueItem::where('stock_issue_id', $doc->id)
                        ->where('product_id', $m->product_id)
                        ->where('location_id', $m->location_id)
                        ->first();
                    $this->assertNotNull($item, "Issue item missing for movement {$m->id}");
                    $this->assertEquals(DecimalQuantity::normalize($m->quantity), DecimalQuantity::normalize($item->quantity));
                    break;

                case MovementType::TRANSFER_OUT->value:
                    $this->assertSame(StockTransfer::class, $m->reference_type);
                    $doc = StockTransfer::find($m->reference_id);
                    $this->assertNotNull($doc, "Transfer doc {$m->reference_id} missing");
                    $this->assertEquals($doc->origin_location_id, $m->location_id, "TRANSFER_OUT location mismatch for movement {$m->id}");
                    $item = StockTransferItem::where('stock_transfer_id', $doc->id)
                        ->where('product_id', $m->product_id)
                        ->first();
                    $this->assertNotNull($item, "Transfer item missing for movement {$m->id}");
                    $this->assertEquals(DecimalQuantity::normalize($m->quantity), DecimalQuantity::normalize($item->quantity));
                    break;

                case MovementType::TRANSFER_IN->value:
                    $this->assertSame(StockTransfer::class, $m->reference_type);
                    $doc = StockTransfer::find($m->reference_id);
                    $this->assertNotNull($doc, "Transfer doc {$m->reference_id} missing");
                    $this->assertEquals($doc->destination_location_id, $m->location_id, "TRANSFER_IN location mismatch for movement {$m->id}");
                    $item = StockTransferItem::where('stock_transfer_id', $doc->id)
                        ->where('product_id', $m->product_id)
                        ->first();
                    $this->assertNotNull($item, "Transfer item missing for movement {$m->id}");
                    $this->assertEquals(DecimalQuantity::normalize($m->quantity), DecimalQuantity::normalize($item->quantity));
                    break;

                case MovementType::ADJUSTMENT_IN->value:
                    $this->assertSame(StockAdjustment::class, $m->reference_type);
                    $doc = StockAdjustment::find($m->reference_id);
                    $this->assertNotNull($doc, "Adjustment doc {$m->reference_id} missing");
                    $this->assertEquals('INCREASE', $doc->direction, "ADJUSTMENT_IN direction mismatch for movement {$m->id}");
                    $this->assertEquals($doc->location_id, $m->location_id, "ADJUSTMENT_IN location mismatch for movement {$m->id}");
                    $item = StockAdjustmentItem::where('stock_adjustment_id', $doc->id)
                        ->where('product_id', $m->product_id)
                        ->first();
                    $this->assertNotNull($item, "Adjustment item missing for movement {$m->id}");
                    $this->assertEquals(DecimalQuantity::normalize($m->quantity), DecimalQuantity::normalize($item->quantity));
                    break;

                case MovementType::ADJUSTMENT_OUT->value:
                    $this->assertSame(StockAdjustment::class, $m->reference_type);
                    $doc = StockAdjustment::find($m->reference_id);
                    $this->assertNotNull($doc, "Adjustment doc {$m->reference_id} missing");
                    $this->assertEquals('DECREASE', $doc->direction, "ADJUSTMENT_OUT direction mismatch for movement {$m->id}");
                    $this->assertEquals($doc->location_id, $m->location_id, "ADJUSTMENT_OUT location mismatch for movement {$m->id}");
                    $item = StockAdjustmentItem::where('stock_adjustment_id', $doc->id)
                        ->where('product_id', $m->product_id)
                        ->first();
                    $this->assertNotNull($item, "Adjustment item missing for movement {$m->id}");
                    $this->assertEquals(DecimalQuantity::normalize($m->quantity), DecimalQuantity::normalize($item->quantity));
                    break;

                case MovementType::OPNAME_IN->value:
                    $this->assertSame(StockOpname::class, $m->reference_type);
                    $doc = StockOpname::find($m->reference_id);
                    $this->assertNotNull($doc, "Opname doc {$m->reference_id} missing");
                    $this->assertEquals($doc->location_id, $m->location_id, "OPNAME_IN location mismatch for movement {$m->id}");
                    $item = StockOpnameItem::where('stock_opname_id', $doc->id)
                        ->where('product_id', $m->product_id)
                        ->first();
                    $this->assertNotNull($item, "Opname item missing for movement {$m->id}");
                    $this->assertSame(1, bccomp(DecimalQuantity::normalize($item->variance_quantity), '0.0000', 4), 'OPNAME_IN variance must be positive');
                    $this->assertEquals(DecimalQuantity::normalize($m->quantity), DecimalQuantity::normalize($item->variance_quantity));
                    break;

                case MovementType::OPNAME_OUT->value:
                    $this->assertSame(StockOpname::class, $m->reference_type);
                    $doc = StockOpname::find($m->reference_id);
                    $this->assertNotNull($doc, "Opname doc {$m->reference_id} missing");
                    $this->assertEquals($doc->location_id, $m->location_id, "OPNAME_OUT location mismatch for movement {$m->id}");
                    $item = StockOpnameItem::where('stock_opname_id', $doc->id)
                        ->where('product_id', $m->product_id)
                        ->first();
                    $this->assertNotNull($item, "Opname item missing for movement {$m->id}");
                    $this->assertSame(-1, bccomp(DecimalQuantity::normalize($item->variance_quantity), '0.0000', 4), 'OPNAME_OUT variance must be negative');
                    $absVariance = str_starts_with((string) $item->variance_quantity, '-') ? substr((string) $item->variance_quantity, 1) : (string) $item->variance_quantity;
                    $this->assertEquals(DecimalQuantity::normalize($m->quantity), DecimalQuantity::normalize($absVariance));
                    break;
            }
        }
    }

    public function test_csv_safety_fixtures_have_leading_dangerous_characters(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $prodName = Product::where('sku', 'REL-SKU-0001')->value('name');
        $supName = Supplier::where('code', 'REL-SUP-01')->value('name');
        $issPurpose = StockIssue::where('issue_number', 'REL-ISS-0001')->value('purpose');
        $adjNotes = StockAdjustment::where('adjustment_number', 'REL-ADJ-0001')->value('notes');

        $this->assertSame('=', $prodName[0]);
        $this->assertSame('+', $supName[0]);
        $this->assertSame('-', $issPurpose[0]);
        $this->assertSame('@', $adjNotes[0]);
    }

    public function test_csv_export_sanitization_prefixes_dangerous_characters_and_preserves_decimals(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $prodName = Product::where('sku', 'REL-SKU-0001')->value('name');
        $supName = Supplier::where('code', 'REL-SUP-01')->value('name');
        $issPurpose = StockIssue::where('issue_number', 'REL-ISS-0001')->value('purpose');
        $adjNotes = StockAdjustment::where('adjustment_number', 'REL-ADJ-0001')->value('notes');

        $sanitizedProd = CsvStreamWriter::sanitizeValue($prodName);
        $sanitizedSup = CsvStreamWriter::sanitizeValue($supName);
        $sanitizedIss = CsvStreamWriter::sanitizeValue($issPurpose);
        $sanitizedAdj = CsvStreamWriter::sanitizeValue($adjNotes);

        $this->assertSame("'=FORMULA Produk Kabel", $sanitizedProd);
        $this->assertSame("'+FORMULA Supplier Jakarta", $sanitizedSup);
        $this->assertSame("'-FORMULA Release Issue", $sanitizedIss);
        $this->assertSame("'@FORMULA Release Adjustment", $sanitizedAdj);
    }

    public function test_decimal_helper_sanitization_preserves_negative_decimals(): void
    {
        $this->assertSame('-0.0001', CsvStreamWriter::sanitizeValue('-0.0001'));
        $this->assertSame('-10.0000', CsvStreamWriter::sanitizeValue('-10.0000'));
        $this->assertSame('50.0000', CsvStreamWriter::sanitizeValue('50.0000'));
    }

    public function test_end_to_end_csv_export_streams_sanitized_output_with_bom(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        // Create authorized user with REPORTS_EXPORT permission
        $role = Role::firstOrCreate(['code' => RoleCode::ADMIN->value], ['name' => 'Administrator']);
        $permission = Permission::firstOrCreate(['code' => PermissionCode::REPORTS_EXPORT->value], ['name' => 'Export Reports', 'group' => 'reports']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $targetProduct = Product::where('sku', 'REL-SKU-0001')->first();
        $targetLocation = Location::where('code', 'REL-LOC-01')->first();
        $otherProduct = Product::where('sku', 'REL-SKU-0002')->first();

        $user->locations()->attach($targetLocation->id);

        $response = $this->actingAs($user, 'sanctum')
            ->get("/api/v1/reports/inventory-balances/export?search=REL-SKU-0001&location_id={$targetLocation->id}");

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $content = $response->streamedContent();
        $this->assertTrue(str_starts_with($content, "\xEF\xBB\xBF"), 'CSV output must start with UTF-8 BOM');

        // Assert Canonical Header Row
        $this->assertStringContainsString('SKU,"Nama Produk",Kategori,Satuan,"Kode Lokasi","Nama Lokasi",Saldo,"Stok Minimum","Status Produk","Status Lokasi"', $content);

        $this->assertStringContainsString("'=FORMULA Produk Kabel", $content);
        $this->assertStringContainsString($targetProduct->sku, $content);
        $this->assertStringNotContainsString($otherProduct->sku, $content);
    }

    public function test_integrity_audit_rejects_movement_quantity_drift(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        // Corrupt 1 movement quantity to 999.0000 without changing before/after
        StockMovement::query()->first()->update(['quantity' => '999.0000']);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_integrity_audit_rejects_movement_type_direction_mismatch(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        // Corrupt an ISSUE movement to have positive delta (quantity_after > quantity_before)
        StockMovement::where('movement_type', MovementType::ISSUE->value)
            ->first()
            ->update([
                'quantity_before' => '40.0000',
                'quantity_after' => '50.0000',
            ]);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_integrity_audit_rejects_extra_balance_on_non_release_location(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $nonReleaseLoc = Location::create([
            'code' => 'NON-REL-LOC-EXTRA',
            'name' => 'Non Release Extra Location',
            'is_active' => true,
        ]);

        $relProd = Product::where('sku', 'REL-SKU-0001')->first();

        // Create an extra balance for a release product on a non-release location
        InventoryBalance::create([
            'product_id' => $relProd->id,
            'location_id' => $nonReleaseLoc->id,
            'quantity' => '10.0000',
        ]);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_missing_receipt_item(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        StockReceiptItem::query()->first()->delete();

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_orphan_receipt_item(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $rec = StockReceipt::where('receipt_number', 'like', 'REL-REC-%')->first();
        // Find a product not yet in $rec
        $existingProductIds = $rec->items()->pluck('product_id')->toArray();
        $prod = Product::where('sku', 'like', 'REL-SKU-%')->whereNotIn('id', $existingProductIds)->first()
            ?? Product::factory()->create();
        $loc = Location::where('code', 'like', 'REL-LOC-%')->first();

        StockReceiptItem::create([
            'stock_receipt_id' => $rec->id,
            'product_id' => $prod->id,
            'location_id' => $loc->id,
            'quantity' => '12.0000',
        ]);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_receipt_item_quantity_mismatch(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        StockReceiptItem::query()->first()->update(['quantity' => '999.0000']);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_missing_issue_item(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        StockIssueItem::query()->first()->delete();

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_issue_item_quantity_mismatch(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        StockIssueItem::query()->first()->update(['quantity' => '999.0000']);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_missing_transfer_counterpart_with_preserved_global_count(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        // Delete 1 TRANSFER_IN movement
        $trfIn = StockMovement::where('movement_type', MovementType::TRANSFER_IN->value)->first();
        $trfIn->delete();

        // Create a fake RECEIPT movement to preserve 10,000 global movements
        $sampleRec = StockMovement::where('movement_type', MovementType::RECEIPT->value)->first();
        StockMovement::create([
            'movement_id' => (string) Str::uuid(),
            'product_id' => $sampleRec->product_id,
            'location_id' => $sampleRec->location_id,
            'movement_type' => MovementType::RECEIPT->value,
            'quantity' => '10.0000',
            'quantity_before' => '50.0000',
            'quantity_after' => '60.0000',
            'reference_type' => $sampleRec->reference_type,
            'reference_id' => 999998,
            'reference_number' => $sampleRec->reference_number.'-fake',
            'occurred_at' => now()->toDateTimeString(),
            'created_by' => $sampleRec->created_by,
        ]);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_orphan_transfer_movement_with_preserved_global_count(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        // Delete 1 non-transfer movement (RECEIPT)
        $receiptMovement = StockMovement::where('movement_type', MovementType::RECEIPT->value)->first();
        $receiptMovement->delete();

        // Create an orphan transfer movement referencing a RECEIVED transfer item
        $receivedTransfer = StockTransfer::where('status', TransferStatus::RECEIVED->value)->first();
        $transferItem = $receivedTransfer->items()->first();

        StockMovement::create([
            'movement_id' => (string) Str::uuid(),
            'product_id' => $transferItem->product_id,
            'location_id' => $receivedTransfer->origin_location_id,
            'movement_type' => MovementType::TRANSFER_OUT->value,
            'quantity' => '5.0000',
            'quantity_before' => '50.0000',
            'quantity_after' => '45.0000',
            'reference_type' => StockTransfer::class,
            'reference_id' => 999997,
            'reference_number' => $receivedTransfer->transfer_number.'-fake',
            'occurred_at' => now()->toDateTimeString(),
            'created_by' => $receivedTransfer->created_by,
        ]);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_missing_lifecycle_metadata(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        // Clear sent_at metadata from a RECEIVED transfer
        StockTransfer::where('status', TransferStatus::RECEIVED->value)->first()->update(['sent_at' => null]);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_adjustment_direction_mismatch(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        // Change an INCREASE adjustment document to DECREASE
        StockAdjustment::where('direction', 'INCREASE')->first()->update(['direction' => 'DECREASE']);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_missing_adjustment_item(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        StockAdjustmentItem::query()->first()->delete();

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_missing_opname_item(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        StockOpnameItem::query()->first()->delete();

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_opname_variance_mismatch(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        StockOpnameItem::query()->first()->update(['variance_quantity' => '999.0000']);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_movement_referencing_draft_receipt(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $draft = StockReceipt::where('status', ReceiptStatus::DRAFT->value)->first();
        StockMovement::where('movement_type', MovementType::RECEIPT->value)->first()->update(['reference_id' => $draft->id]);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_movement_referencing_draft_issue(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $draft = StockIssue::where('status', IssueStatus::DRAFT->value)->first();
        StockMovement::where('movement_type', MovementType::ISSUE->value)->first()->update(['reference_id' => $draft->id]);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_movement_referencing_draft_transfer(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $draft = StockTransfer::where('status', TransferStatus::DRAFT->value)->first();
        StockMovement::where('movement_type', MovementType::TRANSFER_OUT->value)->first()->update(['reference_id' => $draft->id]);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_movement_referencing_draft_adjustment(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $draft = StockAdjustment::where('status', AdjustmentStatus::DRAFT->value)->first();
        StockMovement::where('movement_type', MovementType::ADJUSTMENT_IN->value)->first()->update(['reference_id' => $draft->id]);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_movement_referencing_draft_opname(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $draft = StockOpname::where('status', OpnameStatus::DRAFT->value)->first();
        StockMovement::where('movement_type', MovementType::OPNAME_IN->value)->first()->update(['reference_id' => $draft->id]);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_missing_reference_document(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $m = StockMovement::where('movement_type', MovementType::RECEIPT->value)->first();
        StockReceipt::where('id', $m->reference_id)->delete();

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_reference_number_mismatch(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        StockMovement::where('movement_type', MovementType::RECEIPT->value)->first()->update(['reference_number' => 'WRONG-NUM']);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_completeness_rejects_chronologically_broken_ledger(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $releaseProductIds = Product::where('sku', 'like', 'REL-SKU-%')->pluck('id');

        $pair = StockMovement::query()
            ->select('product_id', 'location_id', DB::raw('COUNT(*) AS movement_count'))
            ->whereIn('product_id', $releaseProductIds)
            ->groupBy('product_id', 'location_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('movement_count')
            ->firstOrFail();

        $movements = StockMovement::query()
            ->where('product_id', $pair->product_id)
            ->where('location_id', $pair->location_id)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        $this->assertGreaterThanOrEqual(2, $movements->count());

        $firstOccurredAt = Carbon::parse($movements[0]->occurred_at);
        $movements[1]->update(['occurred_at' => $firstOccurredAt->subHour()->toDateTimeString()]);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertFalse($seeder->isDatasetCompleteAndValid());
    }

    public function test_canonical_domain_actions_execute_successfully(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $user = User::factory()->create();
        $location = Location::factory()->create();
        $user->locations()->attach($location->id);

        $category = Category::factory()->create();
        $unit = Unit::factory()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'unit_id' => $unit->id, 'minimum_stock' => '5.0000']);

        // 1. Post Receipt Action
        $receipt = app(CreateStockReceiptAction::class)->execute([
            'supplier_id' => $supplier->id,
            'date' => now()->toDateString(),
            'notes' => 'Canonical Receipt Action',
            'items' => [
                ['product_id' => $product->id, 'location_id' => $location->id, 'quantity' => '100.0000'],
            ],
        ], $user->id);

        app(PostStockReceiptAction::class)->execute($receipt, $user->id);
        $this->assertTrue($receipt->fresh()->isPosted());

        // 2. Post Issue Action
        $issue = app(CreateStockIssueAction::class)->execute([
            'purpose' => 'Canonical Issue Action',
            'date' => now()->toDateString(),
            'notes' => 'Canonical Issue',
            'items' => [
                ['product_id' => $product->id, 'location_id' => $location->id, 'quantity' => '10.0000'],
            ],
        ], $user->id);

        app(PostStockIssueAction::class)->execute($issue, $user->id);
        $this->assertTrue($issue->fresh()->isPosted());

        // 3. Send & Receive Transfer Action
        $destLocation = Location::factory()->create();
        $user->locations()->attach($destLocation->id);

        $transfer = app(CreateStockTransferAction::class)->execute([
            'origin_location_id' => $location->id,
            'destination_location_id' => $destLocation->id,
            'transfer_date' => now()->toDateString(),
            'notes' => 'Canonical Transfer Action',
            'items' => [
                ['product_id' => $product->id, 'quantity' => '20.0000'],
            ],
        ], $user->id);

        app(SendStockTransferAction::class)->execute($transfer, $user->id);
        app(ReceiveStockTransferAction::class)->execute($transfer, $user->id);
        $this->assertSame(TransferStatus::RECEIVED, $transfer->fresh()->status);

        $supervisorRole = Role::firstOrCreate(['code' => RoleCode::INVENTORY_SUPERVISOR->value], ['name' => 'Supervisor']);
        $supervisorUser = User::factory()->create();
        $supervisorUser->roles()->attach($supervisorRole->id);
        $supervisorUser->locations()->attach($location->id);

        // 4. Post Adjustment IN Action
        $adjIn = app(CreateStockAdjustmentAction::class)->execute([
            'location_id' => $location->id,
            'adjustment_date' => now()->toDateString(),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'notes' => 'Canonical Adjustment IN',
            'items' => [
                ['product_id' => $product->id, 'quantity' => '5.0000'],
            ],
        ], $user->id);

        app(PostStockAdjustmentAction::class)->execute($adjIn, $supervisorUser->id);
        $this->assertTrue($adjIn->fresh()->isPosted());

        // 5. Post Adjustment OUT Action
        $adjOut = app(CreateStockAdjustmentAction::class)->execute([
            'location_id' => $location->id,
            'adjustment_date' => now()->toDateString(),
            'direction' => 'DECREASE',
            'reason_code' => AdjustmentReason::DAMAGED->value,
            'notes' => 'Canonical Adjustment OUT',
            'items' => [
                ['product_id' => $product->id, 'quantity' => '2.0000'],
            ],
        ], $user->id);

        app(PostStockAdjustmentAction::class)->execute($adjOut, $supervisorUser->id);
        $this->assertTrue($adjOut->fresh()->isPosted());

        // 6. Post Opname Surplus Action
        $opnameSurplus = app(CreateStockOpnameAction::class)->execute([
            'location_id' => $location->id,
            'opname_date' => now()->toDateString(),
            'notes' => 'Canonical Opname Surplus',
        ], $user->id);

        app(StartStockOpnameAction::class)->execute($opnameSurplus, $user->id);

        $itemSurplus = $opnameSurplus->items()->where('product_id', $product->id)->first();
        app(InputCountAction::class)->execute($opnameSurplus, $itemSurplus->id, ['counted_quantity' => '120.0000'], $user->id);

        app(CompleteStockOpnameAction::class)->execute($opnameSurplus, $user->id);
        app(PostStockOpnameAction::class)->execute($opnameSurplus, $supervisorUser->id);
        $this->assertTrue($opnameSurplus->fresh()->isPosted());

        // 7. Post Opname Shortage Action
        $opnameShortage = app(CreateStockOpnameAction::class)->execute([
            'location_id' => $location->id,
            'opname_date' => now()->toDateString(),
            'notes' => 'Canonical Opname Shortage',
        ], $user->id);

        app(StartStockOpnameAction::class)->execute($opnameShortage, $user->id);

        $itemShortage = $opnameShortage->items()->where('product_id', $product->id)->first();
        app(InputCountAction::class)->execute($opnameShortage, $itemShortage->id, ['counted_quantity' => '90.0000'], $user->id);

        app(CompleteStockOpnameAction::class)->execute($opnameShortage, $user->id);
        app(PostStockOpnameAction::class)->execute($opnameShortage, $supervisorUser->id);
        $this->assertTrue($opnameShortage->fresh()->isPosted());

        $shortageMovement = StockMovement::where('reference_type', StockOpname::class)
            ->where('reference_id', $opnameShortage->id)
            ->first();

        $this->assertNotNull($shortageMovement);
        $this->assertSame(MovementType::OPNAME_OUT->value, $shortageMovement->movement_type);
        $this->assertSame(-1, bccomp((string) $shortageMovement->quantity_after, (string) $shortageMovement->quantity_before, 4));
        $this->assertFalse((bool) $location->fresh()->is_frozen);
    }

    public function test_maker_checker_authorization_enforces_creator_posting_restrictions(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $location = Location::factory()->create();
        $officerRole = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();
        $supervisorRole = Role::where('code', RoleCode::INVENTORY_SUPERVISOR->value)->first();

        // Creator User (Officer)
        $creator = User::factory()->create();
        $creator->roles()->attach($officerRole->id);
        $creator->locations()->attach($location->id);

        // Supervisor User (Distinct User)
        $supervisor = User::factory()->create();
        $supervisor->roles()->attach($supervisorRole->id);
        $supervisor->locations()->attach($location->id);

        $category = Category::factory()->create();
        $unit = Unit::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'unit_id' => $unit->id]);

        // Create Adjustment by Creator
        $adjustment = app(CreateStockAdjustmentAction::class)->execute([
            'location_id' => $location->id,
            'adjustment_date' => now()->toDateString(),
            'direction' => 'INCREASE',
            'reason_code' => AdjustmentReason::FOUND->value,
            'notes' => 'Maker-Checker Adjustment',
            'items' => [
                ['product_id' => $product->id, 'quantity' => '10.0000'],
            ],
        ], $creator->id);

        // Officer Creator attempts HTTP Post -> 403 Forbidden
        $responseCreator = $this->actingAs($creator, 'sanctum')
            ->postJson("/api/v1/stock-adjustments/{$adjustment->id}/post");
        $responseCreator->assertStatus(403);

        // Supervisor Non-Creator attempts HTTP Post -> 200 OK
        $responseSupervisor = $this->actingAs($supervisor, 'sanctum')
            ->postJson("/api/v1/stock-adjustments/{$adjustment->id}/post");
        $responseSupervisor->assertStatus(200);

        $this->assertTrue($adjustment->fresh()->isPosted());
    }

    public function test_release_dataset_source_contract_is_self_consistent(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $seeder = new ReleaseVerificationSeeder;
        $this->assertTrue($seeder->isDatasetCompleteAndValid());

        $this->assertEquals(10000, StockMovement::count());
        $this->assertEquals(5000, InventoryBalance::count());
        $this->assertEquals(0, StockMovement::where('quantity_before', '<', 0)->orWhere('quantity_after', '<', 0)->count());
        $this->assertEquals(0, InventoryBalance::where('quantity', '<', 0)->count());
    }

    public function test_release_dataset_contains_report_search_fixtures(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $this->assertTrue(Product::where('name', '=FORMULA Produk Kabel')->exists());
        $this->assertTrue(Product::where('name', 'Produk Baut')->exists());
        $this->assertTrue(Supplier::where('name', '+FORMULA Supplier Jakarta')->exists());
        $this->assertTrue(Location::where('name', 'Gudang Utama Release')->exists());
    }

    /**
     * Independent test oracle calculating reconciliation, continuity, and Cartesian parity.
     */
    private function assertReleaseDatasetIntegrity(): void
    {
        $releaseProductIds = Product::query()
            ->where('sku', 'like', 'REL-SKU-%')
            ->pluck('id');

        $releaseLocationIds = Location::query()
            ->where('code', 'like', 'REL-LOC-%')
            ->pluck('id');

        $this->assertCount(1000, $releaseProductIds);
        $this->assertCount(5, $releaseLocationIds);

        // Verify Cartesian pair set
        $expectedPairKeys = [];
        foreach ($releaseProductIds as $pId) {
            foreach ($releaseLocationIds as $lId) {
                $expectedPairKeys[] = "{$pId}:{$lId}";
            }
        }
        sort($expectedPairKeys);

        $actualBalanceKeys = InventoryBalance::query()
            ->whereIn('product_id', $releaseProductIds)
            ->whereIn('location_id', $releaseLocationIds)
            ->get(['product_id', 'location_id'])
            ->map(fn ($b) => "{$b->product_id}:{$b->location_id}")
            ->sort()
            ->values()
            ->all();

        $this->assertSame($expectedPairKeys, $actualBalanceKeys);

        // Verify 0 extra balances on non-release locations
        $extraBalances = InventoryBalance::whereIn('product_id', $releaseProductIds)
            ->whereNotIn('location_id', $releaseLocationIds)
            ->count();
        $this->assertEquals(0, $extraBalances);

        // Single-pass memory-safe cursor calculation
        $balancesMap = InventoryBalance::whereIn('product_id', $releaseProductIds)
            ->whereIn('location_id', $releaseLocationIds)
            ->get(['product_id', 'location_id', 'quantity'])
            ->keyBy(fn ($b) => "{$b->product_id}:{$b->location_id}");

        $currentPairKey = null;
        $runningDelta = '0.0000';
        $prevAfter = null;
        $lastAfter = null;
        $auditedPairKeys = [];

        $movementsCursor = StockMovement::whereIn('product_id', $releaseProductIds)
            ->orderBy('product_id')
            ->orderBy('location_id')
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->cursor();

        $inTypes = [
            MovementType::RECEIPT->value,
            MovementType::TRANSFER_IN->value,
            MovementType::ADJUSTMENT_IN->value,
            MovementType::OPNAME_IN->value,
        ];

        foreach ($movementsCursor as $m) {
            $this->assertTrue($releaseLocationIds->contains($m->location_id), "Movement {$m->id} contains non-release location {$m->location_id}");

            $delta = bcsub((string) $m->quantity_after, (string) $m->quantity_before, 4);
            $absDelta = str_starts_with($delta, '-') ? substr($delta, 1) : $delta;
            $this->assertEquals(DecimalQuantity::normalize($absDelta), DecimalQuantity::normalize($m->quantity), "Movement {$m->id} quantity drift");

            if (in_array($m->movement_type, $inTypes, true)) {
                $this->assertSame(1, bccomp(DecimalQuantity::normalize($delta), '0.0000', 4), "IN movement {$m->id} has non-positive delta");
            } else {
                $this->assertSame(-1, bccomp(DecimalQuantity::normalize($delta), '0.0000', 4), "OUT movement {$m->id} has non-negative delta");
            }

            $pairKey = "{$m->product_id}:{$m->location_id}";

            if ($currentPairKey !== $pairKey) {
                if ($currentPairKey !== null) {
                    $expectedBalanceModel = $balancesMap->get($currentPairKey);
                    $this->assertNotNull($expectedBalanceModel);
                    $expectedQty = DecimalQuantity::normalize($expectedBalanceModel->quantity);
                    $this->assertEquals($expectedQty, DecimalQuantity::normalize($runningDelta));
                    $this->assertEquals($expectedQty, DecimalQuantity::normalize($lastAfter));
                    $auditedPairKeys[] = $currentPairKey;
                }

                $currentPairKey = $pairKey;
                $runningDelta = '0.0000';
                $prevAfter = null;

                $this->assertEquals('0.0000', DecimalQuantity::normalize($m->quantity_before));
            } else {
                $this->assertEquals(DecimalQuantity::normalize($prevAfter), DecimalQuantity::normalize($m->quantity_before));
            }

            $runningDelta = bcadd($runningDelta, $delta, 4);
            $prevAfter = (string) $m->quantity_after;
            $lastAfter = (string) $m->quantity_after;
        }

        if ($currentPairKey !== null) {
            $expectedBalanceModel = $balancesMap->get($currentPairKey);
            $this->assertNotNull($expectedBalanceModel);
            $expectedQty = DecimalQuantity::normalize($expectedBalanceModel->quantity);
            $this->assertEquals($expectedQty, DecimalQuantity::normalize($runningDelta));
            $this->assertEquals($expectedQty, DecimalQuantity::normalize($lastAfter));
            $auditedPairKeys[] = $currentPairKey;
        }

        sort($auditedPairKeys);
        $this->assertSame($expectedPairKeys, $auditedPairKeys);
    }

    public function test_maker_checker_authorization_for_adjustments(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $adjustment = StockAdjustment::where('status', AdjustmentStatus::DRAFT->value)->first();
        $creator = User::find($adjustment->created_by);

        $response = $this->actingAs($creator, 'sanctum')
            ->post("/api/v1/stock-adjustments/{$adjustment->id}/post");
        $response->assertStatus(403);

        $supervisorRole = Role::where('code', RoleCode::INVENTORY_SUPERVISOR->value)->first();
        $supervisor = User::whereHas('roles', fn ($q) => $q->where('id', $supervisorRole->id))
            ->where('id', '!=', $creator->id)
            ->first();
        $supervisor->locations()->syncWithoutDetaching([$adjustment->location_id]);

        $response = $this->actingAs($supervisor, 'sanctum')
            ->post("/api/v1/stock-adjustments/{$adjustment->id}/post");
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_maker_checker_authorization_for_opnames(): void
    {
        $this->seed(ReleaseVerificationSeeder::class);

        $opname = StockOpname::where('status', OpnameStatus::DRAFT->value)->first();
        $creator = User::find($opname->created_by);

        $opname->update([
            'status' => OpnameStatus::COUNTED->value,
            'started_by' => $creator->id,
            'completed_by' => $creator->id,
        ]);

        $response = $this->actingAs($creator, 'sanctum')
            ->post("/api/v1/stock-opnames/{$opname->id}/post");
        $response->assertStatus(403);

        $supervisorRole = Role::where('code', RoleCode::INVENTORY_SUPERVISOR->value)->first();
        $supervisor = User::whereHas('roles', fn ($q) => $q->where('id', $supervisorRole->id))
            ->where('id', '!=', $creator->id)
            ->first();
        $supervisor->locations()->syncWithoutDetaching([$opname->location_id]);

        $response = $this->actingAs($supervisor, 'sanctum')
            ->post("/api/v1/stock-opnames/{$opname->id}/post");
        $this->assertNotEquals(403, $response->getStatusCode());
    }
}
