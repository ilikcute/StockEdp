<?php

namespace App\Features\Reporting\Services;

use App\Features\Reporting\Repositories\Contracts\ReportingRepositoryInterface;

class FieldBalanceReportQueryService
{
    public function __construct(
        private readonly ReportingRepositoryInterface $repository
    ) {}

    public function getReport(array $allowedLocationIds, array $filters): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $paginatedItems = $this->repository->getPaginatedFieldBalances($allowedLocationIds, $filters, $perPage);
        $summary = $this->repository->getFieldBalancesSummary($allowedLocationIds, $filters);

        return [
            'items' => $paginatedItems,
            'meta' => [
                'summary' => $summary,
            ],
        ];
    }
}
