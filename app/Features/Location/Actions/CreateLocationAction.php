<?php

namespace App\Features\Location\Actions;

use App\Features\Location\Models\Location;
use App\Features\Location\Repositories\Contracts\LocationRepositoryInterface;

class CreateLocationAction
{
    public function __construct(
        private LocationRepositoryInterface $repository
    ) {}

    public function execute(array $data, ?int $userId = null): Location
    {
        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = true;

        if ($userId) {
            $data['created_by'] = $userId;
        }

        return $this->repository->create($data);
    }
}
