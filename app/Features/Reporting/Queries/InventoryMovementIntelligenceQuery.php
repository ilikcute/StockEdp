<?php

namespace App\Features\Reporting\Queries;

use App\Features\Inventory\Enums\MovementType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class InventoryMovementIntelligenceQuery
{
    /**
     * Supported standard analysis periods in days.
     */
    public const ALLOWED_PERIODS = [30, 60, 90, 120, 180, 365];

    public const DEFAULT_PERIOD = 90;

    /**
     * Calculate period boundaries in Asia/Jakarta timezone.
     */
    public static function calculatePeriodDates(int $periodDays, ?string $referenceDate = null): array
    {
        $today = $referenceDate !== null
            ? CarbonImmutable::parse($referenceDate, 'Asia/Jakarta')->startOfDay()
            : CarbonImmutable::now('Asia/Jakarta')->startOfDay();

        $startDate = $today->subDays($periodDays - 1);
        $endDate = $today;

        return [
            'period_days' => $periodDays,
            'today' => $today,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'start_datetime' => $startDate->format('Y-m-d 00:00:00'),
            'end_next_day_datetime' => $endDate->addDay()->format('Y-m-d 00:00:00'),
        ];
    }

    /**
     * Get summary counts for Slow Moving and Fast Moving products.
     */
    public static function getSummary(array $allowedLocationIds, ?int $locationId = null, int $periodDays = self::DEFAULT_PERIOD): array
    {
        if (empty($allowedLocationIds)) {
            return [
                'period_days' => $periodDays,
                'slow_moving_count' => 0,
                'fast_moving_count' => 0,
            ];
        }

        $targetLocationIds = $locationId !== null ? [$locationId] : $allowedLocationIds;
        $periodInfo = self::calculatePeriodDates($periodDays);

        // 1. Slow Moving Count (active products in target locations with 0 movements in period)
        $slowMovingQuery = self::buildSlowMovingBaseQuery($targetLocationIds, $periodInfo);
        $slowMovingCount = $slowMovingQuery->count();

        // 2. Fast Moving Count (active products with actual outbound consumption in period)
        $fastMovingQuery = self::buildFastMovingBaseQuery($targetLocationIds, $periodInfo);
        $fastMovingCount = $fastMovingQuery->count();

        return [
            'period_days' => $periodDays,
            'slow_moving_count' => (int) $slowMovingCount,
            'fast_moving_count' => (int) $fastMovingCount,
        ];
    }

    /**
     * Build base query for Slow Moving products (active products with 0 movements in period).
     */
    public static function buildSlowMovingBaseQuery(array $targetLocationIds, array $periodInfo, array $filters = []): Builder
    {
        $startDateTime = $periodInfo['start_datetime'];
        $endNextDayDateTime = $periodInfo['end_next_day_datetime'];

        // Subquery for movements in period
        $periodMovements = DB::table('stock_movements')
            ->select('product_id', 'location_id', DB::raw('COUNT(id) as period_movement_count'))
            ->whereIn('location_id', $targetLocationIds)
            ->where('occurred_at', '>=', $startDateTime)
            ->where('occurred_at', '<', $endNextDayDateTime)
            ->groupBy('product_id', 'location_id');

        // Subquery for latest historical movement
        $lastMovements = DB::table('stock_movements')
            ->select('product_id', 'location_id', DB::raw('MAX(occurred_at) as last_movement_at'))
            ->whereIn('location_id', $targetLocationIds)
            ->groupBy('product_id', 'location_id');

        $query = DB::table('products')
            ->crossJoin('locations')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->leftJoin('inventory_balances', function ($join) {
                $join->on('inventory_balances.product_id', '=', 'products.id')
                    ->on('inventory_balances.location_id', '=', 'locations.id');
            })
            ->leftJoinSub($periodMovements, 'pm', function ($join) {
                $join->on('pm.product_id', '=', 'products.id')
                    ->on('pm.location_id', '=', 'locations.id');
            })
            ->leftJoinSub($lastMovements, 'lm', function ($join) {
                $join->on('lm.product_id', '=', 'products.id')
                    ->on('lm.location_id', '=', 'locations.id');
            })
            ->whereIn('locations.id', $targetLocationIds)
            ->where('products.is_active', true)
            ->whereRaw('COALESCE(pm.period_movement_count, 0) = 0');

        self::applyCommonFilters($query, $filters);

        return $query;
    }

    /**
     * Build base query for Fast Moving products (active products with outbound ISSUE movements in period).
     */
    public static function buildFastMovingBaseQuery(array $targetLocationIds, array $periodInfo, array $filters = []): Builder
    {
        $startDateTime = $periodInfo['start_datetime'];
        $endNextDayDateTime = $periodInfo['end_next_day_datetime'];

        // Aggregate outbound ISSUE movements per (product_id, location_id) in period
        $outboundAggregates = DB::table('stock_movements')
            ->select([
                'product_id',
                'location_id',
                DB::raw('SUM(quantity) as total_outbound_quantity'),
                DB::raw('COUNT(id) as outbound_movement_count'),
                DB::raw('COUNT(DISTINCT DATE(occurred_at)) as movement_days'),
                DB::raw('MAX(occurred_at) as last_outbound_at'),
            ])
            ->whereIn('location_id', $targetLocationIds)
            ->where('movement_type', MovementType::ISSUE->value)
            ->where('occurred_at', '>=', $startDateTime)
            ->where('occurred_at', '<', $endNextDayDateTime)
            ->groupBy('product_id', 'location_id')
            ->havingRaw('SUM(quantity) > 0');

        $query = DB::table('products')
            ->joinSub($outboundAggregates, 'oa', function ($join) {
                $join->on('oa.product_id', '=', 'products.id');
            })
            ->join('locations', 'locations.id', '=', 'oa.location_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->leftJoin('inventory_balances', function ($join) {
                $join->on('inventory_balances.product_id', '=', 'products.id')
                    ->on('inventory_balances.location_id', '=', 'locations.id');
            })
            ->whereIn('locations.id', $targetLocationIds)
            ->where('products.is_active', true);

        self::applyCommonFilters($query, $filters);

        return $query;
    }

    /**
     * Apply common category and search filters.
     */
    private static function applyCommonFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['category_id'])) {
            $query->where('products.category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['unit_id'])) {
            $query->where('products.unit_id', (int) $filters['unit_id']);
        }

        if (! empty($filters['search'])) {
            $search = addcslashes($filters['search'], '%_');
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('products.barcode', 'like', "%{$search}%");
            });
        }
    }
}
