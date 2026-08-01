<?php

namespace App\Features\Location\Actions;

use App\Features\Location\Models\Location;
use App\Features\Location\Repositories\Contracts\LocationRepositoryInterface;

class SetLocationStatusAction
{
    public function __construct(
        private LocationRepositoryInterface $repository
    ) {}

    public function execute(Location $location, bool $isActive, ?int $userId = null): Location
    {
        $data = ['is_active' => $isActive];

        if ($userId) {
            $data['updated_by'] = $userId;
        }

        return $this->repository->update($location, $data);
    }
}
