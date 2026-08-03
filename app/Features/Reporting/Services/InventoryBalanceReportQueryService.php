<?php

namespace App\Features\Reporting\Services;

use App\Features\Reporting\Repositories\Contracts\ReportingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InventoryBalanceReportQueryService
{
    public function __construct(
        private readonly ReportingRepositoryInterface $repository
    ) {}

    public function getReport(array $allowedLocationIds, array $filters): LengthAwarePaginator
    {
        $sortField = $filters['sort_by'] ?? 'id';
        $sortDirection = $filters['sort_order'] ?? 'desc';
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $this->repository->getPaginatedBalances(
            $allowedLocationIds,
            $filters,
            $sortField,
            $sortDirection,
            $perPage
        );
    }
}
