<?php

namespace App\Features\Replenishment\Services;

use App\Features\Replenishment\DTOs\ReplenishmentFilterData;
use App\Features\Replenishment\Enums\ReplenishmentPriority;
use App\Features\Replenishment\Enums\ReplenishmentRecommendationType;
use App\Features\Replenishment\Repositories\Contracts\ReplenishmentRepositoryInterface;

class ReplenishmentRecommendationService
{
    public function __construct(
        private readonly ReplenishmentRepositoryInterface $repository
    ) {}

    public function getRecommendations(array $allowedLocationIds, ReplenishmentFilterData $filters): array
    {
        $targetLocation = $this->repository->getTargetLocation($filters->locationId);
        $isTargetFrozen = $this->repository->isLocationFrozen($filters->locationId);

        $actionable = ! $isTargetFrozen;
        $blockedReason = $isTargetFrozen ? 'TARGET_LOCATION_FROZEN' : null;

        $targetContext = $targetLocation ? [
            'id' => (int) $targetLocation->id,
            'code' => (string) $targetLocation->code,
            'name' => (string) $targetLocation->name,
        ] : [
            'id' => $filters->locationId,
            'code' => '',
            'name' => '',
        ];

        $candidates = $this->repository->getLowStockCandidates($allowedLocationIds, $filters);

        if ($candidates->isEmpty()) {
            return [
                'data' => [],
                'summary' => [
                    'low_stock_product_count' => 0,
                    'inbound_covered_count' => 0,
                    'internal_transfer_count' => 0,
                    'mixed_count' => 0,
                    'external_reorder_count' => 0,
                    'critical_product_count' => 0,
                ],
                'meta' => [
                    'current_page' => $filters->page,
                    'from' => null,
                    'last_page' => 1,
                    'per_page' => $filters->perPage,
                    'to' => null,
                    'total' => 0,
                ],
                'links' => [
                    'first' => null,
                    'last' => null,
                    'prev' => null,
                    'next' => null,
                ],
                'generated_at' => now()->toIso8601String(),
            ];
        }

        $productIds = $candidates->pluck('product_id')->map(fn ($id) => (int) $id)->all();
        $pendingInbounds = $this->repository->getPendingInboundQuantities($filters->locationId, $productIds);
        $sourceBalances = $this->repository->getCandidateSourceBalances($filters->locationId, $allowedLocationIds, $productIds);

        $allRecommendations = [];
        $criticalCount = 0;
        $inboundCoveredCount = 0;
        $internalTransferCount = 0;
        $mixedCount = 0;
        $externalReorderCount = 0;

        foreach ($candidates as $item) {
            $productId = (int) $item->product_id;

            $onHandQuantity = bcadd((string) $item->on_hand_quantity, '0', 4);
            $minimumStock = bcadd((string) $item->minimum_stock, '0', 4);
            $grossShortageQuantity = bcadd((string) $item->gross_shortage_quantity, '0', 4);

            $pendingInboundQuantity = bcadd($pendingInbounds[$productId] ?? '0', '0', 4);

            $priority = bccomp($onHandQuantity, '0.0000', 4) <= 0
                ? ReplenishmentPriority::CRITICAL->value
                : ReplenishmentPriority::WARNING->value;

            if ($priority === ReplenishmentPriority::CRITICAL->value) {
                $criticalCount++;
            }

            if (bccomp($pendingInboundQuantity, $grossShortageQuantity, 4) >= 0) {
                $netReplenishmentNeed = '0.0000';
                $recommendationType = ReplenishmentRecommendationType::INBOUND_COVERED->value;
                $internalReplenishmentQuantity = '0.0000';
                $externalReorderQuantity = '0.0000';
                $sourceAllocations = [];
                $inboundCoveredCount++;
            } else {
                $netReplenishmentNeed = bcsub($grossShortageQuantity, $pendingInboundQuantity, 4);

                $sourceCandidates = [];
                $productSources = $sourceBalances[$productId] ?? [];

                foreach ($productSources as $src) {
                    $srcOnHand = bcadd((string) $src->source_on_hand_quantity, '0', 4);
                    $srcMin = bcadd((string) $src->source_minimum_stock, '0', 4);

                    if (bccomp($srcOnHand, $srcMin, 4) > 0) {
                        $surplus = bcsub($srcOnHand, $srcMin, 4);
                        $sourceCandidates[] = [
                            'source_location_id' => (int) $src->location_id,
                            'source_location_code' => (string) $src->location_code,
                            'source_location_name' => (string) $src->location_name,
                            'source_on_hand_quantity' => $srcOnHand,
                            'source_minimum_stock' => $srcMin,
                            'available_surplus_quantity' => $surplus,
                        ];
                    }
                }

                usort($sourceCandidates, function ($a, $b) {
                    $cmp = bccomp($b['available_surplus_quantity'], $a['available_surplus_quantity'], 4);
                    if ($cmp !== 0) {
                        return $cmp;
                    }

                    return $a['source_location_id'] <=> $b['source_location_id'];
                });

                $remainingNeed = $netReplenishmentNeed;
                $sourceAllocations = [];

                foreach ($sourceCandidates as $cand) {
                    if (bccomp($remainingNeed, '0.0000', 4) <= 0) {
                        break;
                    }

                    $alloc = bccomp($cand['available_surplus_quantity'], $remainingNeed, 4) >= 0
                        ? $remainingNeed
                        : $cand['available_surplus_quantity'];

                    $cand['suggested_transfer_quantity'] = $alloc;
                    $sourceAllocations[] = $cand;

                    $remainingNeed = bcsub($remainingNeed, $alloc, 4);
                }

                $internalReplenishmentQuantity = bcsub($netReplenishmentNeed, $remainingNeed, 4);
                $externalReorderQuantity = bccomp($remainingNeed, '0.0000', 4) > 0 ? $remainingNeed : '0.0000';

                if (bccomp($externalReorderQuantity, '0.0000', 4) === 0) {
                    $recommendationType = ReplenishmentRecommendationType::INTERNAL_TRANSFER->value;
                    $internalTransferCount++;
                } elseif (bccomp($internalReplenishmentQuantity, '0.0000', 4) > 0) {
                    $recommendationType = ReplenishmentRecommendationType::MIXED->value;
                    $mixedCount++;
                } else {
                    $recommendationType = ReplenishmentRecommendationType::EXTERNAL_REORDER->value;
                    $externalReorderCount++;
                }
            }

            $allRecommendations[] = [
                'product_id' => $productId,
                'sku' => (string) $item->sku,
                'barcode' => $item->barcode !== null ? (string) $item->barcode : null,
                'product_name' => (string) $item->product_name,
                'category_name' => (string) ($item->category_name ?? '-'),
                'unit_name' => (string) ($item->unit_name ?? '-'),
                'target_location' => $targetContext,
                'target_is_frozen' => $isTargetFrozen,
                'on_hand_quantity' => $onHandQuantity,
                'minimum_stock' => $minimumStock,
                'gross_shortage_quantity' => $grossShortageQuantity,
                'pending_inbound_quantity' => $pendingInboundQuantity,
                'net_replenishment_need' => $netReplenishmentNeed,
                'recommendation_type' => $recommendationType,
                'priority' => $priority,
                'internal_replenishment_quantity' => $internalReplenishmentQuantity,
                'external_reorder_quantity' => $externalReorderQuantity,
                'source_allocations' => $sourceAllocations,
                'actionable' => $actionable,
                'blocked_reason' => $blockedReason,
            ];
        }

        $summary = [
            'low_stock_product_count' => count($allRecommendations),
            'inbound_covered_count' => $inboundCoveredCount,
            'internal_transfer_count' => $internalTransferCount,
            'mixed_count' => $mixedCount,
            'external_reorder_count' => $externalReorderCount,
            'critical_product_count' => $criticalCount,
        ];

        // Filter by recommendation_type if specified
        $filtered = $allRecommendations;
        if (! empty($filters->recommendationType)) {
            $filtered = array_values(array_filter(
                $allRecommendations,
                fn ($row) => $row['recommendation_type'] === $filters->recommendationType
            ));
        }

        // Apply sorting
        $sortBy = $filters->sortBy;
        $sortOrder = strtolower($filters->sortOrder) === 'asc' ? 'asc' : 'desc';

        usort($filtered, function ($a, $b) use ($sortBy, $sortOrder) {
            $cmp = 0;
            switch ($sortBy) {
                case 'gross_shortage_quantity':
                case 'shortage_quantity':
                    $cmp = bccomp($a['gross_shortage_quantity'], $b['gross_shortage_quantity'], 4);
                    break;
                case 'minimum_stock':
                    $cmp = bccomp($a['minimum_stock'], $b['minimum_stock'], 4);
                    break;
                case 'on_hand_quantity':
                    $cmp = bccomp($a['on_hand_quantity'], $b['on_hand_quantity'], 4);
                    break;
                case 'net_replenishment_need':
                    $cmp = bccomp($a['net_replenishment_need'], $b['net_replenishment_need'], 4);
                    break;
                case 'product_name':
                    $cmp = strcmp($a['product_name'], $b['product_name']);
                    break;
                case 'sku':
                    $cmp = strcmp($a['sku'], $b['sku']);
                    break;
                default:
                    $cmp = bccomp($a['gross_shortage_quantity'], $b['gross_shortage_quantity'], 4);
                    break;
            }

            if ($cmp === 0) {
                $cmp = $a['product_id'] <=> $b['product_id'];
            }

            return $sortOrder === 'asc' ? $cmp : -$cmp;
        });

        // Apply pagination on the filtered dataset
        $total = count($filtered);
        $perPage = max($filters->perPage, 1);
        $page = max($filters->page, 1);
        $lastPage = max((int) ceil($total / $perPage), 1);

        $offset = ($page - 1) * $perPage;
        $pagedData = array_slice($filtered, $offset, $perPage);
        $from = $total > 0 && count($pagedData) > 0 ? $offset + 1 : null;
        $to = $total > 0 && count($pagedData) > 0 ? $offset + count($pagedData) : null;

        $baseUrl = url('/api/v1/replenishment-recommendations');
        $queryParams = array_filter([
            'location_id' => $filters->locationId,
            'search' => $filters->search,
            'category_id' => $filters->categoryId,
            'unit_id' => $filters->unitId,
            'recommendation_type' => $filters->recommendationType,
            'priority' => $filters->priority,
            'sort_by' => $filters->sortBy,
            'sort_order' => $filters->sortOrder,
            'per_page' => $perPage,
        ]);

        $makeUrl = function (int $targetPage) use ($baseUrl, $queryParams) {
            $params = array_merge($queryParams, ['page' => $targetPage]);

            return $baseUrl.'?'.http_build_query($params);
        };

        return [
            'data' => $pagedData,
            'summary' => $summary,
            'meta' => [
                'current_page' => $page,
                'from' => $from,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'to' => $to,
                'total' => $total,
            ],
            'links' => [
                'first' => $total > 0 ? $makeUrl(1) : null,
                'last' => $total > 0 ? $makeUrl($lastPage) : null,
                'prev' => $page > 1 ? $makeUrl($page - 1) : null,
                'next' => $page < $lastPage ? $makeUrl($page + 1) : null,
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function getFilterOptions(array $allowedLocationIds): array
    {
        return $this->repository->getFilterOptions($allowedLocationIds);
    }
}
