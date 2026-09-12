<?php

namespace App\Features\Reporting\Services;

use App\Features\Reporting\Repositories\Contracts\ReportingRepositoryInterface;

class StoreAllocationReportQueryService
{
    public function __construct(
        private readonly ReportingRepositoryInterface $repository
    ) {}

    public function getReport(array $allowedLocationIds, array $filters): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $paginatedItems = $this->repository->getPaginatedStoreAllocationReport($allowedLocationIds, $filters, $perPage);
        $summary = $this->repository->getStoreAllocationReportSummary($allowedLocationIds, $filters);

        return [
            'items' => $paginatedItems,
            'meta' => [
                'summary' => $summary,
            ],
        ];
    }
}
