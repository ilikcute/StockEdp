<?php

namespace App\Features\Replenishment\Services;

use App\Features\Replenishment\DTOs\ReplenishmentFilterData;
use App\Features\Replenishment\Enums\ReplenishmentPriority;
use App\Features\Replenishment\Enums\ReplenishmentRecommendationType;
use App\Features\Replenishment\Repositories\Contracts\ReplenishmentRepositoryInterface;
use App\Shared\Exceptions\DomainException;
use Illuminate\Auth\Access\AuthorizationException;

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
                fn ($rec) => $rec['recommendation_type'] === $filters->recommendationType
            ));
        }

        // Sorting
        $sortBy = $filters->sortBy;
        $sortOrder = strtolower($filters->sortOrder) === 'desc' ? -1 : 1;

        usort($filtered, function ($a, $b) use ($sortBy, $sortOrder) {
            $priorityRank = [
                ReplenishmentPriority::CRITICAL->value => 1,
                ReplenishmentPriority::WARNING->value => 2,
            ];

            if ($sortBy === 'priority') {
                $rankA = $priorityRank[$a['priority']] ?? 99;
                $rankB = $priorityRank[$b['priority']] ?? 99;
                if ($rankA !== $rankB) {
                    return ($rankA <=> $rankB) * $sortOrder;
                }
            } elseif (in_array($sortBy, ['gross_shortage_quantity', 'net_replenishment_need', 'internal_replenishment_quantity', 'on_hand_quantity', 'minimum_stock'], true)) {
                $cmp = bccomp((string) $a[$sortBy], (string) $b[$sortBy], 4);
                if ($cmp !== 0) {
                    return $cmp * $sortOrder;
                }
            } elseif (isset($a[$sortBy]) && isset($b[$sortBy])) {
                $cmp = strcmp((string) $a[$sortBy], (string) $b[$sortBy]);
                if ($cmp !== 0) {
                    return $cmp * $sortOrder;
                }
            }

            // Deterministic secondary sort: net_replenishment_need DESC, product_id ASC
            $secCmp = bccomp($b['net_replenishment_need'], $a['net_replenishment_need'], 4);
            if ($secCmp !== 0) {
                return $secCmp;
            }

            return $a['product_id'] <=> $b['product_id'];
        });

        // Pagination
        $total = count($filtered);
        $perPage = max(1, $filters->perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $filters->page), $lastPage);
        $offset = ($page - 1) * $perPage;

        $pagedData = array_slice($filtered, $offset, $perPage);
        $from = $total > 0 ? $offset + 1 : null;
        $to = $total > 0 ? min($offset + count($pagedData), $total) : null;

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
                'first' => 1,
                'last' => $lastPage,
                'prev' => $page > 1 ? $page - 1 : null,
                'next' => $page < $lastPage ? $page + 1 : null,
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function validateAction(array $allowedLocationIds, int $targetLocationId, array $items): array
    {
        if (! in_array($targetLocationId, $allowedLocationIds, true)) {
            throw new AuthorizationException('Lokasi tujuan di luar hak akses Anda.');
        }

        $targetLocation = $this->repository->getTargetLocation($targetLocationId);
        if (! $targetLocation || ! $targetLocation->is_active) {
            throw new DomainException('Lokasi tujuan tidak ditemukan atau tidak aktif.', 422);
        }

        if ($this->repository->isLocationFrozen($targetLocationId)) {
            throw new DomainException('Lokasi tujuan saat ini sedang dalam proses pembekuan persediaan (Stock Opname).', 409);
        }

        $productIds = array_unique(array_map(fn ($i) => (int) $i['product_id'], $items));
        $products = $this->repository->getProducts($productIds)->keyBy('id');

        $pendingInbounds = $this->repository->getPendingInboundQuantities($targetLocationId, $productIds);

        $validatedItems = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $sourceLocationId = (int) $item['source_location_id'];
            $requestedQty = bcadd((string) $item['requested_quantity'], '0', 4);

            if (! in_array($sourceLocationId, $allowedLocationIds, true)) {
                throw new AuthorizationException("Lokasi asal (ID: {$sourceLocationId}) di luar hak akses Anda.");
            }

            $sourceLocation = $this->repository->getLocation($sourceLocationId);
            if (! $sourceLocation || ! $sourceLocation->is_active) {
                throw new DomainException("Lokasi asal (ID: {$sourceLocationId}) tidak ditemukan atau tidak aktif.", 422);
            }

            if ($this->repository->isLocationFrozen($sourceLocationId)) {
                throw new DomainException("Lokasi asal '{$sourceLocation->name}' saat ini sedang dalam proses pembekuan persediaan (Stock Opname).", 409);
            }

            $product = $products->get($productId);
            if (! $product || ! $product->is_active) {
                $name = $product ? $product->name : "ID: {$productId}";
                throw new DomainException("Produk '{$name}' tidak ditemukan atau nonaktif.", 422);
            }

            // Target live calculations
            $targetOnHand = $this->repository->getInventoryBalanceQuantity($targetLocationId, $productId);
            $targetMinStock = bcadd((string) $product->minimum_stock, '0', 4);
            $pendingInbound = bcadd($pendingInbounds[$productId] ?? '0', '0', 4);

            $grossShortage = bccomp($targetMinStock, $targetOnHand, 4) > 0
                ? bcsub($targetMinStock, $targetOnHand, 4)
                : '0.0000';

            $liveNetNeed = bccomp($grossShortage, $pendingInbound, 4) > 0
                ? bcsub($grossShortage, $pendingInbound, 4)
                : '0.0000';

            if (bccomp($liveNetNeed, '0.0000', 4) <= 0) {
                throw new DomainException(
                    "Kebutuhan persediaan produk '{$product->name}' di lokasi '{$targetLocation->name}' telah terpenuhi oleh transaksi terbaru (Net Need: 0.0000).",
                    409
                );
            }

            // Source live calculations
            $sourceOnHand = $this->repository->getInventoryBalanceQuantity($sourceLocationId, $productId);
            $sourceMinStock = $targetMinStock; // Product minimum stock is global on product model

            $liveSourceSurplus = bccomp($sourceOnHand, $sourceMinStock, 4) > 0
                ? bcsub($sourceOnHand, $sourceMinStock, 4)
                : '0.0000';

            if (bccomp($liveSourceSurplus, '0.0000', 4) <= 0) {
                throw new DomainException(
                    "Gudang asal '{$sourceLocation->name}' tidak lagi memiliki surplus untuk produk '{$product->name}' (On Hand: {$sourceOnHand}, Min Stock: {$sourceMinStock}).",
                    409
                );
            }

            if (bccomp($requestedQty, $liveSourceSurplus, 4) > 0) {
                throw new DomainException(
                    "Kuantitas yang diminta ({$requestedQty}) melebihi surplus aktual yang tersedia di gudang asal '{$sourceLocation->name}' ({$liveSourceSurplus}).",
                    409
                );
            }

            $validatedItems[] = [
                'product_id' => $productId,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'source_location_id' => $sourceLocationId,
                'source_location_code' => $sourceLocation->code,
                'source_location_name' => $sourceLocation->name,
                'target_location_id' => $targetLocationId,
                'target_location_code' => $targetLocation->code,
                'target_location_name' => $targetLocation->name,
                'requested_quantity' => $requestedQty,
                'target_on_hand_quantity' => $targetOnHand,
                'target_minimum_stock' => $targetMinStock,
                'target_net_need' => $liveNetNeed,
                'source_available_surplus' => $liveSourceSurplus,
            ];
        }

        return [
            'valid' => true,
            'code' => 'VALID',
            'message' => 'Rekomendasi transfer valid dan siap diproses.',
            'target_location' => [
                'id' => $targetLocation->id,
                'code' => $targetLocation->code,
                'name' => $targetLocation->name,
            ],
            'items' => $validatedItems,
            'validated_at' => now()->toIso8601String(),
        ];
    }

    public function getFilterOptions(array $allowedLocationIds): array
    {
        return $this->repository->getFilterOptions($allowedLocationIds);
    }
}
