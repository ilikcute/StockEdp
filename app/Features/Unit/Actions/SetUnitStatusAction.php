<?php

namespace App\Features\Unit\Actions;

use App\Features\Unit\Models\Unit;
use App\Features\Unit\Repositories\Contracts\UnitRepositoryInterface;

class SetUnitStatusAction
{
    public function __construct(
        protected UnitRepositoryInterface $repository
    ) {}

    public function execute(Unit $unit, bool $isActive): Unit
    {
        return $this->repository->update($unit, [
            'is_active' => $isActive,
            'updated_by' => auth()->id(),
        ]);
    }
}
