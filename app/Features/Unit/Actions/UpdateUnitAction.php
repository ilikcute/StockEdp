<?php

namespace App\Features\Unit\Actions;

use App\Features\Unit\Models\Unit;
use App\Features\Unit\Repositories\Contracts\UnitRepositoryInterface;

class UpdateUnitAction
{
    public function __construct(
        protected UnitRepositoryInterface $repository
    ) {}

    public function execute(Unit $unit, array $data): Unit
    {
        if (isset($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }
        $data['updated_by'] = auth()->id();

        return $this->repository->update($unit, $data);
    }
}
