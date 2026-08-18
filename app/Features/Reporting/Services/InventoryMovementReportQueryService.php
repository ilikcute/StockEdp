<?php

namespace App\Features\Reporting\Services;

use App\Features\Reporting\Helpers\DecimalQuantity;
use App\Features\Reporting\Queries\InventoryMovementIntelligenceQuery;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;

class InventoryMovementReportQueryService
{
    public function getReport(array $allowedLocationIds, array $filters): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 15;
        }

        $type = ($filters['type'] ?? 'slow-moving') === 'fast-moving' ? 'fast-moving' : 'slow-moving';
        $periodDays = isset($filters['period']) && in_array((int) $filters['period'], InventoryMovementIntelligenceQuery::ALLOWED_PERIODS, true)
            ? (int) $filters['period']
            : InventoryMovementIntelligenceQuery::DEFAULT_PERIOD;

        $locationId = ! empty($filters['location_id']) ? (int) $filters['location_id'] : null;

        if ($locationId !== null && ! in_array($locationId, $allowedLocationIds, true)) {
            abort(403, 'Akses ke lokasi ini ditolak.');
        }

        if (empty($allowedLocationIds)) {
            return [
                'meta' => [
                    'type' => $type,
                    'period' => $periodDays,
                    'date_from' => '',
                    'date_to' => '',
                    'summary' => [
                        'period_days' => $periodDays,
                        'slow_moving_count' => 0,
                        'fast_moving_count' => 0,
                    ],
                ],
                'items' => new ConcreteLengthAwarePaginator([], 0, $perPage),
            ];
        }

        $targetLocationIds = $locationId !== null ? [$locationId] : $allowedLocationIds;
        $periodInfo = InventoryMovementIntelligenceQuery::calculatePeriodDates($periodDays);
        $summary = InventoryMovementIntelligenceQuery::getSummary($allowedLocationIds, $locationId, $periodDays);

        $itemsPaginator = $type === 'fast-moving'
            ? $this->getFastMovingPaginated($targetLocationIds, $periodInfo, $filters, $perPage)
            : $this->getSlowMovingPaginated($targetLocationIds, $periodInfo, $filters, $perPage);

        return [
            'meta' => [
                'type' => $type,
                'period' => $periodDays,
                'date_from' => $periodInfo['start_date'],
                'date_to' => $periodInfo['end_date'],
                'summary' => $summary,
            ],
            'items' => $itemsPaginator,
        ];
    }

    private function getSlowMovingPaginated(array $targetLocationIds, array $periodInfo, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InventoryMovementIntelligenceQuery::buildSlowMovingBaseQuery($targetLocationIds, $periodInfo, $filters);

        $query->select([
            'products.id as product_id',
            'products.sku',
            'products.barcode',
            'products.name as product_name',
            'categories.name as category_name',
            'units.code as unit_code',
            'units.symbol as unit_symbol',
            'locations.id as location_id',
            'locations.code as location_code',
            'locations.name as location_name',
            'inventory_balances.quantity as raw_current_stock',
            'lm.last_movement_at',
        ]);

        $sortBy = $filters['sort_by'] ?? 'days_since_last_movement';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        match ($sortBy) {
            'product_name' => $query->orderBy('products.name', $sortOrder)->orderBy('products.id', 'asc'),
            'sku' => $query->orderBy('products.sku', $sortOrder)->orderBy('products.id', 'asc'),
            'current_stock' => $query->orderByRaw("COALESCE(inventory_balances.quantity, 0) {$sortOrder}")->orderBy('products.id', 'asc'),
            'last_movement_at' => $query->orderBy('lm.last_movement_at', $sortOrder)->orderBy('products.id', 'asc'),
            default => $sortOrder === 'asc'
                // Fewest days inactive first (most recent movement first)
                ? $query->orderByRaw('(lm.last_movement_at IS NULL) ASC, lm.last_movement_at DESC')->orderBy('products.name', 'asc')
                // Longest days inactive first (never moved on top, then oldest movement date)
                : $query->orderByRaw('(lm.last_movement_at IS NULL) DESC, lm.last_movement_at ASC')->orderBy('products.name', 'asc'),
        };

        $paginator = $query->paginate($perPage);
        $today = $periodInfo['today'];

        $paginator->getCollection()->transform(function ($row) use ($today) {
            $lastMovementAt = $row->last_movement_at !== null ? CarbonImmutable::parse($row->last_movement_at, 'Asia/Jakarta') : null;
            $daysSinceLastMovement = $lastMovementAt !== null
                ? abs((int) $today->startOfDay()->diffInDays($lastMovementAt->startOfDay(), false))
                : null;

            return [
                'product_id' => (int) $row->product_id,
                'sku' => (string) $row->sku,
                'barcode' => (string) ($row->barcode ?? ''),
                'product_name' => (string) $row->product_name,
                'category_name' => (string) ($row->category_name ?? ''),
                'unit_code' => (string) ($row->unit_code ?? ''),
                'unit_symbol' => (string) ($row->unit_symbol ?: ($row->unit_code ?: '')),
                'location_id' => (int) $row->location_id,
                'location_code' => (string) $row->location_code,
                'location_name' => (string) $row->location_name,
                'current_stock' => DecimalQuantity::normalize((string) ($row->raw_current_stock ?? '0.0000')),
                'last_movement_at' => $lastMovementAt?->toIso8601String(),
                'days_since_last_movement' => $daysSinceLastMovement,
                'movement_count' => 0,
            ];
        });

        return $paginator;
    }

    private function getFastMovingPaginated(array $targetLocationIds, array $periodInfo, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InventoryMovementIntelligenceQuery::buildFastMovingBaseQuery($targetLocationIds, $periodInfo, $filters);

        $query->select([
            'products.id as product_id',
            'products.sku',
            'products.barcode',
            'products.name as product_name',
            'categories.name as category_name',
            'units.code as unit_code',
            'units.symbol as unit_symbol',
            'locations.id as location_id',
            'locations.code as location_code',
            'locations.name as location_name',
            'inventory_balances.quantity as raw_current_stock',
            'oa.total_outbound_quantity',
            'oa.outbound_movement_count',
            'oa.movement_days',
            'oa.last_outbound_at',
        ]);

        $sortBy = $filters['sort_by'] ?? 'velocity_score';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        match ($sortBy) {
            'product_name' => $query->orderBy('products.name', $sortOrder)->orderBy('products.id', 'asc'),
            'sku' => $query->orderBy('products.sku', $sortOrder)->orderBy('products.id', 'asc'),
            'current_stock' => $query->orderByRaw("COALESCE(inventory_balances.quantity, 0) {$sortOrder}")->orderBy('products.id', 'asc'),
            'outbound_movement_count' => $query->orderBy('oa.outbound_movement_count', $sortOrder)->orderBy('products.id', 'asc'),
            'movement_days' => $query->orderBy('oa.movement_days', $sortOrder)->orderBy('products.id', 'asc'),
            default => $query->orderBy('oa.total_outbound_quantity', $sortOrder)->orderBy('products.id', 'asc'),
        };

        $paginator = $query->paginate($perPage);
        $periodDays = $periodInfo['period_days'];

        $paginator->getCollection()->transform(function ($row) use ($periodDays) {
            $totalOutbound = DecimalQuantity::normalize((string) $row->total_outbound_quantity);
            $avgDailyOutbound = bcdiv($totalOutbound, (string) $periodDays, 4);

            return [
                'product_id' => (int) $row->product_id,
                'sku' => (string) $row->sku,
                'barcode' => (string) ($row->barcode ?? ''),
                'product_name' => (string) $row->product_name,
                'category_name' => (string) ($row->category_name ?? ''),
                'unit_code' => (string) ($row->unit_code ?? ''),
                'unit_symbol' => (string) ($row->unit_symbol ?: ($row->unit_code ?: '')),
                'location_id' => (int) $row->location_id,
                'location_code' => (string) $row->location_code,
                'location_name' => (string) $row->location_name,
                'current_stock' => DecimalQuantity::normalize((string) ($row->raw_current_stock ?? '0.0000')),
                'total_outbound_quantity' => $totalOutbound,
                'outbound_movement_count' => (int) $row->outbound_movement_count,
                'movement_days' => (int) $row->movement_days,
                'analysis_period_days' => $periodDays,
                'average_daily_outbound' => $avgDailyOutbound,
                'velocity_score' => $avgDailyOutbound,
                'last_outbound_at' => $row->last_outbound_at !== null ? CarbonImmutable::parse($row->last_outbound_at, 'Asia/Jakarta')->toIso8601String() : null,
            ];
        });

        return $paginator;
    }
}
