<?php

namespace Database\Seeders;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
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
use App\Features\Reporting\Helpers\DecimalQuantity;
use App\Features\Supplier\Models\Supplier;
use App\Features\Unit\Models\Unit;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class ReleaseVerificationSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(
                'ReleaseVerificationSeeder hanya boleh dijalankan pada environment local atau testing.'
            );
        }

        $hasReleaseData = Product::where('sku', 'like', 'REL-SKU-%')->exists();

        if ($hasReleaseData) {
            if ($this->isDatasetCompleteAndValid()) {
                $this->command?->info('Release verification dataset is complete and valid.');

                return;
            }

            throw new RuntimeException(
                'Dataset release verification terdeteksi parsial atau tidak valid. Gunakan database rehearsal bersih.'
            );
        }

        $seedCallback = function (): void {
            $this->call(RoleAndPermissionSeeder::class);

            $warehouseRole = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();
            $supervisorRole = Role::where('code', RoleCode::INVENTORY_SUPERVISOR->value)->first();

            $baseDate = now()->startOfDay()->subDays(180);
            $nowStr = now()->toDateTimeString();

            Location::where('code', 'like', 'REL-LOC-%')->delete();
            Category::where('code', 'like', 'REL-CAT-%')->delete();
            Unit::where('code', 'like', 'REL-UNT-%')->delete();
            Supplier::where('code', 'like', 'REL-SUP-%')->delete();
            Product::where('sku', 'like', 'REL-SKU-%')->delete();
            $relUserIds = User::where('username', 'like', 'rel_user_%')->pluck('id');
            DB::table('user_locations')->whereIn('user_id', $relUserIds)->delete();
            User::whereIn('id', $relUserIds)->delete();

            // 1. Locations (5) — Location 1 contains search fixture
            $locations = [];
            for ($i = 1; $i <= 5; $i++) {
                $code = sprintf('REL-LOC-%02d', $i);
                $name = $i === 1 ? 'Gudang Utama Release' : "Gudang Release {$i}";
                $locations[] = Location::create([
                    'code' => $code,
                    'name' => $name,
                    'is_active' => true,
                ]);
            }

            $locationIndexById = collect($locations)
                ->values()
                ->mapWithKeys(fn (Location $loc, int $idx) => [$loc->id => $idx]);

            // 2. Categories (20)
            $categories = [];
            for ($i = 1; $i <= 20; $i++) {
                $code = sprintf('REL-CAT-%02d', $i);
                $categories[] = Category::create([
                    'code' => $code,
                    'name' => "Kategori Release {$i}",
                    'is_active' => true,
                ]);
            }

            // 3. Units (10)
            $units = [];
            for ($i = 1; $i <= 10; $i++) {
                $code = sprintf('REL-UNT-%02d', $i);
                $units[] = Unit::create([
                    'code' => $code,
                    'name' => "Satuan {$i}",
                    'symbol' => strtolower($code),
                    'is_active' => true,
                ]);
            }

            // 4. Suppliers (50) — Supplier 1 contains CSV safety fixture (index 0) & search fixture
            $suppliers = [];
            for ($i = 1; $i <= 50; $i++) {
                $code = sprintf('REL-SUP-%02d', $i);
                $name = $i === 1 ? '+FORMULA Supplier Jakarta' : "Supplier Release {$i}";
                $suppliers[] = Supplier::create([
                    'code' => $code,
                    'name' => $name,
                    'is_active' => true,
                ]);
            }

            // 5. Users (20) — Random un-guessable password hash, NO ADMIN role attached
            User::where('username', 'like', 'rel_user_%')->delete();
            $users = [];
            for ($i = 1; $i <= 20; $i++) {
                $username = sprintf('rel_user_%02d', $i);
                $user = User::create([
                    'username' => $username,
                    'name' => "Release User {$i}",
                    'email' => "{$username}@example.com",
                    'password' => Hash::make(Str::password(40)),
                    'is_active' => true,
                ]);

                if ($i <= 3) {
                    $user->roles()->attach($supervisorRole->id);
                } else {
                    $user->roles()->attach($warehouseRole->id);
                }
                $user->locations()->attach(collect($locations)->pluck('id')->toArray());
                $users[] = $user;
            }

            // 6. Products (1,000) — 900 Active, 100 Inactive. Search & CSV safety fixtures included (index 0)
            $productInserts = [];
            for ($i = 1; $i <= 1000; $i++) {
                $sku = sprintf('REL-SKU-%04d', $i);
                $catId = $categories[($i - 1) % 20]->id;
                $unitId = $units[($i - 1) % 10]->id;

                if ($i === 1) {
                    $name = '=FORMULA Produk Kabel';
                } elseif ($i === 2) {
                    $name = 'Produk Baut';
                } else {
                    $name = "Produk Verification {$i}";
                }

                $isActive = $i <= 900;

                $productInserts[] = [
                    'name' => $name,
                    'sku' => $sku,
                    'category_id' => $catId,
                    'unit_id' => $unitId,
                    'minimum_stock' => '5.0000',
                    'is_active' => $isActive,
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ];
            }

            foreach (array_chunk($productInserts, 100) as $chunk) {
                Product::insert($chunk);
            }

            $allProducts = Product::where('sku', 'like', 'REL-SKU-%')->get();
            $firstUser = $users[0];

            // 7. Domain Document Headers with complete lifecycle metadata
            // 100 Posted Stock Receipts
            $receiptDocs = [];
            for ($i = 1; $i <= 100; $i++) {
                $docDate = $baseDate->copy()->addDays(($i - 1) % 181)->toDateString();
                $supplier = $suppliers[($i - 1) % 50];
                $receiptDocs[] = StockReceipt::create([
                    'receipt_number' => sprintf('REL-REC-%04d', $i),
                    'supplier_id' => $supplier->id,
                    'date' => $docDate,
                    'status' => ReceiptStatus::POSTED->value,
                    'notes' => "Release Verification Receipt Fixture {$i}",
                    'created_by' => $firstUser->id,
                ]);
            }

            // 100 Posted Stock Issues (contains -FORMULA CSV safety fixture at index 0)
            $issueDocs = [];
            for ($i = 1; $i <= 100; $i++) {
                $docDate = $baseDate->copy()->addDays(($i - 1) % 181)->toDateString();
                $issueDocs[] = StockIssue::create([
                    'issue_number' => sprintf('REL-ISS-%04d', $i),
                    'purpose' => $i === 1 ? '-FORMULA Release Issue' : "Release Issue Fixture {$i}",
                    'date' => $docDate,
                    'status' => IssueStatus::POSTED->value,
                    'notes' => "Release Verification Issue Fixture {$i}",
                    'created_by' => $firstUser->id,
                ]);
            }

            // 75 Posted Stock Transfers (covers all 5 locations as origin and destination)
            $transferDocs = [];
            for ($i = 1; $i <= 75; $i++) {
                $docDate = $baseDate->copy()->addDays(($i - 1) % 181)->toDateString();
                $origLoc = $locations[($i - 1) % 5];
                $destLoc = $locations[$i % 5];
                $transferDocs[] = StockTransfer::create([
                    'transfer_number' => sprintf('REL-TRF-%04d', $i),
                    'origin_location_id' => $origLoc->id,
                    'destination_location_id' => $destLoc->id,
                    'transfer_date' => $docDate,
                    'status' => TransferStatus::RECEIVED->value,
                    'notes' => "Release Verification Transfer Fixture {$i}",
                    'created_by' => $firstUser->id,
                    'sent_by' => $firstUser->id,
                    'sent_at' => "{$docDate} 10:00:00",
                    'received_by' => $firstUser->id,
                    'received_at' => "{$docDate} 14:00:00",
                ]);
            }

            // 75 Posted Stock Adjustments (contains @FORMULA CSV safety fixture at index 0, covers all locations & both directions)
            $adjustmentDocs = [];
            for ($i = 1; $i <= 75; $i++) {
                $docDate = $baseDate->copy()->addDays(($i - 1) % 181)->toDateString();
                $loc = $locations[($i - 1) % 5];
                $direction = $i <= 38 ? 'INCREASE' : 'DECREASE';
                $reason = $direction === 'INCREASE' ? AdjustmentReason::FOUND->value : AdjustmentReason::DAMAGED->value;
                $notes = $i === 1 ? '@FORMULA Release Adjustment' : "Release Adjustment Fixture {$i}";

                $adjustmentDocs[] = StockAdjustment::create([
                    'adjustment_number' => sprintf('REL-ADJ-%04d', $i),
                    'location_id' => $loc->id,
                    'adjustment_date' => $docDate,
                    'direction' => $direction,
                    'reason_code' => $reason,
                    'status' => AdjustmentStatus::POSTED->value,
                    'notes' => $notes,
                    'created_by' => $firstUser->id,
                    'posted_by' => $firstUser->id,
                    'posted_at' => "{$docDate} 10:00:00",
                ]);
            }

            // 50 Posted Stock Opnames (covers all 5 locations)
            $opnameDocs = [];
            for ($i = 1; $i <= 50; $i++) {
                $docDate = $baseDate->copy()->addDays(($i - 1) % 181)->toDateString();
                $loc = $locations[($i - 1) % 5];
                $opnameDocs[] = StockOpname::create([
                    'opname_number' => sprintf('REL-OPN-%04d', $i),
                    'location_id' => $loc->id,
                    'opname_date' => $docDate,
                    'status' => OpnameStatus::POSTED->value,
                    'notes' => "Release Verification Opname Fixture {$i}",
                    'created_by' => $firstUser->id,
                    'started_by' => $firstUser->id,
                    'started_at' => "{$docDate} 08:00:00",
                    'completed_by' => $firstUser->id,
                    'completed_at' => "{$docDate} 12:00:00",
                    'posted_by' => $firstUser->id,
                    'posted_at' => "{$docDate} 14:00:00",
                ]);
            }

            // 25 Draft Documents (5 of each type, no movements attached)
            for ($i = 1; $i <= 5; $i++) {
                StockReceipt::create([
                    'receipt_number' => sprintf('REL-DRAFT-REC-%04d', $i),
                    'supplier_id' => $suppliers[0]->id,
                    'date' => now()->toDateString(),
                    'status' => ReceiptStatus::DRAFT->value,
                    'notes' => 'Release Draft Receipt',
                    'created_by' => $firstUser->id,
                ]);
                StockIssue::create([
                    'issue_number' => sprintf('REL-DRAFT-ISS-%04d', $i),
                    'purpose' => 'Release Draft Issue',
                    'date' => now()->toDateString(),
                    'status' => IssueStatus::DRAFT->value,
                    'notes' => 'Release Draft Issue',
                    'created_by' => $firstUser->id,
                ]);
                StockTransfer::create([
                    'transfer_number' => sprintf('REL-DRAFT-TRF-%04d', $i),
                    'origin_location_id' => $locations[0]->id,
                    'destination_location_id' => $locations[1]->id,
                    'transfer_date' => now()->toDateString(),
                    'status' => TransferStatus::DRAFT->value,
                    'notes' => 'Release Draft Transfer',
                    'created_by' => $firstUser->id,
                ]);
                StockAdjustment::create([
                    'adjustment_number' => sprintf('REL-DRAFT-ADJ-%04d', $i),
                    'location_id' => $locations[0]->id,
                    'adjustment_date' => now()->toDateString(),
                    'direction' => 'INCREASE',
                    'reason_code' => AdjustmentReason::FOUND->value,
                    'status' => AdjustmentStatus::DRAFT->value,
                    'notes' => 'Release Draft Adjustment',
                    'created_by' => $firstUser->id,
                ]);
                StockOpname::create([
                    'opname_number' => sprintf('REL-DRAFT-OPN-%04d', $i),
                    'location_id' => $locations[0]->id,
                    'opname_date' => now()->toDateString(),
                    'status' => OpnameStatus::DRAFT->value,
                    'notes' => 'Release Draft Opname',
                    'created_by' => $firstUser->id,
                ]);
            }

            // Keyed maps for document items
            $receiptItemsMap = [];
            $issueItemsMap = [];
            $transferItemsMap = [];
            $adjustmentItemsMap = [];
            $opnameItemsMap = [];

            // 8. Event Planning Layer (10,000 movements total)
            $pairEventsMap = [];
            $totalMovementsCount = 0;

            // 8.1 Initial Receipts (5,000 movements: 1 per pair)
            foreach ($allProducts as $pIdx => $product) {
                foreach ($locations as $lIdx => $location) {
                    $pairIdx = ($pIdx * 5) + $lIdx;
                    $mDate = $this->pairBaseDate($pIdx, $lIdx, $baseDate)->toDateTimeString();
                    $recDoc = $receiptDocs[$pairIdx % 100];

                    $recItemKey = "{$recDoc->id}:{$product->id}:{$location->id}";
                    if (! isset($receiptItemsMap[$recItemKey])) {
                        $receiptItemsMap[$recItemKey] = [
                            'stock_receipt_id' => $recDoc->id,
                            'product_id' => $product->id,
                            'location_id' => $location->id,
                            'quantity' => '50.0000',
                            'created_at' => $nowStr,
                            'updated_at' => $nowStr,
                        ];
                    }

                    $pairKey = "{$product->id}:{$location->id}";
                    $pairEventsMap[$pairKey][] = [
                        'event_key' => "01_RECEIPT_{$totalMovementsCount}",
                        'type' => MovementType::RECEIPT->value,
                        'product_id' => $product->id,
                        'location_id' => $location->id,
                        'change_qty' => '50.0000',
                        'ref_type' => StockReceipt::class,
                        'ref_id' => $recDoc->id,
                        'ref_num' => $recDoc->receipt_number,
                        'proposed_date' => $mDate,
                    ];
                    $totalMovementsCount++;
                }
            }

            // 8.2 Secondary Movement Slots (5,000 movements)
            // A. 300 Issue Events (0..99 zero stock, 100..199 low stock, 200..299 at min stock)
            for ($pairIdx = 0; $pairIdx < 300; $pairIdx++) {
                $pIdx = intdiv($pairIdx, 5);
                $lIdx = $pairIdx % 5;
                $product = $allProducts[$pIdx];
                $location = $locations[$lIdx];
                $mDate = $this->pairBaseDate($pIdx, $lIdx, $baseDate)->addHours(2)->toDateTimeString();

                $issDoc = $issueDocs[$pairIdx % 100];

                if ($pairIdx < 100) {
                    $qtyChange = '50.0000'; // zero stock final
                } elseif ($pairIdx < 200) {
                    $qtyChange = '48.0000'; // low stock final (2.0000)
                } else {
                    $qtyChange = '45.0000'; // at min stock final (5.0000)
                }

                $issItemKey = "{$issDoc->id}:{$product->id}:{$location->id}";
                if (! isset($issueItemsMap[$issItemKey])) {
                    $issueItemsMap[$issItemKey] = [
                        'stock_issue_id' => $issDoc->id,
                        'product_id' => $product->id,
                        'location_id' => $location->id,
                        'quantity' => $qtyChange,
                        'created_at' => $nowStr,
                        'updated_at' => $nowStr,
                    ];
                }

                $pairKey = "{$product->id}:{$location->id}";
                $pairEventsMap[$pairKey][] = [
                    'event_key' => "02_ISSUE_{$totalMovementsCount}",
                    'type' => MovementType::ISSUE->value,
                    'product_id' => $product->id,
                    'location_id' => $location->id,
                    'change_qty' => $qtyChange,
                    'ref_type' => StockIssue::class,
                    'ref_id' => $issDoc->id,
                    'ref_num' => $issDoc->issue_number,
                    'proposed_date' => $mDate,
                ];
                $totalMovementsCount++;
            }

            // B. 500 Transfer Items -> 1,000 Secondary Movements (500 TRANSFER_OUT + 500 TRANSFER_IN)
            for ($trfIdx = 0; $trfIdx < 500; $trfIdx++) {
                $pairIdx = 300 + $trfIdx;
                $pIdx = intdiv($pairIdx, 5);
                $product = $allProducts[$pIdx];

                $trfDoc = $transferDocs[$trfIdx % 75];
                $origLocId = $trfDoc->origin_location_id;
                $destLocId = $trfDoc->destination_location_id;
                $origLIdx = $locationIndexById[$origLocId];
                $destLIdx = $locationIndexById[$destLocId];

                $origMDate = $this->pairBaseDate($pIdx, $origLIdx, $baseDate)->addHours(2)->toDateTimeString();
                $destMDate = $this->pairBaseDate($pIdx, $destLIdx, $baseDate)->addHours(3)->toDateTimeString();
                $qtyChange = '10.0000';

                $trfItemKey = "{$trfDoc->id}:{$product->id}";
                if (! isset($transferItemsMap[$trfItemKey])) {
                    $transferItemsMap[$trfItemKey] = [
                        'stock_transfer_id' => $trfDoc->id,
                        'product_id' => $product->id,
                        'quantity' => $qtyChange,
                        'created_at' => $nowStr,
                        'updated_at' => $nowStr,
                    ];
                }

                // OUT on origin location
                $origPairKey = "{$product->id}:{$origLocId}";
                $pairEventsMap[$origPairKey][] = [
                    'event_key' => "02_TRF_OUT_{$totalMovementsCount}",
                    'type' => MovementType::TRANSFER_OUT->value,
                    'product_id' => $product->id,
                    'location_id' => $origLocId,
                    'change_qty' => $qtyChange,
                    'ref_type' => StockTransfer::class,
                    'ref_id' => $trfDoc->id,
                    'ref_num' => $trfDoc->transfer_number,
                    'proposed_date' => $origMDate,
                ];
                $totalMovementsCount++;

                // IN on destination location
                $destPairKey = "{$product->id}:{$destLocId}";
                $pairEventsMap[$destPairKey][] = [
                    'event_key' => "02_TRF_IN_{$totalMovementsCount}",
                    'type' => MovementType::TRANSFER_IN->value,
                    'product_id' => $product->id,
                    'location_id' => $destLocId,
                    'change_qty' => $qtyChange,
                    'ref_type' => StockTransfer::class,
                    'ref_id' => $trfDoc->id,
                    'ref_num' => $trfDoc->transfer_number,
                    'proposed_date' => $destMDate,
                ];
                $totalMovementsCount++;
            }

            // C. 1,850 Adjustment Movements (925 ADJUSTMENT_IN + 925 ADJUSTMENT_OUT)
            $incAdjDocs = array_values(array_filter($adjustmentDocs, fn ($d) => $d->direction === 'INCREASE'));
            $decAdjDocs = array_values(array_filter($adjustmentDocs, fn ($d) => $d->direction === 'DECREASE'));

            for ($adjIdx = 0; $adjIdx < 1850; $adjIdx++) {
                $pairIdx = 800 + $adjIdx;
                $pIdx = intdiv($pairIdx, 5);
                $product = $allProducts[$pIdx];

                $isIncrease = $adjIdx < 925;
                $mType = $isIncrease ? MovementType::ADJUSTMENT_IN->value : MovementType::ADJUSTMENT_OUT->value;
                $docList = $isIncrease ? $incAdjDocs : $decAdjDocs;
                $doc = $docList[$adjIdx % count($docList)];
                $locId = $doc->location_id;
                $lIdx = $locationIndexById[$locId];
                $mDate = $this->pairBaseDate($pIdx, $lIdx, $baseDate)->addHours(2)->toDateTimeString();
                $qtyChange = '10.0000';

                $adjItemKey = "{$doc->id}:{$product->id}";
                if (! isset($adjustmentItemsMap[$adjItemKey])) {
                    $adjustmentItemsMap[$adjItemKey] = [
                        'stock_adjustment_id' => $doc->id,
                        'product_id' => $product->id,
                        'quantity' => $qtyChange,
                        'item_notes' => 'Release adjustment item',
                        'created_at' => $nowStr,
                        'updated_at' => $nowStr,
                    ];
                }

                $pairKey = "{$product->id}:{$locId}";
                $pairEventsMap[$pairKey][] = [
                    'event_key' => "02_ADJ_{$totalMovementsCount}",
                    'type' => $mType,
                    'product_id' => $product->id,
                    'location_id' => $locId,
                    'change_qty' => $qtyChange,
                    'ref_type' => StockAdjustment::class,
                    'ref_id' => $doc->id,
                    'ref_num' => $doc->adjustment_number,
                    'proposed_date' => $mDate,
                ];
                $totalMovementsCount++;
            }

            // D. 1,850 Opname Movements (925 OPNAME_IN + 925 OPNAME_OUT)
            for ($opnIdx = 0; $opnIdx < 1850; $opnIdx++) {
                $pairIdx = 2650 + $opnIdx;
                $pIdx = intdiv($pairIdx, 5);
                $product = $allProducts[$pIdx];

                $isIncrease = $opnIdx < 925;
                $mType = $isIncrease ? MovementType::OPNAME_IN->value : MovementType::OPNAME_OUT->value;
                $doc = $opnameDocs[$opnIdx % 50];
                $locId = $doc->location_id;
                $lIdx = $locationIndexById[$locId];
                $mDate = $this->pairBaseDate($pIdx, $lIdx, $baseDate)->addHours(2)->toDateTimeString();
                $qtyChange = '10.0000';

                $countedQty = $isIncrease ? '60.0000' : '40.0000';
                $variance = $isIncrease ? '10.0000' : '-10.0000';

                $opnItemKey = "{$doc->id}:{$product->id}";
                if (! isset($opnameItemsMap[$opnItemKey])) {
                    $opnameItemsMap[$opnItemKey] = [
                        'stock_opname_id' => $doc->id,
                        'product_id' => $product->id,
                        'snapshot_quantity' => '50.0000',
                        'counted_quantity' => $countedQty,
                        'variance_quantity' => $variance,
                        'count_version' => 1,
                        'counted_by' => $firstUser->id,
                        'counted_at' => $nowStr,
                        'item_notes' => 'Release opname item',
                        'is_unexpected' => false,
                        'created_at' => $nowStr,
                        'updated_at' => $nowStr,
                    ];
                }

                $pairKey = "{$product->id}:{$locId}";
                $pairEventsMap[$pairKey][] = [
                    'event_key' => "02_OPN_{$totalMovementsCount}",
                    'type' => $mType,
                    'product_id' => $product->id,
                    'location_id' => $locId,
                    'change_qty' => $qtyChange,
                    'ref_type' => StockOpname::class,
                    'ref_id' => $doc->id,
                    'ref_num' => $doc->opname_number,
                    'proposed_date' => $mDate,
                ];
                $totalMovementsCount++;
            }

            // 9. Stream Execution & Balance Calculation per Pair with Sequential Chronological Order
            $movementInserts = [];
            $balanceInserts = [];

            $inTypes = [
                MovementType::RECEIPT->value,
                MovementType::TRANSFER_IN->value,
                MovementType::ADJUSTMENT_IN->value,
                MovementType::OPNAME_IN->value,
            ];

            foreach ($allProducts as $pIdx => $product) {
                foreach ($locations as $lIdx => $location) {
                    $pairKey = "{$product->id}:{$location->id}";
                    $events = $pairEventsMap[$pairKey] ?? [];

                    usort(
                        $events,
                        fn (array $left, array $right): int => [$left['proposed_date'], $left['event_key']] <=> [$right['proposed_date'], $right['event_key']]
                    );

                    $pairBase = $this->pairBaseDate($pIdx, $lIdx, $baseDate);
                    $qtyBefore = '0.0000';

                    foreach ($events as $index => $evt) {
                        $sequence = $index + 1;
                        $mDate = $pairBase->copy()->addMinutes($sequence)->toDateTimeString();
                        $changeQty = (string) $evt['change_qty'];

                        if (in_array($evt['type'], $inTypes, true)) {
                            $qtyAfter = bcadd($qtyBefore, $changeQty, 4);
                        } else {
                            $qtyAfter = bcsub($qtyBefore, $changeQty, 4);
                        }

                        if (bccomp($qtyAfter, '0.0000', 4) < 0) {
                            throw new RuntimeException("Negative stock computed for pair {$pairKey}");
                        }

                        $movementInserts[] = [
                            'movement_id' => (string) Str::uuid(),
                            'product_id' => $evt['product_id'],
                            'location_id' => $evt['location_id'],
                            'movement_type' => $evt['type'],
                            'quantity' => $changeQty,
                            'quantity_before' => $qtyBefore,
                            'quantity_after' => $qtyAfter,
                            'reference_type' => $evt['ref_type'],
                            'reference_id' => $evt['ref_id'],
                            'reference_number' => $evt['ref_num'],
                            'occurred_at' => $mDate,
                            'created_by' => $firstUser->id,
                            'created_at' => $mDate,
                            'updated_at' => $mDate,
                        ];

                        $qtyBefore = $qtyAfter;
                    }

                    $balanceInserts[] = [
                        'product_id' => $product->id,
                        'location_id' => $location->id,
                        'quantity' => $qtyBefore,
                        'created_at' => $nowStr,
                        'updated_at' => $nowStr,
                    ];
                }
            }

            // Bulk insert items
            foreach (array_chunk(array_values($receiptItemsMap), 100) as $chunk) {
                StockReceiptItem::insert($chunk);
            }
            foreach (array_chunk(array_values($issueItemsMap), 100) as $chunk) {
                StockIssueItem::insert($chunk);
            }
            foreach (array_chunk(array_values($transferItemsMap), 100) as $chunk) {
                StockTransferItem::insert($chunk);
            }
            foreach (array_chunk(array_values($adjustmentItemsMap), 100) as $chunk) {
                StockAdjustmentItem::insert($chunk);
            }
            foreach (array_chunk(array_values($opnameItemsMap), 100) as $chunk) {
                StockOpnameItem::insert($chunk);
            }

            // Bulk insert movements and balances
            foreach (array_chunk($movementInserts, 50) as $chunk) {
                StockMovement::insert($chunk);
            }
            foreach (array_chunk($balanceInserts, 50) as $chunk) {
                InventoryBalance::insert($chunk);
            }

            $this->command?->info("Release verification dataset seeded: {$totalMovementsCount} movements created.");
        };

        if (DB::transactionLevel() > 0) {
            $seedCallback();
        } else {
            DB::transaction($seedCallback);
        }
    }

    private function pairBaseDate(
        int $productIndex,
        int $locationIndex,
        CarbonInterface $baseDate
    ): CarbonInterface {
        $pairIndex = ($productIndex * 5) + $locationIndex;

        return $baseDate
            ->copy()
            ->addDays($pairIndex % 181);
    }

    /**
     * Audit whether the release verification dataset is complete and valid.
     */
    public function isDatasetCompleteAndValid(): bool
    {
        $releaseProductIds = Product::query()
            ->where('sku', 'like', 'REL-SKU-%')
            ->pluck('id');

        if ($releaseProductIds->count() !== 1000) {
            return false;
        }

        $releaseLocationIds = Location::query()
            ->where('code', 'like', 'REL-LOC-%')
            ->pluck('id');

        if ($releaseLocationIds->count() !== 5) {
            return false;
        }

        if (Category::where('code', 'like', 'REL-CAT-%')->count() !== 20) {
            return false;
        }
        if (Unit::where('code', 'like', 'REL-UNT-%')->count() !== 10) {
            return false;
        }
        if (Supplier::where('code', 'like', 'REL-SUP-%')->count() !== 50) {
            return false;
        }
        if (User::where('username', 'like', 'rel_user_%')->count() !== 20) {
            return false;
        }

        // Verify document header counts with enum-safe checks
        if (StockReceipt::where('receipt_number', 'like', 'REL-REC-%')->where('status', ReceiptStatus::POSTED->value)->count() !== 100) {
            return false;
        }
        if (StockIssue::where('issue_number', 'like', 'REL-ISS-%')->where('status', IssueStatus::POSTED->value)->count() !== 100) {
            return false;
        }
        if (StockTransfer::where('transfer_number', 'like', 'REL-TRF-%')->where('status', TransferStatus::RECEIVED->value)->count() !== 75) {
            return false;
        }
        if (StockAdjustment::where('adjustment_number', 'like', 'REL-ADJ-%')->where('status', AdjustmentStatus::POSTED->value)->count() !== 75) {
            return false;
        }
        if (StockOpname::where('opname_number', 'like', 'REL-OPN-%')->where('status', OpnameStatus::POSTED->value)->count() !== 50) {
            return false;
        }

        // Verify exact draft document counts per model
        if (StockReceipt::where('receipt_number', 'like', 'REL-DRAFT-REC-%')->where('status', ReceiptStatus::DRAFT->value)->count() !== 5) {
            return false;
        }
        if (StockIssue::where('issue_number', 'like', 'REL-DRAFT-ISS-%')->where('status', IssueStatus::DRAFT->value)->count() !== 5) {
            return false;
        }
        if (StockTransfer::where('transfer_number', 'like', 'REL-DRAFT-TRF-%')->where('status', TransferStatus::DRAFT->value)->count() !== 5) {
            return false;
        }
        if (StockAdjustment::where('adjustment_number', 'like', 'REL-DRAFT-ADJ-%')->where('status', AdjustmentStatus::DRAFT->value)->count() !== 5) {
            return false;
        }
        if (StockOpname::where('opname_number', 'like', 'REL-DRAFT-OPN-%')->where('status', OpnameStatus::DRAFT->value)->count() !== 5) {
            return false;
        }

        // Verify zero movements referencing draft documents
        if ($this->draftDocumentHasMovements(StockReceipt::class, StockReceipt::where('receipt_number', 'like', 'REL-DRAFT-REC-%')->pluck('id'))) {
            return false;
        }
        if ($this->draftDocumentHasMovements(StockIssue::class, StockIssue::where('issue_number', 'like', 'REL-DRAFT-ISS-%')->pluck('id'))) {
            return false;
        }
        if ($this->draftDocumentHasMovements(StockTransfer::class, StockTransfer::where('transfer_number', 'like', 'REL-DRAFT-TRF-%')->pluck('id'))) {
            return false;
        }
        if ($this->draftDocumentHasMovements(StockAdjustment::class, StockAdjustment::where('adjustment_number', 'like', 'REL-DRAFT-ADJ-%')->pluck('id'))) {
            return false;
        }
        if ($this->draftDocumentHasMovements(StockOpname::class, StockOpname::where('opname_number', 'like', 'REL-DRAFT-OPN-%')->pluck('id'))) {
            return false;
        }

        // Verify total release balances count equals 5000 and zero balances exist outside release locations
        $totalReleaseBalances = InventoryBalance::whereIn('product_id', $releaseProductIds)->count();
        if ($totalReleaseBalances !== 5000) {
            return false;
        }

        $outsideBalanceExists = InventoryBalance::whereIn('product_id', $releaseProductIds)
            ->whereNotIn('location_id', $releaseLocationIds)
            ->exists();
        if ($outsideBalanceExists) {
            return false;
        }

        // Verify exact Cartesian pair keys for balances
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

        if ($expectedPairKeys !== $actualBalanceKeys) {
            return false;
        }

        // Verify exact movement count for release products
        $movementsCount = StockMovement::whereIn('product_id', $releaseProductIds)->count();
        if ($movementsCount !== 10000) {
            return false;
        }

        // Verify exact movement types parity
        $expectedTypes = collect([
            MovementType::RECEIPT->value,
            MovementType::ISSUE->value,
            MovementType::TRANSFER_OUT->value,
            MovementType::TRANSFER_IN->value,
            MovementType::ADJUSTMENT_IN->value,
            MovementType::ADJUSTMENT_OUT->value,
            MovementType::OPNAME_IN->value,
            MovementType::OPNAME_OUT->value,
        ])->sort()->values()->all();

        $actualTypes = StockMovement::query()
            ->whereIn('product_id', $releaseProductIds)
            ->distinct()
            ->pluck('movement_type')
            ->sort()
            ->values()
            ->all();

        if ($expectedTypes !== $actualTypes) {
            return false;
        }

        // Verify no negative quantities
        $hasNegativeMovement = StockMovement::whereIn('product_id', $releaseProductIds)
            ->where(function ($q) {
                $q->where('quantity_before', '<', 0)
                    ->orWhere('quantity_after', '<', 0);
            })
            ->exists();

        if ($hasNegativeMovement) {
            return false;
        }

        $hasNegativeBalance = InventoryBalance::whereIn('product_id', $releaseProductIds)
            ->where('quantity', '<', 0)
            ->exists();

        if ($hasNegativeBalance) {
            return false;
        }

        // Modular Verifiers for Document-Item-Movement Traceability & Metadata
        if (! $this->validateReceiptTraceability($releaseProductIds)) {
            return false;
        }
        if (! $this->validateIssueTraceability($releaseProductIds)) {
            return false;
        }
        if (! $this->validateTransferTraceability($releaseProductIds)) {
            return false;
        }
        if (! $this->validateAdjustmentTraceability($releaseProductIds)) {
            return false;
        }
        if (! $this->validateOpnameTraceability($releaseProductIds)) {
            return false;
        }

        // Single-pass memory-safe cursor audit for all 5,000 pairs & chronological movement chains
        $balancesMap = InventoryBalance::whereIn('product_id', $releaseProductIds)
            ->whereIn('location_id', $releaseLocationIds)
            ->get(['product_id', 'location_id', 'quantity'])
            ->keyBy(fn ($b) => "{$b->product_id}:{$b->location_id}");

        if ($balancesMap->count() !== 5000) {
            return false;
        }

        $currentPairKey = null;
        $runningDelta = '0.0000';
        $prevAfter = null;
        $prevOccurredAt = null;
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
            // Reject movement if location is not in release locations
            if (! $releaseLocationIds->contains($m->location_id)) {
                return false;
            }

            // Verify movement quantity equals abs(quantity_after - quantity_before)
            $delta = bcsub((string) $m->quantity_after, (string) $m->quantity_before, 4);
            $absDelta = str_starts_with($delta, '-') ? substr($delta, 1) : $delta;
            if (DecimalQuantity::normalize($m->quantity) !== DecimalQuantity::normalize($absDelta)) {
                return false;
            }

            // Verify movement type & direction contract
            if (in_array($m->movement_type, $inTypes, true)) {
                if (bccomp($delta, '0.0000', 4) <= 0) {
                    return false;
                }
            } else {
                if (bccomp($delta, '0.0000', 4) >= 0) {
                    return false;
                }
            }

            $pairKey = "{$m->product_id}:{$m->location_id}";

            if ($currentPairKey !== $pairKey) {
                // Verify previous pair if exists
                if ($currentPairKey !== null) {
                    $expectedBalanceModel = $balancesMap->get($currentPairKey);
                    if (! $expectedBalanceModel) {
                        return false;
                    }
                    $expectedQty = DecimalQuantity::normalize($expectedBalanceModel->quantity);
                    if (DecimalQuantity::normalize($runningDelta) !== $expectedQty) {
                        return false;
                    }
                    if (DecimalQuantity::normalize($lastAfter) !== $expectedQty) {
                        return false;
                    }
                    $auditedPairKeys[] = $currentPairKey;
                }

                // Reset group
                $currentPairKey = $pairKey;
                $runningDelta = '0.0000';
                $prevAfter = null;
                $prevOccurredAt = null;

                // First movement in pair must start at 0.0000
                if (DecimalQuantity::normalize($m->quantity_before) !== '0.0000') {
                    return false;
                }
            } else {
                // Chronological check: occurred_at must be non-decreasing
                if ($prevOccurredAt !== null && strcmp((string) $m->occurred_at, (string) $prevOccurredAt) < 0) {
                    return false;
                }
                // Continuity check: current before == previous after
                if (DecimalQuantity::normalize($m->quantity_before) !== DecimalQuantity::normalize($prevAfter)) {
                    return false;
                }
            }

            $runningDelta = bcadd($runningDelta, $delta, 4);
            $prevAfter = (string) $m->quantity_after;
            $prevOccurredAt = (string) $m->occurred_at;
            $lastAfter = (string) $m->quantity_after;
        }

        // Verify final pair
        if ($currentPairKey !== null) {
            $expectedBalanceModel = $balancesMap->get($currentPairKey);
            if (! $expectedBalanceModel) {
                return false;
            }
            $expectedQty = DecimalQuantity::normalize($expectedBalanceModel->quantity);
            if (DecimalQuantity::normalize($runningDelta) !== $expectedQty) {
                return false;
            }
            if (DecimalQuantity::normalize($lastAfter) !== $expectedQty) {
                return false;
            }
            $auditedPairKeys[] = $currentPairKey;
        }

        sort($auditedPairKeys);

        return $auditedPairKeys === $expectedPairKeys;
    }

    private function draftDocumentHasMovements(string $modelClass, Collection $draftIds): bool
    {
        if ($draftIds->isEmpty()) {
            return false;
        }

        return StockMovement::where('reference_type', $modelClass)
            ->whereIn('reference_id', $draftIds)
            ->exists();
    }

    private function validateReceiptTraceability(Collection $releaseProductIds): bool
    {
        $postedReceipts = StockReceipt::where('receipt_number', 'like', 'REL-REC-%')->get();
        if ($postedReceipts->count() !== 100) {
            return false;
        }

        foreach ($postedReceipts as $rec) {
            if (! $rec->isPosted()) {
                return false;
            }
            if ($rec->items()->count() === 0) {
                return false;
            }
        }

        $receiptItemsCount = StockReceiptItem::whereIn('stock_receipt_id', $postedReceipts->pluck('id'))->count();
        $receiptMovementsCount = StockMovement::where('reference_type', StockReceipt::class)
            ->whereIn('reference_id', $postedReceipts->pluck('id'))
            ->count();

        if ($receiptItemsCount !== $receiptMovementsCount) {
            return false;
        }

        $receiptMovements = StockMovement::where('reference_type', StockReceipt::class)
            ->whereIn('product_id', $releaseProductIds)
            ->get();

        foreach ($receiptMovements as $m) {
            if ($m->movement_type !== MovementType::RECEIPT->value) {
                return false;
            }
            $doc = $postedReceipts->firstWhere('id', $m->reference_id);
            if (! $doc || $doc->receipt_number !== $m->reference_number || ! $doc->isPosted()) {
                return false;
            }

            $matchingItems = StockReceiptItem::where('stock_receipt_id', $doc->id)
                ->where('product_id', $m->product_id)
                ->where('location_id', $m->location_id)
                ->get();

            if ($matchingItems->count() !== 1) {
                return false;
            }

            $item = $matchingItems->first();
            if (DecimalQuantity::normalize($item->quantity) !== DecimalQuantity::normalize($m->quantity)) {
                return false;
            }
        }

        return true;
    }

    private function validateIssueTraceability(Collection $releaseProductIds): bool
    {
        $postedIssues = StockIssue::where('issue_number', 'like', 'REL-ISS-%')->get();
        if ($postedIssues->count() !== 100) {
            return false;
        }

        foreach ($postedIssues as $iss) {
            if (! $iss->isPosted()) {
                return false;
            }
            if ($iss->items()->count() === 0) {
                return false;
            }
        }

        $issueItemsCount = StockIssueItem::whereIn('stock_issue_id', $postedIssues->pluck('id'))->count();
        $issueMovementsCount = StockMovement::where('reference_type', StockIssue::class)
            ->whereIn('reference_id', $postedIssues->pluck('id'))
            ->count();

        if ($issueItemsCount !== $issueMovementsCount) {
            return false;
        }

        $issueMovements = StockMovement::where('reference_type', StockIssue::class)
            ->whereIn('product_id', $releaseProductIds)
            ->get();

        foreach ($issueMovements as $m) {
            if ($m->movement_type !== MovementType::ISSUE->value) {
                return false;
            }
            $doc = $postedIssues->firstWhere('id', $m->reference_id);
            if (! $doc || $doc->issue_number !== $m->reference_number || ! $doc->isPosted()) {
                return false;
            }

            $matchingItems = StockIssueItem::where('stock_issue_id', $doc->id)
                ->where('product_id', $m->product_id)
                ->where('location_id', $m->location_id)
                ->get();

            if ($matchingItems->count() !== 1) {
                return false;
            }

            $item = $matchingItems->first();
            if (DecimalQuantity::normalize($item->quantity) !== DecimalQuantity::normalize($m->quantity)) {
                return false;
            }
        }

        return true;
    }

    private function validateTransferTraceability(Collection $releaseProductIds): bool
    {
        $receivedTransfers = StockTransfer::where('transfer_number', 'like', 'REL-TRF-%')->get();
        if ($receivedTransfers->count() !== 75) {
            return false;
        }

        foreach ($receivedTransfers as $trf) {
            if ($trf->status !== TransferStatus::RECEIVED) {
                return false;
            }
            if ($trf->items()->count() === 0) {
                return false;
            }
            if (! $trf->sent_by || ! $trf->sent_at || ! $trf->received_by || ! $trf->received_at) {
                return false;
            }
            if (strcmp((string) $trf->sent_at, (string) $trf->received_at) > 0) {
                return false;
            }
        }

        $transferItems = StockTransferItem::whereIn('stock_transfer_id', $receivedTransfers->pluck('id'))->get();
        $transferMovementCount = StockMovement::query()
            ->where('reference_type', StockTransfer::class)
            ->whereIn('reference_id', $receivedTransfers->pluck('id'))
            ->count();

        if ($transferMovementCount !== $transferItems->count() * 2) {
            return false;
        }

        $transferMovements = StockMovement::where('reference_type', StockTransfer::class)
            ->whereIn('product_id', $releaseProductIds)
            ->get();

        foreach ($transferMovements as $m) {
            if ($m->movement_type !== MovementType::TRANSFER_OUT->value && $m->movement_type !== MovementType::TRANSFER_IN->value) {
                return false;
            }

            $doc = $receivedTransfers->firstWhere('id', $m->reference_id);
            if (! $doc || $doc->transfer_number !== $m->reference_number || $doc->status !== TransferStatus::RECEIVED) {
                return false;
            }

            $expectedLocId = $m->movement_type === MovementType::TRANSFER_OUT->value ? $doc->origin_location_id : $doc->destination_location_id;
            if ($m->location_id !== $expectedLocId) {
                return false;
            }

            $matchingItems = StockTransferItem::where('stock_transfer_id', $doc->id)
                ->where('product_id', $m->product_id)
                ->get();

            if ($matchingItems->count() !== 1) {
                return false;
            }

            $item = $matchingItems->first();
            if (DecimalQuantity::normalize($item->quantity) !== DecimalQuantity::normalize($m->quantity)) {
                return false;
            }
        }

        return true;
    }

    private function validateAdjustmentTraceability(Collection $releaseProductIds): bool
    {
        $postedAdjustments = StockAdjustment::where('adjustment_number', 'like', 'REL-ADJ-%')->get();
        if ($postedAdjustments->count() !== 75) {
            return false;
        }

        foreach ($postedAdjustments as $adj) {
            if (! $adj->isPosted()) {
                return false;
            }
            if ($adj->items()->count() === 0) {
                return false;
            }
            if (! $adj->posted_by || ! $adj->posted_at) {
                return false;
            }
        }

        $adjItemsCount = StockAdjustmentItem::whereIn('stock_adjustment_id', $postedAdjustments->pluck('id'))->count();
        $adjMovementsCount = StockMovement::where('reference_type', StockAdjustment::class)
            ->whereIn('reference_id', $postedAdjustments->pluck('id'))
            ->count();

        if ($adjItemsCount !== $adjMovementsCount) {
            return false;
        }

        $adjMovements = StockMovement::where('reference_type', StockAdjustment::class)
            ->whereIn('product_id', $releaseProductIds)
            ->get();

        foreach ($adjMovements as $m) {
            $doc = $postedAdjustments->firstWhere('id', $m->reference_id);
            if (! $doc || $doc->adjustment_number !== $m->reference_number || ! $doc->isPosted()) {
                return false;
            }
            if ($doc->location_id !== $m->location_id) {
                return false;
            }

            if ($m->movement_type === MovementType::ADJUSTMENT_IN->value) {
                if ($doc->direction !== 'INCREASE') {
                    return false;
                }
            } elseif ($m->movement_type === MovementType::ADJUSTMENT_OUT->value) {
                if ($doc->direction !== 'DECREASE') {
                    return false;
                }
            } else {
                return false;
            }

            $matchingItems = StockAdjustmentItem::where('stock_adjustment_id', $doc->id)
                ->where('product_id', $m->product_id)
                ->get();

            if ($matchingItems->count() !== 1) {
                return false;
            }

            $item = $matchingItems->first();
            if (DecimalQuantity::normalize($item->quantity) !== DecimalQuantity::normalize($m->quantity)) {
                return false;
            }
        }

        return true;
    }

    private function validateOpnameTraceability(Collection $releaseProductIds): bool
    {
        $postedOpnames = StockOpname::where('opname_number', 'like', 'REL-OPN-%')->get();
        if ($postedOpnames->count() !== 50) {
            return false;
        }

        foreach ($postedOpnames as $opn) {
            if (! $opn->isPosted()) {
                return false;
            }
            if ($opn->items()->count() === 0) {
                return false;
            }
            if (! $opn->started_by || ! $opn->started_at || ! $opn->completed_by || ! $opn->completed_at || ! $opn->posted_by || ! $opn->posted_at) {
                return false;
            }
            if (strcmp((string) $opn->started_at, (string) $opn->completed_at) > 0 || strcmp((string) $opn->completed_at, (string) $opn->posted_at) > 0) {
                return false;
            }
        }

        $opnItemsCount = StockOpnameItem::whereIn('stock_opname_id', $postedOpnames->pluck('id'))->count();
        $opnMovementsCount = StockMovement::where('reference_type', StockOpname::class)
            ->whereIn('reference_id', $postedOpnames->pluck('id'))
            ->count();

        if ($opnItemsCount !== $opnMovementsCount) {
            return false;
        }

        $opnMovements = StockMovement::where('reference_type', StockOpname::class)
            ->whereIn('product_id', $releaseProductIds)
            ->get();

        foreach ($opnMovements as $m) {
            $doc = $postedOpnames->firstWhere('id', $m->reference_id);
            if (! $doc || $doc->opname_number !== $m->reference_number || ! $doc->isPosted()) {
                return false;
            }
            if ($doc->location_id !== $m->location_id) {
                return false;
            }

            $matchingItems = StockOpnameItem::where('stock_opname_id', $doc->id)
                ->where('product_id', $m->product_id)
                ->get();

            if ($matchingItems->count() !== 1) {
                return false;
            }

            $item = $matchingItems->first();

            if ($m->movement_type === MovementType::OPNAME_IN->value) {
                if (bccomp(DecimalQuantity::normalize($item->variance_quantity), '0.0000', 4) <= 0) {
                    return false;
                }
                if (DecimalQuantity::normalize($item->variance_quantity) !== DecimalQuantity::normalize($m->quantity)) {
                    return false;
                }
            } elseif ($m->movement_type === MovementType::OPNAME_OUT->value) {
                if (bccomp(DecimalQuantity::normalize($item->variance_quantity), '0.0000', 4) >= 0) {
                    return false;
                }
                $absVar = str_starts_with((string) $item->variance_quantity, '-') ? substr((string) $item->variance_quantity, 1) : (string) $item->variance_quantity;
                if (DecimalQuantity::normalize($absVar) !== DecimalQuantity::normalize($m->quantity)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }
}
