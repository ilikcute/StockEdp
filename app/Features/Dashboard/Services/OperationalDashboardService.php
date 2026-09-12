<?php

namespace App\Features\Dashboard\Services;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Dashboard\Repositories\Contracts\OperationalDashboardRepositoryInterface;
use App\Features\Reporting\Queries\InventoryMovementIntelligenceQuery;
use Carbon\Carbon;

class OperationalDashboardService
{
    public function __construct(
        private readonly OperationalDashboardRepositoryInterface $repository
    ) {}

    public function getDashboardData(array $allowedLocationIds, array $filters): array
    {
        $locationId = ! empty($filters['location_id']) ? (int) $filters['location_id'] : null;
        $period = $filters['period'] ?? '7d';

        [$dateFrom, $dateTo] = $this->calculatePeriodDates($period);

        $inventoryHealth = $this->repository->getInventoryHealth($allowedLocationIds, $locationId);
        $operationalQueue = $this->repository->getOperationalQueue($allowedLocationIds, $locationId);
        $periodActivity = $this->repository->getPeriodActivity($allowedLocationIds, $locationId, $dateFrom, $dateTo);
        $recentActivity = $this->repository->getRecentActivity($allowedLocationIds, $locationId);
        $topIssued = $this->repository->getTopIssuedProducts($allowedLocationIds, $locationId, $dateFrom, $dateTo);
        $topReceived = $this->repository->getTopReceivedProducts($allowedLocationIds, $locationId, $dateFrom, $dateTo);
        $filterOptions = $this->repository->getFilterOptions($allowedLocationIds);

        $alerts = $this->computeAlerts($inventoryHealth, $operationalQueue);
        $movementSummary = $this->repository->getInventoryMovementSummary($allowedLocationIds, $locationId, 90);

        return [
            'filters' => [
                'period' => $period,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'location_id' => $locationId,
            ],
            'filter_options' => $filterOptions,
            'inventory_health' => $inventoryHealth,
            'inventory_movement' => $movementSummary,
            'operational_queue' => $operationalQueue,
            'period_activity' => $periodActivity,
            'alerts' => $alerts,
            'recent_activity' => $recentActivity,
            'top_issued_products' => $topIssued,
            'top_received_products' => $topReceived,
            'generated_at' => Carbon::now('Asia/Jakarta')->toIso8601String(),
        ];
    }

    public function getMovementSummary(array $allowedLocationIds, array $filters): array
    {
        $locationId = ! empty($filters['location_id']) ? (int) $filters['location_id'] : null;
        $periodDays = isset($filters['period']) && in_array((int) $filters['period'], InventoryMovementIntelligenceQuery::ALLOWED_PERIODS, true)
            ? (int) $filters['period']
            : InventoryMovementIntelligenceQuery::DEFAULT_PERIOD;

        return $this->repository->getInventoryMovementSummary($allowedLocationIds, $locationId, $periodDays);
    }

    private function calculatePeriodDates(string $period): array
    {
        $today = Carbon::now('Asia/Jakarta');

        return match ($period) {
            'today' => [$today->toDateString(), $today->toDateString()],
            '30d' => [$today->copy()->subDays(29)->toDateString(), $today->toDateString()],
            default => [$today->copy()->subDays(6)->toDateString(), $today->toDateString()],
        };
    }

    private function computeAlerts(array $health, array $queue): array
    {
        $alerts = [];

        // 1. CRITICAL: Out of Stock
        if ($health['out_of_stock_count'] > 0) {
            $alerts[] = [
                'type' => 'OUT_OF_STOCK',
                'severity' => 'CRITICAL',
                'title' => 'Stok Habis Membutuhkan Penanganan',
                'message' => "Terdapat {$health['out_of_stock_count']} item persediaan dengan stok 0.",
                'count' => $health['out_of_stock_count'],
                'route_name' => 'reports.inventory-balances',
                'permission' => PermissionCode::REPORTS_INVENTORY_BALANCE_VIEW->value,
            ];
        }

        // 2. WARNING: Low Stock
        if ($health['low_stock_count'] > 0) {
            $alerts[] = [
                'type' => 'LOW_STOCK',
                'severity' => 'WARNING',
                'title' => 'Stok Minimum Perlu Perhatian',
                'message' => "Terdapat {$health['low_stock_count']} produk di bawah batas stok minimum.",
                'count' => $health['low_stock_count'],
                'route_name' => 'reports.low-stock',
                'permission' => PermissionCode::REPORTS_LOW_STOCK_VIEW->value,
            ];
        }

        // 3. INFO: Transfers Awaiting Receipt
        if ($queue['transfer_awaiting_receipt_count'] > 0) {
            $alerts[] = [
                'type' => 'TRANSFER_AWAITING_RECEIPT',
                'severity' => 'INFO',
                'title' => 'Transfer Stok Menunggu Penerimaan',
                'message' => "Terdapat {$queue['transfer_awaiting_receipt_count']} dokumen transfer status Dikirim yang belum diterima.",
                'count' => $queue['transfer_awaiting_receipt_count'],
                'route_name' => 'inventory.transfers',
                'permission' => PermissionCode::STOCK_TRANSFERS_VIEW->value,
            ];
        }

        // 4. INFO: Adjustment Pending
        if ($queue['adjustment_pending_count'] > 0) {
            $alerts[] = [
                'type' => 'ADJUSTMENT_PENDING',
                'severity' => 'INFO',
                'title' => 'Draft Penyesuaian Stok Menunggu Posting',
                'message' => "Terdapat {$queue['adjustment_pending_count']} draft penyesuaian stok yang belum diposting.",
                'count' => $queue['adjustment_pending_count'],
                'route_name' => 'inventory.adjustments',
                'permission' => PermissionCode::STOCK_ADJUSTMENTS_VIEW->value,
            ];
        }

        // 5. INFO: Opname In Progress
        if ($queue['opname_in_progress_count'] > 0) {
            $alerts[] = [
                'type' => 'OPNAME_IN_PROGRESS',
                'severity' => 'INFO',
                'title' => 'Stock Opname Sedang Berlangsung',
                'message' => "Terdapat {$queue['opname_in_progress_count']} proses stock opname yang sedang dihitung.",
                'count' => $queue['opname_in_progress_count'],
                'route_name' => 'stockOpnames',
                'permission' => PermissionCode::STOCK_OPNAMES_VIEW->value,
            ];
        }

        // 6. INFO: Opname Awaiting Post
        if ($queue['opname_awaiting_post_count'] > 0) {
            $alerts[] = [
                'type' => 'OPNAME_AWAITING_POST',
                'severity' => 'INFO',
                'title' => 'Hasil Stock Opname Menunggu Posting',
                'message' => "Terdapat {$queue['opname_awaiting_post_count']} stock opname selesai dihitung dan menunggu posting.",
                'count' => $queue['opname_awaiting_post_count'],
                'route_name' => 'stockOpnames',
                'permission' => PermissionCode::STOCK_OPNAMES_VIEW->value,
            ];
        }

        // 7. INFO: Frozen Location
        if ($health['frozen_location_count'] > 0) {
            $alerts[] = [
                'type' => 'FROZEN_LOCATION',
                'severity' => 'INFO',
                'title' => 'Lokasi Persediaan Dalam Kondisi Beku',
                'message' => "Terdapat {$health['frozen_location_count']} lokasi persediaan yang sedang dibekukan untuk opname.",
                'count' => $health['frozen_location_count'],
                'route_name' => 'locations.index',
                'permission' => PermissionCode::LOCATIONS_VIEW->value,
            ];
        }

        return $alerts;
    }
}
