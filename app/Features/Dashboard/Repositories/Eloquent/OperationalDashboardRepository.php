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
                ->crossJoin('locations')
                ->leftJoin('inventory_balances', function ($join) {
                    $join->on('inventory_balances.product_id', '=', 'products.id')
                        ->on('inventory_balances.location_id', '=', 'locations.id');
                })
                ->whereIn('locations.id', $targetLocationIds)
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

        // Transfer Awaiting Receipt (Status SENT)
        $transferAwaitingReceiptCount = StockTransfer::query()
            ->where('status', TransferStatus::SENT->value)
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
                'posted_issue_count' => 0,
                'received_transfer_count' => 0,
                'movement_count' => 0,
            ];
        }

        $targetLocationIds = $locationId !== null ? [$locationId] : $allowedLocationIds;
        $startDateTime = $dateFrom.' 00:00:00';
        $endDateTime = $dateTo.' 23:59:59';

        $postedReceiptCount = StockReceipt::query()
            ->where('status', ReceiptStatus::POSTED->value)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->whereHas('items', fn ($q) => $q->whereIn('location_id', $targetLocationIds))
            ->count();

        $postedIssueCount = StockIssue::query()
            ->where('status', IssueStatus::POSTED->value)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->whereHas('items', fn ($q) => $q->whereIn('location_id', $targetLocationIds))
            ->count();

        $receivedTransferCount = StockTransfer::query()
            ->where('status', TransferStatus::RECEIVED->value)
            ->whereBetween('transfer_date', [$dateFrom, $dateTo])
            ->where(function ($q) use ($targetLocationIds) {
                $q->whereIn('origin_location_id', $targetLocationIds)
                    ->orWhereIn('destination_location_id', $targetLocationIds);
            })
            ->count();

        $movementCount = StockMovement::query()
            ->whereIn('location_id', $targetLocationIds)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->count();

        return [
            'posted_receipt_count' => (int) $postedReceiptCount,
            'posted_issue_count' => (int) $postedIssueCount,
            'received_transfer_count' => (int) $receivedTransferCount,
            'movement_count' => (int) $movementCount,
        ];
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
            return [
                'id' => $m->id,
                'occurred_at' => $m->occurred_at ?? $m->created_at?->toIso8601String() ?? '',
                'type' => $m->movement_type->value ?? (string) ($m->movement_type ?? $m->type),
                'reference_number' => $m->reference_number ?? $m->movement_id ?? '',
                'product_sku' => $m->product?->sku ?? '',
                'product_name' => $m->product?->name ?? '',
                'unit_symbol' => $m->product?->unit?->symbol ?? $m->product?->unit?->name ?? '',
                'location_code' => $m->location?->code ?? '',
                'location_name' => $m->location?->name ?? '',
                'quantity' => (string) $m->quantity,
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
        $startDateTime = $dateFrom.' 00:00:00';
        $endDateTime = $dateTo.' 23:59:59';

        $results = DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->whereIn('stock_movements.location_id', $targetLocationIds)
            ->whereIn('stock_movements.movement_type', [
                MovementType::ISSUE->value,
                MovementType::TRANSFER_OUT->value,
                MovementType::ADJUSTMENT_OUT->value,
                MovementType::OPNAME_OUT->value,
            ])
            ->whereBetween('stock_movements.created_at', [$startDateTime, $endDateTime])
            ->select([
                'products.id as product_id',
                'products.sku',
                'products.name as product_name',
                'units.symbol as unit_symbol',
                'units.name as unit_name',
                DB::raw('SUM(stock_movements.quantity) as total_quantity'),
                DB::raw('COUNT(stock_movements.id) as movement_count'),
            ])
            ->groupBy('products.id', 'products.sku', 'products.name', 'units.symbol', 'units.name')
            ->orderByDesc(DB::raw('SUM(stock_movements.quantity)'))
            ->limit(10)
            ->get();

        return $results->map(function ($row) {
            return [
                'product_id' => $row->product_id,
                'sku' => $row->sku,
                'name' => $row->product_name,
                'unit_symbol' => $row->unit_symbol ?: ($row->unit_name ?: ''),
                'total_quantity' => (string) sprintf('%.4f', $row->total_quantity),
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
        $startDateTime = $dateFrom.' 00:00:00';
        $endDateTime = $dateTo.' 23:59:59';

        $results = DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->whereIn('stock_movements.location_id', $targetLocationIds)
            ->whereIn('stock_movements.movement_type', [
                MovementType::RECEIPT->value,
                MovementType::TRANSFER_IN->value,
                MovementType::ADJUSTMENT_IN->value,
                MovementType::OPNAME_IN->value,
            ])
            ->whereBetween('stock_movements.created_at', [$startDateTime, $endDateTime])
            ->select([
                'products.id as product_id',
                'products.sku',
                'products.name as product_name',
                'units.symbol as unit_symbol',
                'units.name as unit_name',
                DB::raw('SUM(stock_movements.quantity) as total_quantity'),
                DB::raw('COUNT(stock_movements.id) as movement_count'),
            ])
            ->groupBy('products.id', 'products.sku', 'products.name', 'units.symbol', 'units.name')
            ->orderByDesc(DB::raw('SUM(stock_movements.quantity)'))
            ->limit(10)
            ->get();

        return $results->map(function ($row) {
            return [
                'product_id' => $row->product_id,
                'sku' => $row->sku,
                'name' => $row->product_name,
                'unit_symbol' => $row->unit_symbol ?: ($row->unit_name ?: ''),
                'total_quantity' => (string) sprintf('%.4f', $row->total_quantity),
                'movement_count' => (int) $row->movement_count,
            ];
        })->all();
    }
}
