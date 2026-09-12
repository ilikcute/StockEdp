<?php

namespace App\Features\Location\Actions;

use App\Features\Location\Models\Location;
use App\Features\Location\Repositories\Contracts\LocationRepositoryInterface;

class UpdateLocationAction
{
    public function __construct(
        private LocationRepositoryInterface $repository
    ) {}

    public function execute(Location $location, array $data, ?int $userId = null): Location
    {
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        if ($userId) {
            $data['updated_by'] = $userId;
        }

        return $this->repository->update($location, $data);
    }
}
