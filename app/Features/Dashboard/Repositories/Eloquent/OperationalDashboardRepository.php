<?php

namespace App\Features\Dashboard\Repositories\Eloquent;

use App\Features\Dashboard\Repositories\Contracts\OperationalDashboardRepositoryInterface;
use App\Features\Inventory\Enums\AdjustmentStatus;
use App\Features\Inventory\Enums\IssueStatus;
use App\Features\Inventory\Enums\MovementType;
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
use App\Features\Reporting\Helpers\DecimalQuantity;
use App\Features\Reporting\Queries\InventoryMovementIntelligenceQuery;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class OperationalDashboardRepository implements OperationalDashboardRepositoryInterface
{
    public function getInventoryHealth(array $allowedLocationIds, ?int $locationId = null): array
    {
        if (empty($allowedLocationIds)) {
            return [
                'low_stock_count' => 0,
                'out_of_stock_count' => 0,
                'active_opname_count' => 0,
                'frozen_location_count' => 0,
            ];
        }

        $targetLocationIds = $locationId !== null ? [$locationId] : $allowedLocationIds;

        // 1. Low Stock Count - Exact parity with ReportingRepository
        if ($locationId !== null) {
            $lowStockCount = DB::table('products')
                ->leftJoin('inventory_balances', function ($join) use ($locationId) {
                    $join->on('inventory_balances.product_id', '=', 'products.id')
                        ->where('inventory_balances.location_id', '=', $locationId);
                })
                ->where('products.minimum_stock', '>', 0)
                ->whereRaw('COALESCE(inventory_balances.quantity, 0.0000) < products.minimum_stock')
                ->where('products.is_active', true)
                ->count();
        } else {
            // Across all allowed locations: count (product_id, location_id) pairs below minimum stock
            $lowStockCount = DB::table('products')
                ->join('locations', function ($join) use ($targetLocationIds) {
                    $join->whereIn('locations.id', $targetLocationIds);
                })
                ->leftJoin('inventory_balances', function ($join) {
                    $join->on('inventory_balances.product_id', '=', 'products.id')
                        ->on('inventory_balances.location_id', '=', 'locations.id');
                })
                ->where('products.minimum_stock', '>', 0)
                ->whereRaw('COALESCE(inventory_balances.quantity, 0.0000) < products.minimum_stock')
                ->where('products.is_active', true)
                ->count();
        }

        // 2. Out of Stock Count (balances = 0.0000)
        $outOfStockCount = InventoryBalance::query()
            ->whereIn('location_id', $targetLocationIds)
            ->where('quantity', '=', '0.0000')
            ->count();

        // 3. Active Opname Count (IN_PROGRESS or COUNTED)
        $activeOpnameCount = StockOpname::query()
            ->whereIn('location_id', $targetLocationIds)
            ->whereIn('status', [OpnameStatus::IN_PROGRESS->value, OpnameStatus::COUNTED->value])
            ->count();

        // 4. Frozen Location Count
        $frozenLocationCount = DB::table('inventory_location_locks')
            ->whereIn('location_id', $targetLocationIds)
            ->where('is_frozen', true)
            ->count();

        return [
            'low_stock_count' => (int) $lowStockCount,
            'out_of_stock_count' => (int) $outOfStockCount,
            'active_opname_count' => (int) $activeOpnameCount,
            'frozen_location_count' => (int) $frozenLocationCount,
        ];
    }

    public function getOperationalQueue(array $allowedLocationIds, ?int $locationId = null): array
    {
        if (empty($allowedLocationIds)) {
            return [
                'receipt_draft_count' => 0,
                'issue_draft_count' => 0,
                'transfer_awaiting_receipt_count' => 0,
                'adjustment_pending_count' => 0,
                'opname_in_progress_count' => 0,
                'opname_awaiting_post_count' => 0,
            ];
        }

        $targetLocationIds = $locationId !== null ? [$locationId] : $allowedLocationIds;

        // Receipt Drafts
        $receiptDraftCount = StockReceipt::query()
            ->where('status', ReceiptStatus::DRAFT->value)
            ->whereHas('items', function ($q) use ($targetLocationIds) {
                $q->whereIn('location_id', $targetLocationIds);
            })
            ->count();

        // Issue Drafts
        $issueDraftCount = StockIssue::query()
            ->where('status', IssueStatus::DRAFT->value)
            ->whereHas('items', function ($q) use ($targetLocationIds) {
                $q->whereIn('location_id', $targetLocationIds);
            })
            ->count();

        // Transfer Awaiting Receipt (Status IN_TRANSIT)
        $transferAwaitingReceiptCount = StockTransfer::query()
            ->where('status', TransferStatus::IN_TRANSIT->value)
            ->where(function ($q) use ($targetLocationIds) {
                $q->whereIn('origin_location_id', $targetLocationIds)
                    ->orWhereIn('destination_location_id', $targetLocationIds);
            })
            ->count();

        // Adjustment Pending (Status DRAFT)
        $adjustmentPendingCount = StockAdjustment::query()
            ->where('status', AdjustmentStatus::DRAFT->value)
            ->whereIn('location_id', $targetLocationIds)
            ->count();

        // Opname In Progress
        $opnameInProgressCount = StockOpname::query()
            ->where('status', OpnameStatus::IN_PROGRESS->value)
            ->whereIn('location_id', $targetLocationIds)
            ->count();

        // Opname Awaiting Post (Status COUNTED)
        $opnameAwaitingPostCount = StockOpname::query()
            ->where('status', OpnameStatus::COUNTED->value)
            ->whereIn('location_id', $targetLocationIds)
            ->count();

        return [
            'receipt_draft_count' => (int) $receiptDraftCount,
            'issue_draft_count' => (int) $issueDraftCount,
            'transfer_awaiting_receipt_count' => (int) $transferAwaitingReceiptCount,
            'adjustment_pending_count' => (int) $adjustmentPendingCount,
            'opname_in_progress_count' => (int) $opnameInProgressCount,
            'opname_awaiting_post_count' => (int) $opnameAwaitingPostCount,
        ];
    }

    public function getPeriodActivity(array $allowedLocationIds, ?int $locationId, string $dateFrom, string $dateTo): array
    {
        if (empty($allowedLocationIds)) {
            return [
                'posted_receipt_count' => 0,
                'receipt_item_count' => 0,
                'receipt_total_quantity' => '0.0000',
                'receipt_total_amount' => 0.0,

                'posted_issue_count' => 0,
                'issue_item_count' => 0,
                'issue_total_quantity' => '0.0000',
                'issue_total_amount' => 0.0,

                'received_transfer_count' => 0,
                'transfer_item_count' => 0,
                'transfer_total_quantity' => '0.0000',
                'transfer_total_amount' => 0.0,

                'movement_count' => 0,
                'movement_item_count' => 0,
                'movement_total_quantity' => '0.0000',
                'movement_total_amount' => 0.0,
            ];
        }

        $targetLocationIds = $locationId !== null ? [$locationId] : $allowedLocationIds;
        $startDateTime = CarbonImmutable::parse($dateFrom, 'Asia/Jakarta')->startOfDay();
        $endNextDateTime = CarbonImmutable::parse($dateTo, 'Asia/Jakarta')->addDay()->startOfDay();

        // 1. Receipt
        $receiptQuery = StockMovement::query()
            ->whereIn('stock_movements.location_id', $targetLocationIds)
            ->whereIn('stock_movements.movement_type', [
                MovementType::RECEIPT->value,
                MovementType::RECEIPT_GA->value,
            ])
            ->where('stock_movements.reference_type', StockReceipt::class)
            ->where('stock_movements.created_at', '>=', $startDateTime)
            ->where('stock_movements.created_at', '<', $endNextDateTime);

        $postedReceiptCount = (clone $receiptQuery)->distinct()->count('stock_movements.reference_id');
        $receiptStats = (clone $receiptQuery)
            ->leftJoin('products', 'products.id', '=', 'stock_movements.product_id')
            ->selectRaw('
                COUNT(DISTINCT stock_movements.product_id) as item_count,
                COALESCE(SUM(stock_movements.quantity), 0) as total_quantity,
                COALESCE(SUM(stock_movements.quantity * COALESCE(products.unit_price, 0)), 0) as total_amount
            ')
            ->first();

        // 2. Issue
        $issueQuery = StockMovement::query()
            ->whereIn('stock_movements.location_id', $targetLocationIds)
            ->where('stock_movements.movement_type', MovementType::ISSUE->value)
            ->where('stock_movements.reference_type', StockIssue::class)
            ->where('stock_movements.created_at', '>=', $startDateTime)
            ->where('stock_movements.created_at', '<', $endNextDateTime);

        $postedIssueCount = (clone $issueQuery)->distinct()->count('stock_movements.reference_id');
        $issueStats = (clone $issueQuery)
            ->leftJoin('products', 'products.id', '=', 'stock_movements.product_id')
            ->selectRaw('
                COUNT(DISTINCT stock_movements.product_id) as item_count,
                COALESCE(SUM(stock_movements.quantity), 0) as total_quantity,
                COALESCE(SUM(stock_movements.quantity * COALESCE(products.unit_price, 0)), 0) as total_amount
            ')
            ->first();

        // 3. Transfer
        $transferQuery = StockTransfer::query()
            ->where('stock_transfers.status', TransferStatus::RECEIVED->value)
            ->where('stock_transfers.received_at', '>=', $startDateTime)
            ->where('stock_transfers.received_at', '<', $endNextDateTime)
            ->where(function ($q) use ($targetLocationIds) {
                $q->whereIn('stock_transfers.origin_location_id', $targetLocationIds)
                    ->orWhereIn('stock_transfers.destination_location_id', $targetLocationIds);
            });

        $receivedTransferCount = (clone $transferQuery)->count();
        $transferStats = (clone $transferQuery)
            ->join('stock_transfer_items', 'stock_transfer_items.stock_transfer_id', '=', 'stock_transfers.id')
            ->leftJoin('products', 'products.id', '=', 'stock_transfer_items.product_id')
            ->selectRaw('
                COUNT(DISTINCT stock_transfer_items.product_id) as item_count,
                COALESCE(SUM(COALESCE(stock_transfer_items.received_quantity, stock_transfer_items.quantity)), 0) as total_quantity,
                COALESCE(SUM(COALESCE(stock_transfer_items.received_quantity, stock_transfer_items.quantity) * COALESCE(products.unit_price, 0)), 0) as total_amount
            ')
            ->first();

        // 4. Movement
        $movementQuery = StockMovement::query()
            ->whereIn('stock_movements.location_id', $targetLocationIds)
            ->where('stock_movements.created_at', '>=', $startDateTime)
            ->where('stock_movements.created_at', '<', $endNextDateTime);

        $movementCount = (clone $movementQuery)->count();
        $movementStats = (clone $movementQuery)
            ->leftJoin('products', 'products.id', '=', 'stock_movements.product_id')
            ->selectRaw('
                COUNT(DISTINCT stock_movements.product_id) as item_count,
                COALESCE(SUM(stock_movements.quantity), 0) as total_quantity,
                COALESCE(SUM(stock_movements.quantity * COALESCE(products.unit_price, 0)), 0) as total_amount
            ')
            ->first();

        return [
            'posted_receipt_count' => (int) $postedReceiptCount,
            'receipt_item_count' => (int) ($receiptStats?->item_count ?? 0),
            'receipt_total_quantity' => $this->formatDecimalQuantity($receiptStats?->total_quantity),
            'receipt_total_amount' => (float) ($receiptStats?->total_amount ?? 0.0),

            'posted_issue_count' => (int) $postedIssueCount,
            'issue_item_count' => (int) ($issueStats?->item_count ?? 0),
            'issue_total_quantity' => $this->formatDecimalQuantity($issueStats?->total_quantity),
            'issue_total_amount' => (float) ($issueStats?->total_amount ?? 0.0),

            'received_transfer_count' => (int) $receivedTransferCount,
            'transfer_item_count' => (int) ($transferStats?->item_count ?? 0),
            'transfer_total_quantity' => $this->formatDecimalQuantity($transferStats?->total_quantity),
            'transfer_total_amount' => (float) ($transferStats?->total_amount ?? 0.0),

            'movement_count' => (int) $movementCount,
            'movement_item_count' => (int) ($movementStats?->item_count ?? 0),
            'movement_total_quantity' => $this->formatDecimalQuantity($movementStats?->total_quantity),
            'movement_total_amount' => (float) ($movementStats?->total_amount ?? 0.0),
        ];
    }

    private function formatDecimalQuantity(mixed $value): string
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0' || $value === 0.0) {
            return '0.0000';
        }

        return DecimalQuantity::normalize((string) $value);
    }

    public function getRecentActivity(array $allowedLocationIds, ?int $locationId): array
    {
        if (empty($allowedLocationIds)) {
            return [];
        }

        $targetLocationIds = $locationId !== null ? [$locationId] : $allowedLocationIds;

        $movements = StockMovement::with(['product.unit', 'location', 'creator'])
            ->whereIn('location_id', $targetLocationIds)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return $movements->map(function (StockMovement $m) {
            $unitPrice = (string) ($m->product?->unit_price ?? '0.00');
            $quantity = DecimalQuantity::normalize((string) $m->quantity);
            $totalGross = bcmul($quantity, $unitPrice, 2);

            return [
                'id' => $m->id,
                'occurred_at' => $m->occurred_at ?? $m->created_at?->toIso8601String() ?? '',
                'type' => $m->movement_type->value ?? (string) ($m->movement_type ?? $m->type),
                'reference_number' => $m->reference_number ?? $m->movement_id ?? '',
                'product_sku' => $m->product?->sku ?? '',
                'product_name' => $m->product?->name ?? '',
                'unit_symbol' => $m->product?->unit?->symbol ?? $m->product?->unit?->name ?? '',
                'unit_price' => $unitPrice,
                'total_gross' => $totalGross,
                'location_code' => $m->location?->code ?? '',
                'location_name' => $m->location?->name ?? '',
                'quantity' => $quantity,
                'performed_by' => $m->creator?->name ?? 'System',
            ];
        })->all();
    }

    public function getTopIssuedProducts(array $allowedLocationIds, ?int $locationId, string $dateFrom, string $dateTo): array
    {
        if (empty($allowedLocationIds)) {
            return [];
        }

        $targetLocationIds = $locationId !== null ? [$locationId] : $allowedLocationIds;
        $startDateTime = CarbonImmutable::parse($dateFrom, 'Asia/Jakarta')->startOfDay();
        $endNextDateTime = CarbonImmutable::parse($dateTo, 'Asia/Jakarta')->addDay()->startOfDay();

        $results = DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->whereIn('stock_movements.location_id', $targetLocationIds)
            ->where('stock_movements.movement_type', '=', MovementType::ISSUE->value)
            ->where('stock_movements.created_at', '>=', $startDateTime)
            ->where('stock_movements.created_at', '<', $endNextDateTime)
            ->select([
                'products.id as product_id',
                'products.sku',
                'products.name as product_name',
                'products.unit_price',
                'units.symbol as unit_symbol',
                'units.name as unit_name',
                DB::raw('SUM(stock_movements.quantity) as total_quantity'),
                DB::raw('COUNT(stock_movements.id) as movement_count'),
            ])
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.unit_price', 'units.symbol', 'units.name')
            ->orderByDesc(DB::raw('SUM(stock_movements.quantity)'))
            ->limit(10)
            ->get();

        return $results->map(function ($row) {
            $unitPrice = (string) ($row->unit_price ?? '0.00');
            $totalQuantity = DecimalQuantity::normalize((string) $row->total_quantity);
            $totalGross = bcmul($totalQuantity, $unitPrice, 2);

            return [
                'product_id' => $row->product_id,
                'sku' => $row->sku,
                'name' => $row->product_name,
                'unit_symbol' => $row->unit_symbol ?: ($row->unit_name ?: ''),
                'unit_price' => $unitPrice,
                'total_gross' => $totalGross,
                'total_quantity' => $totalQuantity,
                'movement_count' => (int) $row->movement_count,
            ];
        })->all();
    }

    public function getTopReceivedProducts(array $allowedLocationIds, ?int $locationId, string $dateFrom, string $dateTo): array
    {
        if (empty($allowedLocationIds)) {
            return [];
        }

        $targetLocationIds = $locationId !== null ? [$locationId] : $allowedLocationIds;
        $startDateTime = CarbonImmutable::parse($dateFrom, 'Asia/Jakarta')->startOfDay();
        $endNextDateTime = CarbonImmutable::parse($dateTo, 'Asia/Jakarta')->addDay()->startOfDay();

        $results = DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->whereIn('stock_movements.movement_type', [
                MovementType::RECEIPT->value,
                MovementType::RECEIPT_GA->value,
            ])
            ->where('stock_movements.created_at', '>=', $startDateTime)
            ->where('stock_movements.created_at', '<', $endNextDateTime)
            ->select([
                'products.id as product_id',
                'products.sku',
                'products.name as product_name',
                'products.unit_price',
                'units.symbol as unit_symbol',
                'units.name as unit_name',
                DB::raw('SUM(stock_movements.quantity) as total_quantity'),
                DB::raw('COUNT(stock_movements.id) as movement_count'),
            ])
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.unit_price', 'units.symbol', 'units.name')
            ->orderByDesc(DB::raw('SUM(stock_movements.quantity)'))
            ->limit(10)
            ->get();

        return $results->map(function ($row) {
            $unitPrice = (string) ($row->unit_price ?? '0.00');
            $totalQuantity = DecimalQuantity::normalize((string) $row->total_quantity);
            $totalGross = bcmul($totalQuantity, $unitPrice, 2);

            return [
                'product_id' => $row->product_id,
                'sku' => $row->sku,
                'name' => $row->product_name,
                'unit_symbol' => $row->unit_symbol ?: ($row->unit_name ?: ''),
                'unit_price' => $unitPrice,
                'total_gross' => $totalGross,
                'total_quantity' => $totalQuantity,
                'movement_count' => (int) $row->movement_count,
            ];
        })->all();
    }

    public function getFilterOptions(array $allowedLocationIds): array
    {
        if (empty($allowedLocationIds)) {
            return ['locations' => []];
        }

        $locations = Location::query()
            ->whereIn('id', $allowedLocationIds)
            ->where('is_active', true)
            ->orderBy('code')
            ->select(['id', 'code', 'name', 'type'])
            ->get()
            ->map(fn ($loc) => [
                'id' => $loc->id,
                'code' => $loc->code,
                'name' => $loc->name,
                'type' => $loc->type instanceof \BackedEnum ? $loc->type->value : (string) $loc->type,
            ])
            ->all();

        return [
            'locations' => $locations,
        ];
    }

    public function getInventoryMovementSummary(array $allowedLocationIds, ?int $locationId = null, int $periodDays = 90): array
    {
        return InventoryMovementIntelligenceQuery::getSummary(
            $allowedLocationIds,
            $locationId,
            $periodDays
        );
    }
}
