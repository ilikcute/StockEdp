<?php

namespace App\Features\Reporting\Services;

use App\Features\Reporting\Repositories\Contracts\ReportingRepositoryInterface;
use Illuminate\Support\Collection;

class ReportFilterOptionsService
{
    public function __construct(
        protected ReportingRepositoryInterface $repository
    ) {}

    public function getBaseOptions(array $allowedLocationIds): array
    {
        return [
            'locations' => $this->repository->getBaseLocations($allowedLocationIds),
            'categories' => $this->repository->getActiveCategories(),
            'units' => $this->repository->getActiveUnits(),
        ];
    }

    public function getProductOptions(?string $search, int $perPage = 20): Collection
    {
        return $this->repository->searchProductOptions($search, $perPage);
    }

    public function getSupplierOptions(?string $search, int $perPage = 20): Collection
    {
        return $this->repository->searchSupplierOptions($search, $perPage);
    }
}
