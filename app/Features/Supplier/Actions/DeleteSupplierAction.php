<?php

namespace App\Features\Supplier\Actions;

use App\Features\Supplier\Models\Supplier;
use App\Features\Supplier\Repositories\Contracts\SupplierRepositoryInterface;

class DeleteSupplierAction
{
    public function __construct(
        protected SupplierRepositoryInterface $repository
    ) {}

    public function execute(Supplier $supplier): bool
    {
        return $this->repository->delete($supplier);
    }
}
