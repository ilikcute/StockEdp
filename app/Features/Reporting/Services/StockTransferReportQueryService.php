<?php

namespace App\Features\Reporting\Services;

use App\Features\Reporting\Repositories\Contracts\ReportingRepositoryInterface;

class StockTransferReportQueryService
{
    public function __construct(
        private readonly ReportingRepositoryInterface $repository
    ) {}

    public function getReport(array $allowedLocationIds, array $filters): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $dateBasis = strtoupper($filters['date_basis'] ?? 'SENT_AT');
        $sortField = $filters['sort_by'] ?? ($dateBasis === 'RECEIVED_AT' ? 'received_at' : 'sent_at');
        $sortDirection = $filters['sort_order'] ?? 'desc';

        $itemsPaginator = $this->repository->getPaginatedStockTransferReport(
            $allowedLocationIds,
            $filters,
            $sortField,
            $sortDirection,
            $perPage
        );

        $summary = $this->repository->getStockTransferReportSummary($allowedLocationIds, $filters);

        return [
            'meta' => [
                'date_basis' => $dateBasis,
                'summary' => $summary,
            ],
            'items' => $itemsPaginator,
        ];
    }
}
