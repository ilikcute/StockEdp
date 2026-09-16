<?php

namespace App\Features\Reporting\Repositories\Eloquent;

use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockAdjustmentItem;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Models\StockIssueItem;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Inventory\Models\StockOpnameItem;
use App\Features\Inventory\Models\StockReceipt;
use App\Features\Inventory\Models\StockReceiptItem;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Models\StockTransferItem;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Reporting\Helpers\DecimalQuantity;
use App\Features\Reporting\Queries\LowStockQuery;
use App\Features\Reporting\Repositories\Contracts\ReportingRepositoryInterface;
use App\Features\Supplier\Models\Supplier;
use App\Features\Unit\Models\Unit;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class ReportingRepository implements ReportingRepositoryInterface
{
    public function getBaseLocations(array $allowedLocationIds): Collection
    {
        return Location::query()
            ->when(! empty($allowedLocationIds), fn ($q) => $q->whereIn('id', $allowedLocationIds))
            ->when(empty($allowedLocationIds), fn ($q) => $q->whereRaw('1 = 0'))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    public function getActiveCategories(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getActiveUnits(): Collection
    {
        return Unit::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'symbol']);
    }

    public function searchProductOptions(?string $search, int $perPage = 20): Collection
    {
        $search = trim((string) $search);

        return Product::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit($perPage)
            ->get(['id', 'name', 'sku', 'barcode']);
    }

    public function searchSupplierOptions(?string $search, int $perPage = 20): Collection
    {
        $search = trim((string) $search);

        return Supplier::query()
            ->where('is_active', true)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit($perPage)
            ->get(['id', 'name', 'code']);
    }

    public function getPaginatedBalances(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'id',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        if (empty($allowedLocationIds)) {
            return new ConcretePaginator([], 0, $perPage, 1);
        }

        $query = InventoryBalance::with(['product.category', 'product.unit', 'location.operationLock']);

        // Scope to allowed locations
        $query->whereIn('location_id', $allowedLocationIds);

        // Filter location_id if requested (must be within allowed locations)
        if (! empty($filters['location_id'])) {
            if (! in_array((int) $filters['location_id'], $allowedLocationIds, true)) {
                return new ConcretePaginator([], 0, $perPage, 1);
            }
            $query->where('location_id', $filters['location_id']);
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        if (! empty($filters['unit_id'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('unit_id', $filters['unit_id']);
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $isActive = (bool) $filters['is_active'];
            $query->whereHas('product', function ($q) use ($isActive) {
                $q->where('is_active', $isActive);
            });
        }

        if (! empty($filters['positive_stock'])) {
            $query->where('quantity', '>', '0.0000');
        }

        if (isset($filters['zero_stock']) && $filters['zero_stock'] === '1') {
            $query->where('quantity', '=', '0.0000');
        }

        if (isset($filters['frozen_location']) && $filters['frozen_location'] === '1') {
            $query->whereHas('location.operationLock', function ($q) {
                $q->where('is_frozen', true);
            });
        }

        if (! empty($filters['condition'])) {
            $query->where('condition', $filters['condition']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $allowlist = ['id', 'product_id', 'location_id', 'quantity', 'created_at'];
        $sortField = in_array($sortField, $allowlist, true) ? $sortField : 'id';
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $sortDirection)->paginate($perPage);
    }

    public function getPaginatedLowStock(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'shortage_quantity',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $locationId = isset($filters['location_id']) ? (int) $filters['location_id'] : 0;

        if ($locationId === 0 || ! in_array($locationId, $allowedLocationIds, true)) {
            return new ConcretePaginator([], 0, $perPage, 1);
        }

        $query = LowStockQuery::forLocation($locationId, $filters);

        $allowlist = ['shortage_quantity', 'minimum_stock', 'on_hand_quantity', 'product_name', 'sku'];
        $sortField = in_array($sortField, $allowlist, true) ? $sortField : 'shortage_quantity';
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $sortDirection)->paginate($perPage);
    }

    public function getOpeningBalanceForStockCard(
        int $productId,
        int $locationId,
        string $startDateTime
    ): string {
        $movement = StockMovement::where('product_id', $productId)
            ->where('location_id', $locationId)
            ->where('created_at', '<', $startDateTime)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $movement ? (string) $movement->quantity_after : '0.0000';
    }

    public function getPaginatedStockCardMovements(
        int $productId,
        int $locationId,
        string $startDateTime,
        string $endNextDayDateTime,
        int $perPage = 15
    ): LengthAwarePaginator {
        return StockMovement::with(['product.unit', 'location', 'creator'])
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->where('created_at', '>=', $startDateTime)
            ->where('created_at', '<', $endNextDayDateTime)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage);
    }

    public function getStockCardSummary(
        int $productId,
        int $locationId,
        string $startDateTime,
        string $endNextDayDateTime,
        string $openingBalance
    ): array {
        $movements = StockMovement::where('product_id', $productId)
            ->where('location_id', $locationId)
            ->where('created_at', '>=', $startDateTime)
            ->where('created_at', '<', $endNextDayDateTime)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $totalIn = '0.0000';
        $totalOut = '0.0000';

        foreach ($movements as $m) {
            $delta = bcsub((string) $m->quantity_after, (string) $m->quantity_before, 4);
            if (bccomp($delta, '0.0000', 4) > 0) {
                $totalIn = bcadd($totalIn, $delta, 4);
            } elseif (bccomp($delta, '0.0000', 4) < 0) {
                $positiveDelta = bcsub('0.0000', $delta, 4);
                $totalOut = bcadd($totalOut, $positiveDelta, 4);
            }
        }

        $closingBalance = $movements->isNotEmpty()
            ? (string) $movements->last()->quantity_after
            : $openingBalance;

        return [
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'total_quantity_in' => $totalIn,
            'total_quantity_out' => $totalOut,
            'movement_count' => $movements->count(),
        ];
    }

    // ==========================================
    // 1. STOCK RECEIPT REPORT
    // ==========================================
    public function getPaginatedStockReceiptReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        if (empty($allowedLocationIds)) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        $query = StockReceiptItem::query()
            ->select('stock_receipt_items.*', 'stock_movements.created_at as movement_posted_at')
            ->join('stock_receipts', 'stock_receipts.id', '=', 'stock_receipt_items.stock_receipt_id')
            ->join('products', 'products.id', '=', 'stock_receipt_items.product_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'stock_receipts.supplier_id')
            ->leftJoin('stock_movements', function ($join) {
                $join->on('stock_movements.reference_id', '=', 'stock_receipts.id')
                    ->where('stock_movements.reference_type', '=', StockReceipt::class)
                    ->on('stock_movements.product_id', '=', 'stock_receipt_items.product_id')
                    ->on('stock_movements.location_id', '=', 'stock_receipt_items.location_id')
                    ->whereIn('stock_movements.movement_type', [
                        MovementType::RECEIPT->value,
                        MovementType::RECEIPT_GA->value,
                    ]);
            })
            ->where('stock_receipts.status', 'POSTED')
            ->whereIn('stock_receipt_items.location_id', $allowedLocationIds);

        $this->applyReceiptFilters($query, $filters);

        $query->orderBy('stock_movements.created_at', 'desc')
            ->orderBy('stock_movements.id', 'desc')
            ->orderBy('stock_receipt_items.id', 'desc');

        return $query->with(['receipt.supplier', 'receipt.creator', 'product.unit', 'product.category', 'location'])
            ->paginate($perPage);
    }

    public function getStockReceiptReportSummary(array $allowedLocationIds, array $filters): array
    {
        if (empty($allowedLocationIds)) {
            return [
                'total_rows' => 0,
                'total_documents' => 0,
                'quantity_by_unit' => [],
            ];
        }

        $baseQuery = StockReceiptItem::query()
            ->join('stock_receipts', 'stock_receipts.id', '=', 'stock_receipt_items.stock_receipt_id')
            ->join('products', 'products.id', '=', 'stock_receipt_items.product_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'stock_receipts.supplier_id')
            ->leftJoin('stock_movements', function ($join) {
                $join->on('stock_movements.reference_id', '=', 'stock_receipts.id')
                    ->where('stock_movements.reference_type', '=', StockReceipt::class)
                    ->on('stock_movements.product_id', '=', 'stock_receipt_items.product_id')
                    ->on('stock_movements.location_id', '=', 'stock_receipt_items.location_id')
                    ->whereIn('stock_movements.movement_type', [
                        MovementType::RECEIPT->value,
                        MovementType::RECEIPT_GA->value,
                    ]);
            })
            ->where('stock_receipts.status', 'POSTED')
            ->whereIn('stock_receipt_items.location_id', $allowedLocationIds);

        $this->applyReceiptFilters($baseQuery, $filters);

        $totalRows = (clone $baseQuery)->count();
        $totalDocuments = (clone $baseQuery)->distinct()->count('stock_receipts.id');

        $quantityByUnit = (clone $baseQuery)
            ->join('units', 'units.id', '=', 'products.unit_id')
            ->select(
                'units.id as unit_id',
                'units.code as unit_code',
                'units.name as unit_name',
                DB::raw('SUM(stock_receipt_items.quantity) as total_quantity')
            )
            ->groupBy('units.id', 'units.code', 'units.name')
            ->get()
            ->map(fn ($r) => [
                'unit_id' => $r->unit_id,
                'unit_code' => $r->unit_code,
                'unit_name' => $r->unit_name,
                'total_quantity' => DecimalQuantity::normalize($r->total_quantity),
            ])->toArray();

        return [
            'total_rows' => $totalRows,
            'total_documents' => $totalDocuments,
            'quantity_by_unit' => $quantityByUnit,
        ];
    }

    private function applyReceiptFilters($query, array $filters): void
    {
        if (! empty($filters['supplier_id'])) {
            $query->where('stock_receipts.supplier_id', $filters['supplier_id']);
        }
        if (! empty($filters['location_id'])) {
            $query->where('stock_receipt_items.location_id', $filters['location_id']);
        }
        if (! empty($filters['product_id'])) {
            $query->where('stock_receipt_items.product_id', $filters['product_id']);
        }
        if (! empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }
        if (! empty($filters['unit_id'])) {
            $query->where('products.unit_id', $filters['unit_id']);
        }
        if (! empty($filters['start_date'])) {
            $start = CarbonImmutable::parse($filters['start_date'], 'Asia/Jakarta')->startOfDay()->format('Y-m-d H:i:s');
            $query->where('stock_movements.created_at', '>=', $start);
        }
        if (! empty($filters['end_date'])) {
            $endNext = CarbonImmutable::parse($filters['end_date'], 'Asia/Jakarta')->startOfDay()->addDay()->format('Y-m-d H:i:s');
            $query->where('stock_movements.created_at', '<', $endNext);
        }
        if (! empty($filters['search'])) {
            $search = addcslashes($filters['search'], '%_');
            $query->where(function ($q) use ($search) {
                $q->where('stock_receipts.receipt_number', 'like', "%{$search}%")
                    ->orWhere('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('suppliers.name', 'like', "%{$search}%");
            });
        }
    }

    // ==========================================
    // 2. STOCK ISSUE REPORT
    // ==========================================
    public function getPaginatedStockIssueReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        if (empty($allowedLocationIds)) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        $query = StockIssueItem::query()
            ->select('stock_issue_items.*', 'stock_movements.created_at as movement_posted_at')
            ->join('stock_issues', 'stock_issues.id', '=', 'stock_issue_items.stock_issue_id')
            ->join('products', 'products.id', '=', 'stock_issue_items.product_id')
            ->leftJoin('stock_movements', function ($join) {
                $join->on('stock_movements.reference_id', '=', 'stock_issues.id')
                    ->where('stock_movements.reference_type', '=', StockIssue::class)
                    ->on('stock_movements.product_id', '=', 'stock_issue_items.product_id')
                    ->on('stock_movements.location_id', '=', 'stock_issue_items.location_id')
                    ->where('stock_movements.movement_type', '=', 'ISSUE');
            })
            ->where('stock_issues.status', 'POSTED')
            ->whereIn('stock_issue_items.location_id', $allowedLocationIds);

        $this->applyIssueFilters($query, $filters);

        $query->orderBy('stock_movements.created_at', 'desc')
            ->orderBy('stock_movements.id', 'desc')
            ->orderBy('stock_issue_items.id', 'desc');

        return $query->with(['issue.creator', 'product.unit', 'product.category', 'location'])
            ->paginate($perPage);
    }

    public function getStockIssueReportSummary(array $allowedLocationIds, array $filters): array
    {
        if (empty($allowedLocationIds)) {
            return [
                'total_rows' => 0,
                'total_documents' => 0,
                'quantity_by_unit' => [],
            ];
        }

        $baseQuery = StockIssueItem::query()
            ->join('stock_issues', 'stock_issues.id', '=', 'stock_issue_items.stock_issue_id')
            ->join('products', 'products.id', '=', 'stock_issue_items.product_id')
            ->leftJoin('stock_movements', function ($join) {
                $join->on('stock_movements.reference_id', '=', 'stock_issues.id')
                    ->where('stock_movements.reference_type', '=', StockIssue::class)
                    ->on('stock_movements.product_id', '=', 'stock_issue_items.product_id')
                    ->on('stock_movements.location_id', '=', 'stock_issue_items.location_id')
                    ->where('stock_movements.movement_type', '=', 'ISSUE');
            })
            ->where('stock_issues.status', 'POSTED')
            ->whereIn('stock_issue_items.location_id', $allowedLocationIds);

        $this->applyIssueFilters($baseQuery, $filters);

        $totalRows = (clone $baseQuery)->count();
        $totalDocuments = (clone $baseQuery)->distinct()->count('stock_issues.id');

        $quantityByUnit = (clone $baseQuery)
            ->join('units', 'units.id', '=', 'products.unit_id')
            ->select(
                'units.id as unit_id',
                'units.code as unit_code',
                'units.name as unit_name',
                DB::raw('SUM(stock_issue_items.quantity) as total_quantity')
            )
            ->groupBy('units.id', 'units.code', 'units.name')
            ->get()
            ->map(fn ($r) => [
                'unit_id' => $r->unit_id,
                'unit_code' => $r->unit_code,
                'unit_name' => $r->unit_name,
                'total_quantity' => DecimalQuantity::normalize($r->total_quantity),
            ])->toArray();

        return [
            'total_rows' => $totalRows,
            'total_documents' => $totalDocuments,
            'quantity_by_unit' => $quantityByUnit,
        ];
    }

    private function applyIssueFilters($query, array $filters): void
    {
        if (! empty($filters['location_id'])) {
            $query->where('stock_issue_items.location_id', $filters['location_id']);
        }
        if (! empty($filters['product_id'])) {
            $query->where('stock_issue_items.product_id', $filters['product_id']);
        }
        if (! empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }
        if (! empty($filters['unit_id'])) {
            $query->where('products.unit_id', $filters['unit_id']);
        }
        if (! empty($filters['start_date'])) {
            $start = CarbonImmutable::parse($filters['start_date'], 'Asia/Jakarta')->startOfDay()->format('Y-m-d H:i:s');
            $query->where('stock_movements.created_at', '>=', $start);
        }
        if (! empty($filters['end_date'])) {
            $endNext = CarbonImmutable::parse($filters['end_date'], 'Asia/Jakarta')->startOfDay()->addDay()->format('Y-m-d H:i:s');
            $query->where('stock_movements.created_at', '<', $endNext);
        }
        if (! empty($filters['search'])) {
            $search = addcslashes($filters['search'], '%_');
            $query->where(function ($q) use ($search) {
                $q->where('stock_issues.issue_number', 'like', "%{$search}%")
                    ->orWhere('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('stock_issues.purpose', 'like', "%{$search}%")
                    ->orWhere('stock_issues.notes', 'like', "%{$search}%");
            });
        }
    }

    // ==========================================
    // 3. STOCK TRANSFER REPORT
    // ==========================================
    public function getPaginatedStockTransferReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'sent_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        if (empty($allowedLocationIds)) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        $dateBasis = strtoupper($filters['date_basis'] ?? 'SENT_AT');
        $dateColumn = ($dateBasis === 'RECEIVED_AT') ? 'stock_transfers.received_at' : 'stock_transfers.sent_at';

        $query = StockTransferItem::query()
            ->select('stock_transfer_items.*')
            ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
            ->join('products', 'products.id', '=', 'stock_transfer_items.product_id')
            ->where(function ($q) use ($allowedLocationIds) {
                $q->whereIn('stock_transfers.origin_location_id', $allowedLocationIds)
                    ->orWhereIn('stock_transfers.destination_location_id', $allowedLocationIds);
            });

        if ($dateBasis === 'RECEIVED_AT') {
            $query->where('stock_transfers.status', 'RECEIVED');
        } else {
            $query->whereIn('stock_transfers.status', ['IN_TRANSIT', 'RECEIVED']);
        }

        $this->applyTransferFilters($query, $filters, $dateColumn);

        $sortMap = [
            'sent_at' => 'stock_transfers.sent_at',
            'received_at' => 'stock_transfers.received_at',
            'transfer_number' => 'stock_transfers.transfer_number',
            'id' => 'stock_transfer_items.id',
        ];
        $actualSortField = $sortMap[$sortField] ?? $dateColumn;
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($actualSortField, $sortDirection)
            ->orderBy('stock_transfer_items.id', $sortDirection);

        return $query->with(['transfer.originLocation', 'transfer.destinationLocation', 'transfer.sender', 'transfer.receiver', 'product.unit', 'product.category'])
            ->paginate($perPage);
    }

    public function getStockTransferReportSummary(array $allowedLocationIds, array $filters): array
    {
        if (empty($allowedLocationIds)) {
            return [
                'total_rows' => 0,
                'total_documents' => 0,
                'status_counts' => ['IN_TRANSIT' => 0, 'RECEIVED' => 0],
                'in_transit_document_count' => 0,
                'in_transit_item_count' => 0,
                'quantity_by_unit' => [],
            ];
        }

        $dateBasis = strtoupper($filters['date_basis'] ?? 'SENT_AT');
        $dateColumn = ($dateBasis === 'RECEIVED_AT') ? 'stock_transfers.received_at' : 'stock_transfers.sent_at';

        $baseQuery = StockTransferItem::query()
            ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
            ->join('products', 'products.id', '=', 'stock_transfer_items.product_id')
            ->where(function ($q) use ($allowedLocationIds) {
                $q->whereIn('stock_transfers.origin_location_id', $allowedLocationIds)
                    ->orWhereIn('stock_transfers.destination_location_id', $allowedLocationIds);
            });

        if ($dateBasis === 'RECEIVED_AT') {
            $baseQuery->where('stock_transfers.status', 'RECEIVED');
        } else {
            $baseQuery->whereIn('stock_transfers.status', ['IN_TRANSIT', 'RECEIVED']);
        }

        $this->applyTransferFilters($baseQuery, $filters, $dateColumn);

        $totalRows = (clone $baseQuery)->count();
        $totalDocuments = (clone $baseQuery)->distinct()->count('stock_transfers.id');

        $sentCount = (clone $baseQuery)->where('stock_transfers.status', 'IN_TRANSIT')->count();
        $receivedCount = (clone $baseQuery)->where('stock_transfers.status', 'RECEIVED')->count();

        $inTransitItemCount = (clone $baseQuery)->where('stock_transfers.status', 'IN_TRANSIT')->count();
        $inTransitDocCount = (clone $baseQuery)->where('stock_transfers.status', 'IN_TRANSIT')->distinct()->count('stock_transfers.id');

        $quantityByUnit = (clone $baseQuery)
            ->join('units', 'units.id', '=', 'products.unit_id')
            ->select(
                'units.id as unit_id',
                'units.code as unit_code',
                'units.name as unit_name',
                DB::raw('SUM(stock_transfer_items.quantity) as total_quantity')
            )
            ->groupBy('units.id', 'units.code', 'units.name')
            ->get()
            ->map(fn ($r) => [
                'unit_id' => $r->unit_id,
                'unit_code' => $r->unit_code,
                'unit_name' => $r->unit_name,
                'total_quantity' => DecimalQuantity::normalize($r->total_quantity),
            ])->toArray();

        return [
            'total_rows' => $totalRows,
            'total_documents' => $totalDocuments,
            'status_counts' => [
                'IN_TRANSIT' => $sentCount,
                'RECEIVED' => $receivedCount,
            ],
            'in_transit_document_count' => $inTransitDocCount,
            'in_transit_item_count' => $inTransitItemCount,
            'quantity_by_unit' => $quantityByUnit,
        ];
    }

    private function applyTransferFilters($query, array $filters, string $dateColumn): void
    {
        if (! empty($filters['status'])) {
            $query->where('stock_transfers.status', $filters['status']);
        }
        if (! empty($filters['origin_location_id'])) {
            $query->where('stock_transfers.origin_location_id', $filters['origin_location_id']);
        }
        if (! empty($filters['destination_location_id'])) {
            $query->where('stock_transfers.destination_location_id', $filters['destination_location_id']);
        }
        if (! empty($filters['product_id'])) {
            $query->where('stock_transfer_items.product_id', $filters['product_id']);
        }
        if (! empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }
        if (! empty($filters['unit_id'])) {
            $query->where('products.unit_id', $filters['unit_id']);
        }
        if (! empty($filters['start_date'])) {
            $start = CarbonImmutable::parse($filters['start_date'], 'Asia/Jakarta')->startOfDay()->format('Y-m-d H:i:s');
            $query->where($dateColumn, '>=', $start);
        }
        if (! empty($filters['end_date'])) {
            $endNext = CarbonImmutable::parse($filters['end_date'], 'Asia/Jakarta')->startOfDay()->addDay()->format('Y-m-d H:i:s');
            $query->where($dateColumn, '<', $endNext);
        }
        if (! empty($filters['search'])) {
            $search = addcslashes($filters['search'], '%_');
            $query->where(function ($q) use ($search) {
                $q->where('stock_transfers.transfer_number', 'like', "%{$search}%")
                    ->orWhere('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%");
            });
        }
    }

    // ==========================================
    // 4. STOCK ADJUSTMENT REPORT
    // ==========================================
    public function getPaginatedStockAdjustmentReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        if (empty($allowedLocationIds)) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        $query = StockAdjustmentItem::query()
            ->select('stock_adjustment_items.*')
            ->join('stock_adjustments', 'stock_adjustments.id', '=', 'stock_adjustment_items.stock_adjustment_id')
            ->join('products', 'products.id', '=', 'stock_adjustment_items.product_id')
            ->where('stock_adjustments.status', 'POSTED')
            ->whereIn('stock_adjustments.location_id', $allowedLocationIds);

        $this->applyAdjustmentFilters($query, $filters);

        $query->orderBy('stock_adjustments.posted_at', 'desc')
            ->orderBy('stock_adjustments.id', 'desc')
            ->orderBy('stock_adjustment_items.id', 'desc');

        return $query->with(['adjustment.location', 'adjustment.creator', 'adjustment.poster', 'product.unit', 'product.category'])
            ->paginate($perPage);
    }

    public function getStockAdjustmentReportSummary(array $allowedLocationIds, array $filters): array
    {
        if (empty($allowedLocationIds)) {
            return [
                'total_rows' => 0,
                'total_documents' => 0,
                'quantity_by_unit' => [],
            ];
        }

        $baseQuery = StockAdjustmentItem::query()
            ->join('stock_adjustments', 'stock_adjustments.id', '=', 'stock_adjustment_items.stock_adjustment_id')
            ->join('products', 'products.id', '=', 'stock_adjustment_items.product_id')
            ->where('stock_adjustments.status', 'POSTED')
            ->whereIn('stock_adjustments.location_id', $allowedLocationIds);

        $this->applyAdjustmentFilters($baseQuery, $filters);

        $totalRows = (clone $baseQuery)->count();
        $totalDocuments = (clone $baseQuery)->distinct()->count('stock_adjustments.id');

        $quantityByUnit = (clone $baseQuery)
            ->join('units', 'units.id', '=', 'products.unit_id')
            ->select(
                'units.id as unit_id',
                'units.code as unit_code',
                'units.name as unit_name',
                DB::raw('SUM(stock_adjustment_items.quantity) as total_quantity')
            )
            ->groupBy('units.id', 'units.code', 'units.name')
            ->get()
            ->map(fn ($r) => [
                'unit_id' => $r->unit_id,
                'unit_code' => $r->unit_code,
                'unit_name' => $r->unit_name,
                'total_quantity' => DecimalQuantity::normalize($r->total_quantity),
            ])->toArray();

        $amounts = (clone $baseQuery)
            ->selectRaw("
                COALESCE(SUM(stock_adjustment_items.quantity * COALESCE(products.unit_price, 0)), 0) as total_gross_amount,
                COALESCE(SUM(CASE WHEN stock_adjustments.direction = 'INCREASE' THEN stock_adjustment_items.quantity * COALESCE(products.unit_price, 0) ELSE 0 END), 0) as total_increase_amount,
                COALESCE(SUM(CASE WHEN stock_adjustments.direction = 'DECREASE' THEN stock_adjustment_items.quantity * COALESCE(products.unit_price, 0) ELSE 0 END), 0) as total_decrease_amount
            ")
            ->first();

        $totalGrossAmount = (float) ($amounts->total_gross_amount ?? 0);
        $totalIncreaseAmount = (float) ($amounts->total_increase_amount ?? 0);
        $totalDecreaseAmount = (float) ($amounts->total_decrease_amount ?? 0);
        $netAmount = $totalIncreaseAmount - $totalDecreaseAmount;

        return [
            'total_rows' => $totalRows,
            'total_documents' => $totalDocuments,
            'total_amount' => $totalGrossAmount,
            'total_increase_amount' => $totalIncreaseAmount,
            'total_decrease_amount' => $totalDecreaseAmount,
            'net_amount' => $netAmount,
            'quantity_by_unit' => $quantityByUnit,
        ];
    }

    private function applyAdjustmentFilters($query, array $filters): void
    {
        if (! empty($filters['location_id'])) {
            $query->where('stock_adjustments.location_id', $filters['location_id']);
        }
        if (! empty($filters['product_id'])) {
            $query->where('stock_adjustment_items.product_id', $filters['product_id']);
        }
        if (! empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }
        if (! empty($filters['unit_id'])) {
            $query->where('products.unit_id', $filters['unit_id']);
        }
        if (! empty($filters['direction'])) {
            $query->where('stock_adjustments.direction', $filters['direction']);
        }
        if (! empty($filters['reason_code'])) {
            $query->where('stock_adjustments.reason_code', $filters['reason_code']);
        }
        if (! empty($filters['start_date'])) {
            $start = CarbonImmutable::parse($filters['start_date'], 'Asia/Jakarta')->startOfDay()->format('Y-m-d H:i:s');
            $query->where('stock_adjustments.posted_at', '>=', $start);
        }
        if (! empty($filters['end_date'])) {
            $endNext = CarbonImmutable::parse($filters['end_date'], 'Asia/Jakarta')->startOfDay()->addDay()->format('Y-m-d H:i:s');
            $query->where('stock_adjustments.posted_at', '<', $endNext);
        }
        if (! empty($filters['search'])) {
            $search = addcslashes($filters['search'], '%_');
            $query->where(function ($q) use ($search) {
                $q->where('stock_adjustments.adjustment_number', 'like', "%{$search}%")
                    ->orWhere('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('stock_adjustments.notes', 'like', "%{$search}%");
            });
        }
    }

    // ==========================================
    // 5. STOCK OPNAME REPORT
    // ==========================================
    public function getPaginatedStockOpnameReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        if (empty($allowedLocationIds)) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        $query = StockOpnameItem::query()
            ->select('stock_opname_items.*')
            ->join('stock_opnames', 'stock_opnames.id', '=', 'stock_opname_items.stock_opname_id')
            ->join('products', 'products.id', '=', 'stock_opname_items.product_id')
            ->where('stock_opnames.status', 'POSTED')
            ->whereIn('stock_opnames.location_id', $allowedLocationIds);

        $this->applyOpnameFilters($query, $filters);

        $query->orderBy('stock_opnames.posted_at', 'desc')
            ->orderBy('stock_opnames.id', 'desc')
            ->orderBy('stock_opname_items.id', 'desc');

        return $query->with(['opname.location', 'opname.creator', 'opname.completer', 'opname.poster', 'counter', 'product.unit', 'product.category'])
            ->paginate($perPage);
    }

    public function getStockOpnameReportSummary(array $allowedLocationIds, array $filters): array
    {
        if (empty($allowedLocationIds)) {
            return [
                'total_rows' => 0,
                'total_documents' => 0,
                'positive_item_count' => 0,
                'negative_item_count' => 0,
                'zero_item_count' => 0,
                'quantity_by_unit' => [],
            ];
        }

        $baseQuery = StockOpnameItem::query()
            ->join('stock_opnames', 'stock_opnames.id', '=', 'stock_opname_items.stock_opname_id')
            ->join('products', 'products.id', '=', 'stock_opname_items.product_id')
            ->where('stock_opnames.status', 'POSTED')
            ->whereIn('stock_opnames.location_id', $allowedLocationIds);

        $this->applyOpnameFilters($baseQuery, $filters);

        $totalRows = (clone $baseQuery)->count();
        $totalDocuments = (clone $baseQuery)->distinct()->count('stock_opnames.id');

        $positiveCount = (clone $baseQuery)->where('stock_opname_items.variance_quantity', '>', 0)->count();
        $negativeCount = (clone $baseQuery)->where('stock_opname_items.variance_quantity', '<', 0)->count();
        $zeroCount = (clone $baseQuery)->where('stock_opname_items.variance_quantity', '=', 0)->count();

        $quantityByUnit = (clone $baseQuery)
            ->join('units', 'units.id', '=', 'products.unit_id')
            ->select(
                'units.id as unit_id',
                'units.code as unit_code',
                'units.name as unit_name',
                DB::raw('SUM(stock_opname_items.counted_quantity) as total_quantity')
            )
            ->groupBy('units.id', 'units.code', 'units.name')
            ->get()
            ->map(fn ($r) => [
                'unit_id' => $r->unit_id,
                'unit_code' => $r->unit_code,
                'unit_name' => $r->unit_name,
                'total_quantity' => DecimalQuantity::normalize($r->total_quantity),
            ])->toArray();

        return [
            'total_rows' => $totalRows,
            'total_documents' => $totalDocuments,
            'positive_item_count' => $positiveCount,
            'negative_item_count' => $negativeCount,
            'zero_item_count' => $zeroCount,
            'quantity_by_unit' => $quantityByUnit,
        ];
    }

    private function applyOpnameFilters($query, array $filters): void
    {
        if (! empty($filters['location_id'])) {
            $query->where('stock_opnames.location_id', $filters['location_id']);
        }
        if (! empty($filters['product_id'])) {
            $query->where('stock_opname_items.product_id', $filters['product_id']);
        }
        if (! empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }
        if (! empty($filters['unit_id'])) {
            $query->where('products.unit_id', $filters['unit_id']);
        }
        if (! empty($filters['variance_direction'])) {
            $dir = strtoupper($filters['variance_direction']);
            if ($dir === 'POSITIVE') {
                $query->where('stock_opname_items.variance_quantity', '>', 0);
            } elseif ($dir === 'NEGATIVE') {
                $query->where('stock_opname_items.variance_quantity', '<', 0);
            } elseif ($dir === 'ZERO') {
                $query->where('stock_opname_items.variance_quantity', '=', 0);
            }
        }
        if (isset($filters['is_unexpected'])) {
            $isUnex = in_array($filters['is_unexpected'], ['1', 1, 'true', true], true);
            $query->where('stock_opname_items.is_unexpected', $isUnex);
        }
        if (! empty($filters['start_date'])) {
            $start = CarbonImmutable::parse($filters['start_date'], 'Asia/Jakarta')->startOfDay()->format('Y-m-d H:i:s');
            $query->where('stock_opnames.posted_at', '>=', $start);
        }
        if (! empty($filters['end_date'])) {
            $endNext = CarbonImmutable::parse($filters['end_date'], 'Asia/Jakarta')->startOfDay()->addDay()->format('Y-m-d H:i:s');
            $query->where('stock_opnames.posted_at', '<', $endNext);
        }
        if (! empty($filters['search'])) {
            $search = addcslashes($filters['search'], '%_');
            $query->where(function ($q) use ($search) {
                $q->where('stock_opnames.opname_number', 'like', "%{$search}%")
                    ->orWhere('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%");
            });
        }
    }

    // ==========================================
    // CURSOR STREAM METHODS FOR CSV EXPORT
    // ==========================================
    public function getCursorBalances(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'id',
        string $sortDirection = 'desc'
    ): LazyCollection {
        if (empty($allowedLocationIds)) {
            return LazyCollection::empty();
        }

        $query = DB::table('inventory_balances')
            ->join('products', 'products.id', '=', 'inventory_balances.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->join('locations', 'locations.id', '=', 'inventory_balances.location_id')
            ->leftJoin('inventory_location_locks', 'inventory_location_locks.location_id', '=', 'locations.id')
            ->select([
                'inventory_balances.id',
                'products.sku',
                'products.name as product_name',
                'categories.name as category_name',
                'units.name as unit_name',
                'locations.code as location_code',
                'locations.name as location_name',
                'inventory_balances.quantity',
                'inventory_balances.condition',
                'products.minimum_stock',
                'products.is_active as is_product_active',
                'inventory_location_locks.is_frozen',
            ])
            ->whereIn('inventory_balances.location_id', $allowedLocationIds);

        if (! empty($filters['condition'])) {
            $query->where('inventory_balances.condition', $filters['condition']);
        }

        if (! empty($filters['location_id'])) {
            if (! in_array((int) $filters['location_id'], $allowedLocationIds, true)) {
                return LazyCollection::empty();
            }
            $query->where('inventory_balances.location_id', $filters['location_id']);
        }
        if (! empty($filters['product_id'])) {
            $query->where('inventory_balances.product_id', $filters['product_id']);
        }
        if (! empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }
        if (! empty($filters['unit_id'])) {
            $query->where('products.unit_id', $filters['unit_id']);
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $isActive = (bool) $filters['is_active'];
            $query->where('products.is_active', $isActive);
        }
        if (! empty($filters['positive_stock'])) {
            $query->where('inventory_balances.quantity', '>', '0.0000');
        }
        if (isset($filters['zero_stock']) && $filters['zero_stock'] === '1') {
            $query->where('inventory_balances.quantity', '=', '0.0000');
        }
        if (isset($filters['frozen_location']) && $filters['frozen_location'] === '1') {
            $query->where('inventory_location_locks.is_frozen', true);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('products.barcode', 'like', "%{$search}%");
            });
        }

        $allowlist = ['id', 'product_id', 'location_id', 'quantity', 'created_at'];
        $sortField = in_array($sortField, $allowlist, true) ? "inventory_balances.{$sortField}" : 'inventory_balances.id';
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $sortDirection)->orderBy('inventory_balances.id', $sortDirection)->cursor();
    }

    public function getCursorLowStock(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'shortage_quantity',
        string $sortDirection = 'desc'
    ): LazyCollection {
        $locationId = isset($filters['location_id']) ? (int) $filters['location_id'] : 0;
        if ($locationId === 0 || ! in_array($locationId, $allowedLocationIds, true)) {
            return LazyCollection::empty();
        }

        $filters['include_location_info'] = true;
        $query = LowStockQuery::forLocation($locationId, $filters);

        $allowlist = ['shortage_quantity', 'minimum_stock', 'on_hand_quantity', 'product_name', 'sku'];
        $sortField = in_array($sortField, $allowlist, true) ? $sortField : 'shortage_quantity';
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $sortDirection)->orderBy('products.id', $sortDirection)->cursor();
    }

    public function getCursorStockCardMovements(
        array $allowedLocationIds,
        int $productId,
        int $locationId,
        string $startDateTime,
        string $endNextDayDateTime
    ): LazyCollection {
        if (
            empty($allowedLocationIds)
            || ! in_array($locationId, $allowedLocationIds, true)
        ) {
            return LazyCollection::empty();
        }

        return DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->join('locations', 'locations.id', '=', 'stock_movements.location_id')
            ->leftJoin('users as creators', 'creators.id', '=', 'stock_movements.created_by')
            ->leftJoin('stock_transfers', function ($join) {
                $join->on('stock_transfers.id', '=', 'stock_movements.reference_id')
                    ->where('stock_movements.reference_type', '=', StockTransfer::class);
            })
            ->leftJoin('locations as transfer_origins', 'transfer_origins.id', '=', 'stock_transfers.origin_location_id')
            ->leftJoin('locations as transfer_destinations', 'transfer_destinations.id', '=', 'stock_transfers.destination_location_id')
            ->select([
                'stock_movements.id',
                'stock_movements.occurred_at',
                'stock_movements.created_at',
                'stock_movements.movement_type',
                'stock_movements.movement_id',
                'stock_movements.reference_number',
                'products.sku',
                'products.name as product_name',
                'locations.code as location_code',
                'locations.name as location_name',
                'transfer_origins.code as transfer_origin_code',
                'transfer_origins.name as transfer_origin_name',
                'transfer_destinations.code as transfer_destination_code',
                'transfer_destinations.name as transfer_destination_name',
                'stock_movements.quantity_before',
                'stock_movements.quantity',
                'stock_movements.quantity_after',
                'creators.name as creator_name',
                'creators.username as creator_username',
                DB::raw("'' as notes"),
            ])
            ->where('stock_movements.product_id', $productId)
            ->where('stock_movements.location_id', $locationId)
            ->where('stock_movements.created_at', '>=', $startDateTime)
            ->where('stock_movements.created_at', '<', $endNextDayDateTime)
            ->orderBy('stock_movements.created_at', 'asc')
            ->orderBy('stock_movements.id', 'asc')
            ->cursor();
    }

    public function getCursorStockReceiptReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc'
    ): LazyCollection {
        if (empty($allowedLocationIds)) {
            return LazyCollection::empty();
        }

        $query = DB::table('stock_receipt_items')
            ->join('stock_receipts', 'stock_receipts.id', '=', 'stock_receipt_items.stock_receipt_id')
            ->join('products', 'products.id', '=', 'stock_receipt_items.product_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'stock_receipts.supplier_id')
            ->leftJoin('locations', 'locations.id', '=', 'stock_receipt_items.location_id')
            ->leftJoin('users as creators', 'creators.id', '=', 'stock_receipts.created_by')
            ->leftJoin('stock_movements', function ($join) {
                $join->on('stock_movements.reference_id', '=', 'stock_receipts.id')
                    ->where('stock_movements.reference_type', '=', StockReceipt::class)
                    ->on('stock_movements.product_id', '=', 'stock_receipt_items.product_id')
                    ->on('stock_movements.location_id', '=', 'stock_receipt_items.location_id')
                    ->whereIn('stock_movements.movement_type', [
                        MovementType::RECEIPT->value,
                        MovementType::RECEIPT_GA->value,
                    ]);
            })
            ->select([
                'stock_receipt_items.id as item_id',
                'stock_receipts.receipt_number',
                'stock_receipts.date as receipt_date',
                'stock_movements.created_at as movement_posted_at',
                'suppliers.name as supplier_name',
                'locations.code as location_code',
                'locations.name as location_name',
                'products.sku',
                'products.name as product_name',
                'units.name as unit_name',
                'stock_receipt_items.quantity',
                'creators.name as creator_name',
                'creators.username as creator_username',
                'creators.name as poster_name',
                'creators.username as poster_username',
                'stock_receipts.notes',
            ])
            ->where('stock_receipts.status', 'POSTED')
            ->whereIn('stock_receipt_items.location_id', $allowedLocationIds);

        $this->applyReceiptFilters($query, $filters);

        $sortMap = [
            'posted_at' => 'stock_movements.created_at',
            'document_date' => 'stock_receipts.date',
            'receipt_number' => 'stock_receipts.receipt_number',
            'id' => 'stock_receipt_items.id',
        ];
        $actualSortField = $sortMap[$sortField] ?? 'stock_movements.created_at';
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($actualSortField, $sortDirection)
            ->orderBy('stock_receipt_items.id', $sortDirection)
            ->cursor();
    }

    public function getCursorStockIssueReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc'
    ): LazyCollection {
        if (empty($allowedLocationIds)) {
            return LazyCollection::empty();
        }

        $query = DB::table('stock_issue_items')
            ->join('stock_issues', 'stock_issues.id', '=', 'stock_issue_items.stock_issue_id')
            ->join('products', 'products.id', '=', 'stock_issue_items.product_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->leftJoin('locations', 'locations.id', '=', 'stock_issue_items.location_id')
            ->leftJoin('users as creators', 'creators.id', '=', 'stock_issues.created_by')
            ->leftJoin('stock_movements', function ($join) {
                $join->on('stock_movements.reference_id', '=', 'stock_issues.id')
                    ->where('stock_movements.reference_type', '=', StockIssue::class)
                    ->on('stock_movements.product_id', '=', 'stock_issue_items.product_id')
                    ->on('stock_movements.location_id', '=', 'stock_issue_items.location_id')
                    ->where('stock_movements.movement_type', '=', 'ISSUE');
            })
            ->select([
                'stock_issue_items.id as item_id',
                'stock_issues.issue_number',
                'stock_issues.date as issue_date',
                'stock_movements.created_at as movement_posted_at',
                'locations.code as location_code',
                'locations.name as location_name',
                'stock_issues.purpose',
                'products.sku',
                'products.name as product_name',
                'units.name as unit_name',
                'stock_issue_items.quantity',
                'creators.name as creator_name',
                'creators.username as creator_username',
                'creators.name as poster_name',
                'creators.username as poster_username',
                'stock_issues.notes',
            ])
            ->where('stock_issues.status', 'POSTED')
            ->whereIn('stock_issue_items.location_id', $allowedLocationIds);

        $this->applyIssueFilters($query, $filters);

        $sortMap = [
            'posted_at' => 'stock_movements.created_at',
            'document_date' => 'stock_issues.date',
            'issue_number' => 'stock_issues.issue_number',
            'id' => 'stock_issue_items.id',
        ];
        $actualSortField = $sortMap[$sortField] ?? 'stock_movements.created_at';
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($actualSortField, $sortDirection)
            ->orderBy('stock_issue_items.id', $sortDirection)
            ->cursor();
    }

    public function getCursorStockTransferReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'sent_at',
        string $sortDirection = 'desc'
    ): LazyCollection {
        if (empty($allowedLocationIds)) {
            return LazyCollection::empty();
        }

        $dateBasis = strtoupper($filters['date_basis'] ?? 'SENT_AT');
        $dateColumn = ($dateBasis === 'RECEIVED_AT') ? 'stock_transfers.received_at' : 'stock_transfers.sent_at';

        $query = DB::table('stock_transfer_items')
            ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
            ->join('products', 'products.id', '=', 'stock_transfer_items.product_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->leftJoin('locations as origin_locations', 'origin_locations.id', '=', 'stock_transfers.origin_location_id')
            ->leftJoin('locations as dest_locations', 'dest_locations.id', '=', 'stock_transfers.destination_location_id')
            ->leftJoin('users as senders', 'senders.id', '=', 'stock_transfers.sent_by')
            ->leftJoin('users as receivers', 'receivers.id', '=', 'stock_transfers.received_by')
            ->select([
                'stock_transfer_items.id as item_id',
                'stock_transfers.transfer_number',
                'stock_transfers.transfer_date',
                'stock_transfers.status',
                'origin_locations.name as origin_location_name',
                'dest_locations.name as destination_location_name',
                'products.sku',
                'products.name as product_name',
                'units.name as unit_name',
                'stock_transfer_items.quantity',
                'senders.name as sender_name',
                'senders.username as sender_username',
                'stock_transfers.sent_at',
                'receivers.name as receiver_name',
                'receivers.username as receiver_username',
                'stock_transfers.received_at',
            ])
            ->where(function ($q) use ($allowedLocationIds) {
                $q->whereIn('stock_transfers.origin_location_id', $allowedLocationIds)
                    ->orWhereIn('stock_transfers.destination_location_id', $allowedLocationIds);
            });

        if ($dateBasis === 'RECEIVED_AT') {
            $query->where('stock_transfers.status', 'RECEIVED');
        } else {
            $query->whereIn('stock_transfers.status', ['IN_TRANSIT', 'RECEIVED']);
        }

        $this->applyTransferFilters($query, $filters, $dateColumn);

        $sortMap = [
            'sent_at' => 'stock_transfers.sent_at',
            'received_at' => 'stock_transfers.received_at',
            'transfer_number' => 'stock_transfers.transfer_number',
            'id' => 'stock_transfer_items.id',
        ];
        $actualSortField = $sortMap[$sortField] ?? $dateColumn;
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($actualSortField, $sortDirection)
            ->orderBy('stock_transfer_items.id', $sortDirection)
            ->cursor();
    }

    public function getCursorStockAdjustmentReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc'
    ): LazyCollection {
        if (empty($allowedLocationIds)) {
            return LazyCollection::empty();
        }

        $query = DB::table('stock_adjustment_items')
            ->join('stock_adjustments', 'stock_adjustments.id', '=', 'stock_adjustment_items.stock_adjustment_id')
            ->join('products', 'products.id', '=', 'stock_adjustment_items.product_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->leftJoin('locations', 'locations.id', '=', 'stock_adjustments.location_id')
            ->leftJoin('users as posters', 'posters.id', '=', 'stock_adjustments.posted_by')
            ->select([
                'stock_adjustment_items.id as item_id',
                'stock_adjustments.adjustment_number',
                'stock_adjustments.adjustment_date',
                'stock_adjustments.posted_at',
                'stock_adjustments.direction',
                'stock_adjustments.reason_code',
                'locations.code as location_code',
                'locations.name as location_name',
                'products.sku',
                'products.name as product_name',
                'products.unit_price',
                'units.name as unit_name',
                'stock_adjustment_items.quantity',
                'posters.name as poster_name',
                'posters.username as poster_username',
                'stock_adjustments.notes',
            ])
            ->where('stock_adjustments.status', 'POSTED')
            ->whereIn('stock_adjustments.location_id', $allowedLocationIds);

        $this->applyAdjustmentFilters($query, $filters);

        $sortMap = [
            'posted_at' => 'stock_adjustments.posted_at',
            'adjustment_date' => 'stock_adjustments.adjustment_date',
            'adjustment_number' => 'stock_adjustments.adjustment_number',
            'id' => 'stock_adjustment_items.id',
        ];
        $actualSortField = $sortMap[$sortField] ?? 'stock_adjustments.posted_at';
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($actualSortField, $sortDirection)
            ->orderBy('stock_adjustment_items.id', $sortDirection)
            ->cursor();
    }

    public function getCursorStockOpnameReport(
        array $allowedLocationIds,
        array $filters,
        string $sortField = 'posted_at',
        string $sortDirection = 'desc'
    ): LazyCollection {
        if (empty($allowedLocationIds)) {
            return LazyCollection::empty();
        }

        $query = DB::table('stock_opname_items')
            ->join('stock_opnames', 'stock_opnames.id', '=', 'stock_opname_items.stock_opname_id')
            ->join('products', 'products.id', '=', 'stock_opname_items.product_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->leftJoin('locations', 'locations.id', '=', 'stock_opnames.location_id')
            ->leftJoin('users as counters', 'counters.id', '=', 'stock_opname_items.counted_by')
            ->leftJoin('users as posters', 'posters.id', '=', 'stock_opnames.posted_by')
            ->select([
                'stock_opname_items.id as item_id',
                'stock_opnames.opname_number',
                'stock_opnames.opname_date',
                'stock_opnames.posted_at',
                'locations.code as location_code',
                'locations.name as location_name',
                'products.sku',
                'products.name as product_name',
                'units.name as unit_name',
                'stock_opname_items.snapshot_quantity',
                'stock_opname_items.counted_quantity',
                'stock_opname_items.variance_quantity',
                'stock_opname_items.is_unexpected',
                'counters.name as counter_name',
                'counters.username as counter_username',
                'posters.name as poster_name',
                'posters.username as poster_username',
                DB::raw("'' as item_notes"),
                'stock_opnames.notes as opname_notes',
            ])
            ->where('stock_opnames.status', 'POSTED')
            ->whereIn('stock_opnames.location_id', $allowedLocationIds);

        $this->applyOpnameFilters($query, $filters);

        $sortMap = [
            'posted_at' => 'stock_opnames.posted_at',
            'opname_date' => 'stock_opnames.opname_date',
            'opname_number' => 'stock_opnames.opname_number',
            'id' => 'stock_opname_items.id',
        ];
        $actualSortField = $sortMap[$sortField] ?? 'stock_opnames.posted_at';
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($actualSortField, $sortDirection)
            ->orderBy('stock_opname_items.id', $sortDirection)
            ->cursor();
    }

    private function buildStoreAllocationReportQuery(array $allowedLocationIds, array $filters)
    {
        if (empty($allowedLocationIds)) {
            return DB::table('store_allocation_items')->whereRaw('1 = 0');
        }

        $query = DB::table('store_allocation_items')
            ->join('store_allocations', 'store_allocations.id', '=', 'store_allocation_items.store_allocation_id')
            ->join('stores', 'stores.id', '=', 'store_allocations.store_id')
            ->join('users as technicians', 'technicians.id', '=', 'store_allocations.technician_user_id')
            ->join('locations', 'locations.id', '=', 'store_allocations.technician_location_id')
            ->join('products', 'products.id', '=', 'store_allocation_items.product_id')
            ->leftJoin('products as pulled_products', 'pulled_products.id', '=', 'store_allocation_items.pulled_product_id')
            ->whereIn('store_allocations.technician_location_id', $allowedLocationIds);

        if (! empty($filters['start_date'])) {
            $query->where('store_allocations.allocated_at', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->where('store_allocations.allocated_at', '<=', $filters['end_date']);
        }
        if (! empty($filters['store_id'])) {
            $query->where('store_allocations.store_id', $filters['store_id']);
        }
        if (! empty($filters['technician_id'])) {
            $query->where('store_allocations.technician_user_id', $filters['technician_id']);
        }
        if (! empty($filters['technician_location_id'])) {
            $query->where('store_allocations.technician_location_id', $filters['technician_location_id']);
        }
        if (! empty($filters['product_id'])) {
            $prodId = $filters['product_id'];
            $query->where(function ($q) use ($prodId) {
                $q->where('store_allocation_items.product_id', $prodId)
                    ->orWhere('store_allocation_items.pulled_product_id', $prodId);
            });
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('store_allocations.allocation_number', 'like', "%{$search}%")
                    ->orWhere('stores.name', 'like', "%{$search}%")
                    ->orWhere('stores.code', 'like', "%{$search}%")
                    ->orWhere('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('store_allocation_items.serial_number', 'like', "%{$search}%")
                    ->orWhere('store_allocation_items.pulled_serial_number', 'like', "%{$search}%")
                    ->orWhere('store_allocation_items.defective_reason', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function getPaginatedStoreAllocationReport(array $allowedLocationIds, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        if (empty($allowedLocationIds)) {
            return new ConcretePaginator([], 0, $perPage, 1);
        }

        $query = $this->buildStoreAllocationReportQuery($allowedLocationIds, $filters)
            ->select([
                'store_allocation_items.id',
                'store_allocation_items.store_allocation_id',
                'store_allocations.allocation_number',
                'store_allocations.allocated_at',
                'store_allocations.store_id',
                'stores.name as store_name',
                'stores.code as store_code',
                'stores.address as store_address',
                'technicians.name as technician_name',
                'locations.name as technician_location_name',
                'store_allocation_items.product_id',
                'products.name as product_name',
                'products.sku as product_sku',
                'products.unit_price as unit_price',
                'store_allocation_items.quantity',
                DB::raw('store_allocation_items.quantity * COALESCE(products.unit_price, 0) as total_value'),
                'store_allocation_items.serial_number',
                'store_allocation_items.pulled_product_id',
                'pulled_products.name as pulled_product_name',
                'pulled_products.sku as pulled_product_sku',
                'store_allocation_items.pulled_quantity',
                'store_allocation_items.pulled_serial_number',
                'store_allocation_items.defective_reason',
                'store_allocations.notes',
            ]);

        return $query->orderBy('store_allocations.allocated_at', 'desc')
            ->orderBy('store_allocation_items.id', 'desc')
            ->paginate($perPage);
    }

    public function getCursorStoreAllocationReport(array $allowedLocationIds, array $filters): LazyCollection
    {
        if (empty($allowedLocationIds)) {
            return LazyCollection::empty();
        }

        $query = $this->buildStoreAllocationReportQuery($allowedLocationIds, $filters)
            ->select([
                'store_allocation_items.id',
                'store_allocation_items.store_allocation_id',
                'store_allocations.allocation_number',
                'store_allocations.allocated_at',
                'stores.name as store_name',
                'stores.code as store_code',
                'stores.address as store_address',
                'technicians.name as technician_name',
                'locations.name as technician_location_name',
                'products.name as product_name',
                'products.sku as product_sku',
                'products.unit_price as unit_price',
                'store_allocation_items.quantity',
                DB::raw('store_allocation_items.quantity * COALESCE(products.unit_price, 0) as total_value'),
                'store_allocation_items.serial_number',
                'pulled_products.name as pulled_product_name',
                'pulled_products.sku as pulled_product_sku',
                'store_allocation_items.pulled_quantity',
                'store_allocation_items.pulled_serial_number',
                'store_allocation_items.defective_reason',
                'store_allocations.notes',
            ]);

        return $query->orderBy('store_allocations.allocated_at', 'desc')
            ->orderBy('store_allocation_items.id', 'desc')
            ->cursor();
    }

    public function getStoreAllocationReportSummary(array $allowedLocationIds, array $filters): array
    {
        if (empty($allowedLocationIds)) {
            return ['total_allocations' => 0, 'total_installed' => 0, 'total_pulled' => 0, 'total_value' => 0];
        }

        $base = $this->buildStoreAllocationReportQuery($allowedLocationIds, $filters);

        $row = $base->selectRaw('
            COUNT(DISTINCT store_allocations.id) as total_allocations,
            COALESCE(SUM(store_allocation_items.quantity), 0) as total_installed,
            COALESCE(SUM(store_allocation_items.pulled_quantity), 0) as total_pulled,
            COALESCE(SUM(store_allocation_items.quantity * COALESCE(products.unit_price, 0)), 0) as total_value
        ')->first();

        return [
            'total_allocations' => (int) ($row->total_allocations ?? 0),
            'total_installed' => (float) ($row->total_installed ?? 0),
            'total_pulled' => (float) ($row->total_pulled ?? 0),
            'total_value' => (float) ($row->total_value ?? 0),
        ];
    }

    private function buildFieldBalancesQuery(array $allowedLocationIds, array $filters)
    {
        if (empty($allowedLocationIds)) {
            return DB::table('inventory_balances')->whereRaw('1 = 0');
        }

        $query = DB::table('inventory_balances')
            ->join('locations', 'locations.id', '=', 'inventory_balances.location_id')
            ->leftJoin('users as technicians', 'technicians.id', '=', 'locations.user_id')
            ->join('products', 'products.id', '=', 'inventory_balances.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->where('locations.type', 'FIELD_PERSONNEL')
            ->whereIn('inventory_balances.location_id', $allowedLocationIds);

        if (! empty($filters['technician_id'])) {
            $query->where('locations.user_id', $filters['technician_id']);
        }
        if (! empty($filters['location_id'])) {
            $query->where('locations.id', $filters['location_id']);
        }
        if (! empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }
        if (! empty($filters['product_id'])) {
            $query->where('products.id', $filters['product_id']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('technicians.name', 'like', "%{$search}%")
                    ->orWhere('locations.name', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function getPaginatedFieldBalances(array $allowedLocationIds, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        if (empty($allowedLocationIds)) {
            return new ConcretePaginator([], 0, $perPage, 1);
        }

        $query = $this->buildFieldBalancesQuery($allowedLocationIds, $filters)
            ->groupBy([
                'inventory_balances.location_id',
                'inventory_balances.product_id',
                'locations.code',
                'locations.name',
                'locations.user_id',
                'technicians.name',
                'technicians.email',
                'products.id',
                'products.sku',
                'products.name',
                'products.unit_price',
                'categories.name',
                'units.name',
            ])
            ->select([
                'inventory_balances.location_id',
                'locations.code as location_code',
                'locations.name as location_name',
                'locations.user_id as technician_id',
                'technicians.name as technician_name',
                'technicians.email as technician_email',
                'products.id as product_id',
                'products.sku as product_sku',
                'products.name as product_name',
                'products.unit_price as unit_price',
                'categories.name as category_name',
                'units.name as unit_name',
                DB::raw("SUM(CASE WHEN inventory_balances.condition = 'GOOD' THEN inventory_balances.quantity ELSE 0 END) as good_quantity"),
                DB::raw("SUM(CASE WHEN inventory_balances.condition = 'DEFECTIVE' THEN inventory_balances.quantity ELSE 0 END) as defective_quantity"),
                DB::raw('SUM(inventory_balances.quantity) as total_quantity'),
                DB::raw('SUM(inventory_balances.quantity * COALESCE(products.unit_price, 0)) as total_value'),
            ]);

        return $query->orderBy('technicians.name')
            ->orderBy('products.name')
            ->paginate($perPage);
    }

    public function getCursorFieldBalances(array $allowedLocationIds, array $filters): LazyCollection
    {
        if (empty($allowedLocationIds)) {
            return LazyCollection::empty();
        }

        $query = $this->buildFieldBalancesQuery($allowedLocationIds, $filters)
            ->groupBy([
                'inventory_balances.location_id',
                'inventory_balances.product_id',
                'locations.code',
                'locations.name',
                'locations.user_id',
                'technicians.name',
                'technicians.email',
                'products.id',
                'products.sku',
                'products.name',
                'products.unit_price',
                'categories.name',
                'units.name',
            ])
            ->select([
                'locations.code as location_code',
                'locations.name as location_name',
                'technicians.name as technician_name',
                'products.sku as product_sku',
                'products.name as product_name',
                'products.unit_price as unit_price',
                'categories.name as category_name',
                'units.name as unit_name',
                DB::raw("SUM(CASE WHEN inventory_balances.condition = 'GOOD' THEN inventory_balances.quantity ELSE 0 END) as good_quantity"),
                DB::raw("SUM(CASE WHEN inventory_balances.condition = 'DEFECTIVE' THEN inventory_balances.quantity ELSE 0 END) as defective_quantity"),
                DB::raw('SUM(inventory_balances.quantity) as total_quantity'),
                DB::raw('SUM(inventory_balances.quantity * COALESCE(products.unit_price, 0)) as total_value'),
            ]);

        return $query->orderBy('technicians.name')
            ->orderBy('products.name')
            ->cursor();
    }

    public function getFieldBalancesSummary(array $allowedLocationIds, array $filters): array
    {
        if (empty($allowedLocationIds)) {
            return ['total_good' => 0, 'total_defective' => 0, 'total_units' => 0, 'total_value' => 0];
        }

        $base = $this->buildFieldBalancesQuery($allowedLocationIds, $filters);

        $row = $base->selectRaw("
            COALESCE(SUM(CASE WHEN inventory_balances.condition = 'GOOD' THEN inventory_balances.quantity ELSE 0 END), 0) as total_good,
            COALESCE(SUM(CASE WHEN inventory_balances.condition = 'DEFECTIVE' THEN inventory_balances.quantity ELSE 0 END), 0) as total_defective,
            COALESCE(SUM(inventory_balances.quantity), 0) as total_units,
            COALESCE(SUM(inventory_balances.quantity * COALESCE(products.unit_price, 0)), 0) as total_value
        ")->first();

        return [
            'total_good' => (float) ($row->total_good ?? 0),
            'total_defective' => (float) ($row->total_defective ?? 0),
            'total_units' => (float) ($row->total_units ?? 0),
            'total_value' => (float) ($row->total_value ?? 0),
        ];
    }
}
