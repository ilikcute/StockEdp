<?php

namespace App\Features\Unit\Actions;

use App\Features\Unit\Models\Unit;
use App\Features\Unit\Repositories\Contracts\UnitRepositoryInterface;

class CreateUnitAction
{
    public function __construct(
        protected UnitRepositoryInterface $repository
    ) {}

    public function execute(array $data): Unit
    {
        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = $data['is_active'] ?? true;
        $data['created_by'] = auth()->id();

        return $this->repository->create($data);
    }
}
