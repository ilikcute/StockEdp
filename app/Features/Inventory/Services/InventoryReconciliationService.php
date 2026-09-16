<?php

namespace App\Features\Inventory\Services;

use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Enums\StockCondition;
use App\Features\Inventory\Repositories\Contracts\InventoryBalanceRepositoryInterface;
use App\Features\Reporting\Helpers\DecimalQuantity;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryReconciliationService
{
    public function __construct(
        private readonly InventoryBalanceRepositoryInterface $balanceRepository
    ) {}

    /**
     * Scan all inventory items and locations to detect any mismatch
     * between physical balance (inventory_balances) and ledger movements (stock_movements).
     */
    public function scanDiscrepancies(?int $locationId = null, ?int $productId = null, ?string $condition = null): array
    {
        // 1. Calculate aggregated movements per product, location, condition
        $movementsQuery = DB::table('stock_movements')
            ->select([
                'product_id',
                'location_id',
                'condition',
                DB::raw("SUM(CASE 
                    WHEN movement_type IN (
                        '".MovementType::RECEIPT->value."',
                        '".MovementType::RECEIPT_GA->value."',
                        '".MovementType::TRANSFER_IN->value."',
                        '".MovementType::REPLACEMENT_PULL->value."',
                        '".MovementType::RETURN_TO_WAREHOUSE->value."',
                        '".MovementType::ADJUSTMENT_IN->value."',
                        '".MovementType::OPNAME_IN->value."'
                    ) THEN quantity 
                    ELSE -quantity 
                END) as expected_quantity"),
                DB::raw('COUNT(id) as movement_count'),
            ])
            ->groupBy(['product_id', 'location_id', 'condition']);

        if ($locationId) {
            $movementsQuery->where('location_id', $locationId);
        }
        if ($productId) {
            $movementsQuery->where('product_id', $productId);
        }
        if ($condition) {
            $movementsQuery->where('condition', $condition);
        }

        $movementAggregates = $movementsQuery->get()->keyBy(function ($row) {
            return "{$row->location_id}_{$row->product_id}_{$row->condition}";
        });

        // 2. Fetch inventory_balances
        $balancesQuery = DB::table('inventory_balances')
            ->select([
                'id',
                'location_id',
                'product_id',
                'condition',
                'quantity',
                'updated_at',
            ]);

        if ($locationId) {
            $balancesQuery->where('location_id', $locationId);
        }
        if ($productId) {
            $balancesQuery->where('product_id', $productId);
        }
        if ($condition) {
            $balancesQuery->where('condition', $condition);
        }

        $balances = $balancesQuery->get()->keyBy(function ($row) {
            return "{$row->location_id}_{$row->product_id}_{$row->condition}";
        });

        // 3. Union of all unique keys
        $allKeys = $movementAggregates->keys()->merge($balances->keys())->unique();

        // 4. Fetch metadata (products & locations) for presentation
        $allProductIds = [];
        $allLocationIds = [];
        foreach ($allKeys as $key) {
            [$locId, $prodId] = explode('_', $key);
            $allLocationIds[] = (int) $locId;
            $allProductIds[] = (int) $prodId;
        }

        $products = DB::table('products')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->whereIn('products.id', array_unique($allProductIds))
            ->select([
                'products.id',
                'products.sku',
                'products.name',
                'units.name as unit_name',
            ])
            ->get()
            ->keyBy('id');

        $locations = DB::table('locations')
            ->whereIn('id', array_unique($allLocationIds))
            ->select(['id', 'code', 'name', 'type'])
            ->get()
            ->keyBy('id');

        // 5. Compare each pair
        $discrepancies = [];
        $totalPairs = $allKeys->count();
        $matchingPairs = 0;

        foreach ($allKeys as $key) {
            [$locId, $prodId, $cond] = explode('_', $key);
            $locId = (int) $locId;
            $prodId = (int) $prodId;

            $bal = $balances->get($key);
            $mov = $movementAggregates->get($key);

            $currentQty = $bal ? (string) $bal->quantity : '0.0000';
            $expectedQty = $mov ? (string) $mov->expected_quantity : '0.0000';

            // Compare using bcmath
            $delta = bcsub($currentQty, $expectedQty, 4);

            if (bccomp($delta, '0.0000', 4) === 0) {
                $matchingPairs++;
                continue;
            }

            $prod = $products->get($prodId);
            $loc = $locations->get($locId);

            $discrepancies[] = [
                'key' => $key,
                'balance_id' => $bal ? $bal->id : null,
                'product_id' => $prodId,
                'product_sku' => $prod?->sku ?? '-',
                'product_name' => $prod?->name ?? '-',
                'unit_name' => $prod?->unit_name ?? 'PIECES',
                'location_id' => $locId,
                'location_code' => $loc?->code ?? '-',
                'location_name' => $loc?->name ?? '-',
                'location_type' => $loc?->type ?? '-',
                'condition' => $cond,
                'current_quantity' => DecimalQuantity::normalize($currentQty),
                'expected_quantity' => DecimalQuantity::normalize($expectedQty),
                'difference' => DecimalQuantity::normalize($delta),
                'movement_count' => $mov ? (int) $mov->movement_count : 0,
                'last_balance_update' => $bal?->updated_at ?? '-',
            ];
        }

        // Sort discrepancies by location_name, product_name
        usort($discrepancies, fn ($a, $b) => strcmp($a['location_name'], $b['location_name']) ?: strcmp($a['product_name'], $b['product_name']));

        return [
            'summary' => [
                'total_scanned_pairs' => $totalPairs,
                'matching_pairs' => $matchingPairs,
                'discrepant_pairs' => count($discrepancies),
                'status' => count($discrepancies) === 0 ? 'SYNCED' : 'DISCREPANCY_FOUND',
                'scanned_at' => CarbonImmutable::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ],
            'discrepancies' => $discrepancies,
        ];
    }

    /**
     * Reconcile specific or all discrepancies by updating inventory_balances
     * to match the calculated expected ledger quantity.
     *
     * @param  array  $keysToReconcile  Optional array of "locationId_productId_condition" keys. If empty, reconciles all detected discrepancies.
     */
    public function reconcileBalances(array $keysToReconcile = [], int $userId = 1): array
    {
        $scan = $this->scanDiscrepancies();
        $discrepancies = $scan['discrepancies'];

        if (! empty($keysToReconcile)) {
            $allowedKeys = array_flip($keysToReconcile);
            $discrepancies = array_filter($discrepancies, fn ($d) => isset($allowedKeys[$d['key']]));
        }

        if (empty($discrepancies)) {
            return [
                'reconciled_count' => 0,
                'message' => 'Tidak ada selisih saldo yang perlu disinkronkan.',
                'details' => [],
            ];
        }

        // Sort items by location_id and product_id to prevent database deadlocks
        usort($discrepancies, function ($a, $b) {
            return ($a['location_id'] <=> $b['location_id']) ?: ($a['product_id'] <=> $b['product_id']);
        });

        $reconciledDetails = [];

        DB::transaction(function () use ($discrepancies, &$reconciledDetails, $userId) {
            $now = now();

            foreach ($discrepancies as $item) {
                $locationId = (int) $item['location_id'];
                $productId = (int) $item['product_id'];
                $condition = StockCondition::tryFrom($item['condition']) ?? StockCondition::GOOD;
                $expectedQty = (string) $item['expected_quantity'];

                // Concurrency-safe row locking
                $balance = $this->balanceRepository->lockBalanceForUpdate($productId, $locationId, $condition);
                $oldQty = (string) $balance->quantity;

                $balance->quantity = $expectedQty;
                $balance->updated_at = $now;
                $balance->save();

                $reconciledDetails[] = [
                    'key' => $item['key'],
                    'product_sku' => $item['product_sku'],
                    'product_name' => $item['product_name'],
                    'location_name' => $item['location_name'],
                    'condition' => $item['condition'],
                    'old_quantity' => $oldQty,
                    'new_quantity' => $expectedQty,
                    'difference' => $item['difference'],
                ];
            }

            Log::info("Inventory balance reconciliation executed by user ID {$userId}", [
                'user_id' => $userId,
                'count' => count($reconciledDetails),
                'details' => $reconciledDetails,
            ]);
        });

        return [
            'reconciled_count' => count($reconciledDetails),
            'message' => 'Berhasil menyinkronkan '.count($reconciledDetails).' item saldo persediaan.',
            'details' => $reconciledDetails,
        ];
    }
}
