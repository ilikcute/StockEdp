<?php

namespace App\Features\Supplier\Actions;

use App\Features\Supplier\Models\Supplier;
use App\Features\Supplier\Repositories\Contracts\SupplierRepositoryInterface;

class SetSupplierStatusAction
{
    public function __construct(
        protected SupplierRepositoryInterface $repository
    ) {}

    public function execute(Supplier $supplier, bool $isActive, ?int $userId = null): Supplier
    {
        return $this->repository->update($supplier, [
            'is_active' => $isActive,
            'updated_by' => $userId,
        ]);
    }
}
