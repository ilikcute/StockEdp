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

        $paginator = $this->repository->getPaginatedLowStock($allowedLocationIds, $filters);
        $productIds = collect($paginator->items())->pluck('product_id')->map(fn ($id) => (int) $id)->all();

        $pendingInbounds = $this->repository->getPendingInboundQuantities($filters->locationId, $productIds);
        $sourceBalances = $this->repository->getCandidateSourceBalances($filters->locationId, $allowedLocationIds, $productIds);

        $data = [];
        foreach ($paginator->items() as $item) {
            $productId = (int) $item->product_id;

            $onHandQuantity = bcadd((string) $item->on_hand_quantity, '0', 4);
            $minimumStock = bcadd((string) $item->minimum_stock, '0', 4);
            $grossShortageQuantity = bcadd((string) $item->gross_shortage_quantity, '0', 4);

            $pendingInboundQuantity = bcadd($pendingInbounds[$productId] ?? '0', '0', 4);

            $priority = bccomp($onHandQuantity, '0.0000', 4) <= 0
                ? ReplenishmentPriority::CRITICAL->value
                : ReplenishmentPriority::WARNING->value;

            if (bccomp($pendingInboundQuantity, $grossShortageQuantity, 4) >= 0) {
                $netReplenishmentNeed = '0.0000';
                $recommendationType = ReplenishmentRecommendationType::INBOUND_COVERED->value;
                $internalReplenishmentQuantity = '0.0000';
                $externalReorderQuantity = '0.0000';
                $sourceAllocations = [];
            } else {
                $netReplenishmentNeed = bcsub($grossShortageQuantity, $pendingInboundQuantity, 4);

                $candidates = [];
                $productSources = $sourceBalances[$productId] ?? [];

                foreach ($productSources as $src) {
                    $srcOnHand = bcadd((string) $src->source_on_hand_quantity, '0', 4);
                    $srcMin = bcadd((string) $src->source_minimum_stock, '0', 4);

                    if (bccomp($srcOnHand, $srcMin, 4) > 0) {
                        $surplus = bcsub($srcOnHand, $srcMin, 4);
                        $candidates[] = [
                            'source_location_id' => (int) $src->location_id,
                            'source_location_code' => (string) $src->location_code,
                            'source_location_name' => (string) $src->location_name,
                            'source_on_hand_quantity' => $srcOnHand,
                            'source_minimum_stock' => $srcMin,
                            'available_surplus_quantity' => $surplus,
                        ];
                    }
                }

                usort($candidates, function ($a, $b) {
                    $cmp = bccomp($b['available_surplus_quantity'], $a['available_surplus_quantity'], 4);
                    if ($cmp !== 0) {
                        return $cmp;
                    }

                    return $a['source_location_id'] <=> $b['source_location_id'];
                });

                $remainingNeed = $netReplenishmentNeed;
                $sourceAllocations = [];

                foreach ($candidates as $cand) {
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
                } elseif (bccomp($internalReplenishmentQuantity, '0.0000', 4) > 0) {
                    $recommendationType = ReplenishmentRecommendationType::MIXED->value;
                } else {
                    $recommendationType = ReplenishmentRecommendationType::EXTERNAL_REORDER->value;
                }
            }

            if (! empty($filters->recommendationType) && $recommendationType !== $filters->recommendationType) {
                continue;
            }

            $data[] = [
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

        $summary = $this->repository->calculateSummaryCounts($filters->locationId, $allowedLocationIds, $filters);

        return [
            'data' => $data,
            'summary' => $summary,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function getFilterOptions(array $allowedLocationIds): array
    {
        return $this->repository->getFilterOptions($allowedLocationIds);
    }
}
