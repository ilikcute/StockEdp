<?php

namespace App\Features\Reporting\Services;

use App\Features\Reporting\Repositories\Contracts\ReportingRepositoryInterface;

class StockAdjustmentReportQueryService
{
    public function __construct(
        private readonly ReportingRepositoryInterface $repository
    ) {}

    public function getReport(array $allowedLocationIds, array $filters): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $sortField = $filters['sort_by'] ?? 'posted_at';
        $sortDirection = $filters['sort_order'] ?? 'desc';

        $itemsPaginator = $this->repository->getPaginatedStockAdjustmentReport(
            $allowedLocationIds,
            $filters,
            $sortField,
            $sortDirection,
            $perPage
        );

        $summary = $this->repository->getStockAdjustmentReportSummary($allowedLocationIds, $filters);

        return [
            'meta' => [
                'date_basis' => 'POSTED_AT',
                'summary' => $summary,
            ],
            'items' => $itemsPaginator,
        ];
    }
}
