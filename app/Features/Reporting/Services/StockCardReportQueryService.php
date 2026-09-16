<?php

namespace App\Features\Reporting\Services;

use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Models\StockReceipt;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Reporting\Repositories\Contracts\ReportingRepositoryInterface;
use App\Features\StoreAllocation\Models\StoreAllocation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class StockCardReportQueryService
{
    public function __construct(
        private readonly ReportingRepositoryInterface $repository
    ) {}

    public function getReport(array $allowedLocationIds, array $filters): array
    {
        $productId = (int) $filters['product_id'];
        $locationId = (int) $filters['location_id'];

        if (! in_array($locationId, $allowedLocationIds, true)) {
            abort(403, 'Akses ke lokasi ini ditolak.');
        }

        $startDate = CarbonImmutable::parse($filters['start_date'], 'Asia/Jakarta')->startOfDay();
        $endDate = CarbonImmutable::parse($filters['end_date'], 'Asia/Jakarta')->startOfDay();

        $startDateTime = $startDate->format('Y-m-d H:i:s');
        $endNextDayDateTime = $endDate->addDay()->format('Y-m-d H:i:s');

        $perPage = (int) ($filters['per_page'] ?? 15);

        $product = Product::with(['unit', 'category'])->findOrFail($productId);
        $location = Location::findOrFail($locationId);

        $openingBalance = $this->repository->getOpeningBalanceForStockCard(
            $productId,
            $locationId,
            $startDateTime
        );

        /** @var \Illuminate\Pagination\LengthAwarePaginator $movementsPaginator */
        $movementsPaginator = $this->repository->getPaginatedStockCardMovements(
            $productId,
            $locationId,
            $startDateTime,
            $endNextDayDateTime,
            $perPage
        );

        $this->enrichMovementContext(collect($movementsPaginator->items()));

        $summary = $this->repository->getStockCardSummary(
            $productId,
            $locationId,
            $startDateTime,
            $endNextDayDateTime,
            $openingBalance
        );

        return [
            'meta' => [
                'product' => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'name' => $product->name,
                    'category_name' => $product->category?->name ?? '-',
                    'unit_name' => $product->unit?->name ?? '-',
                    'unit_price' => (float) ($product->unit_price ?? 0),
                ],
                'location' => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'code' => $location->code,
                ],
                'start_date' => $filters['start_date'],
                'end_date' => $filters['end_date'],
                'date_basis' => 'POSTED_AT',
                'opening_balance' => $summary['opening_balance'],
                'closing_balance' => $summary['closing_balance'],
                'total_quantity_in' => $summary['total_quantity_in'],
                'total_quantity_out' => $summary['total_quantity_out'],
                'movement_count' => $summary['movement_count'],
                'summary' => [
                    'opening_balance' => $summary['opening_balance'],
                    'closing_balance' => $summary['closing_balance'],
                    'total_quantity_in' => $summary['total_quantity_in'],
                    'total_quantity_out' => $summary['total_quantity_out'],
                    'movement_count' => $summary['movement_count'],
                ],
            ],
            'movements' => $movementsPaginator,
        ];
    }

    private function enrichMovementContext(Collection $movements): void
    {
        if ($movements->isEmpty()) {
            return;
        }

        $transferIds = $movements
            ->filter(fn ($m) => in_array($m->movement_type, [
                MovementType::TRANSFER_IN->value,
                MovementType::TRANSFER_OUT->value,
                MovementType::RETURN_TO_WAREHOUSE->value,
            ], true) || (is_string($m->reference_type) && str_contains($m->reference_type, 'StockTransfer')))
            ->pluck('reference_id')
            ->filter()
            ->unique()
            ->values();

        $transfers = $transferIds->isNotEmpty()
            ? StockTransfer::with(['originLocation', 'destinationLocation'])
                ->whereIn('id', $transferIds)
                ->get()
                ->keyBy('id')
            : collect();

        $allocationIds = $movements
            ->filter(fn ($m) => in_array($m->movement_type, [
                MovementType::STORE_ALLOCATION->value,
                MovementType::REPLACEMENT_PULL->value,
            ], true) || (is_string($m->reference_type) && str_contains($m->reference_type, 'StoreAllocation')))
            ->pluck('reference_id')
            ->filter()
            ->unique()
            ->values();

        $allocations = $allocationIds->isNotEmpty()
            ? StoreAllocation::with('store')
                ->whereIn('id', $allocationIds)
                ->get()
                ->keyBy('id')
            : collect();

        $receiptIds = $movements
            ->filter(fn ($m) => in_array($m->movement_type, [
                MovementType::RECEIPT->value,
                MovementType::RECEIPT_GA->value,
            ], true) || (is_string($m->reference_type) && str_contains($m->reference_type, 'StockReceipt')))
            ->pluck('reference_id')
            ->filter()
            ->unique()
            ->values();

        $receipts = $receiptIds->isNotEmpty()
            ? StockReceipt::with('supplier')
                ->whereIn('id', $receiptIds)
                ->get()
                ->keyBy('id')
            : collect();

        $issueIds = $movements
            ->filter(fn ($m) => $m->movement_type === MovementType::ISSUE->value
                || (is_string($m->reference_type) && str_contains($m->reference_type, 'StockIssue')))
            ->pluck('reference_id')
            ->filter()
            ->unique()
            ->values();

        $issues = $issueIds->isNotEmpty()
            ? StockIssue::with('department')
                ->whereIn('id', $issueIds)
                ->get()
                ->keyBy('id')
            : collect();

        foreach ($movements as $movement) {
            $counterpartLocation = null;
            $counterpartLabel = null;
            $storeId = null;
            $storeCode = null;
            $storeName = null;

            if ($movement->movement_type === MovementType::TRANSFER_IN->value) {
                $transfer = $transfers->get((int) $movement->reference_id);
                if ($transfer) {
                    $counterpartLocation = $transfer->originLocation;
                    $counterpartLabel = 'Dari: '.($transfer->originLocation?->name ?? '-');
                }
            } elseif ($movement->movement_type === MovementType::TRANSFER_OUT->value || $movement->movement_type === MovementType::RETURN_TO_WAREHOUSE->value) {
                $transfer = $transfers->get((int) $movement->reference_id);
                if ($transfer) {
                    $counterpartLocation = $transfer->destinationLocation;
                    $counterpartLabel = 'Ke: '.($transfer->destinationLocation?->name ?? '-');
                }
            } elseif (
                in_array($movement->movement_type, [
                    MovementType::STORE_ALLOCATION->value,
                    MovementType::REPLACEMENT_PULL->value,
                ], true)
                || (is_string($movement->reference_type) && str_contains($movement->reference_type, 'StoreAllocation'))
            ) {
                $allocation = $allocations->get((int) $movement->reference_id);
                if ($allocation && $allocation->store) {
                    $store = $allocation->store;
                    $storeId = $store->id;
                    $storeCode = $store->code;
                    $storeName = $store->name;
                    $storeLabel = $storeCode ? "{$storeCode} - {$storeName}" : $storeName;
                    $counterpartLabel = 'Toko: '.$storeLabel;
                }
            } elseif (
                in_array($movement->movement_type, [
                    MovementType::RECEIPT->value,
                    MovementType::RECEIPT_GA->value,
                ], true)
                || (is_string($movement->reference_type) && str_contains($movement->reference_type, 'StockReceipt'))
            ) {
                $receipt = $receipts->get((int) $movement->reference_id);
                if ($receipt && $receipt->supplier) {
                    $supplier = $receipt->supplier;
                    $supplierLabel = ($supplier->code && $supplier->code !== $supplier->name)
                        ? "{$supplier->code} - {$supplier->name}"
                        : $supplier->name;
                    $counterpartLabel = 'Supplier: '.$supplierLabel;
                }
            } elseif (
                $movement->movement_type === MovementType::ISSUE->value
                || (is_string($movement->reference_type) && str_contains($movement->reference_type, 'StockIssue'))
            ) {
                $issue = $issues->get((int) $movement->reference_id);
                if ($issue) {
                    if ($issue->department) {
                        $dept = $issue->department;
                        $deptLabel = ($dept->code && $dept->code !== $dept->name)
                            ? "{$dept->code} - {$dept->name}"
                            : $dept->name;
                        $counterpartLabel = 'Dept: '.$deptLabel;
                    } elseif ($issue->purpose) {
                        $counterpartLabel = 'Tujuan: '.$issue->purpose;
                    }
                }
            }

            if ($counterpartLocation) {
                $movement->setAttribute('counterpart_location_name', $counterpartLocation->name);
                $movement->setAttribute('counterpart_location_code', $counterpartLocation->code);
            } else {
                $movement->setAttribute('counterpart_location_name', null);
                $movement->setAttribute('counterpart_location_code', null);
            }
            $movement->setAttribute('counterpart_label', $counterpartLabel);
            $movement->setAttribute('store_id', $storeId);
            $movement->setAttribute('store_code', $storeCode);
            $movement->setAttribute('store_name', $storeName);
        }
    }
}
