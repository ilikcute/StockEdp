<?php

namespace App\Features\Supplier\Actions;

use App\Features\Supplier\Models\Supplier;
use App\Features\Supplier\Repositories\Contracts\SupplierRepositoryInterface;

class UpdateSupplierAction
{
    public function __construct(
        protected SupplierRepositoryInterface $repository
    ) {}

    public function execute(Supplier $supplier, array $data, ?int $userId = null): Supplier
    {
        if (isset($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }
        $data['updated_by'] = $userId;

        return $this->repository->update($supplier, $data);
    }
}
