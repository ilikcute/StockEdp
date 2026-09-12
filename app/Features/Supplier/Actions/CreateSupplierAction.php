<?php

namespace App\Features\Supplier\Actions;

use App\Features\Supplier\Models\Supplier;
use App\Features\Supplier\Repositories\Contracts\SupplierRepositoryInterface;

class CreateSupplierAction
{
    public function __construct(
        protected SupplierRepositoryInterface $repository
    ) {}

    public function execute(array $data, ?int $userId = null): Supplier
    {
        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = $data['is_active'] ?? true;
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;

        return $this->repository->create($data);
    }
}
