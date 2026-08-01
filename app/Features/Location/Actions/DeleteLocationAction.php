<?php

namespace App\Features\Location\Actions;

use App\Features\Location\Models\Location;
use App\Features\Location\Repositories\Contracts\LocationRepositoryInterface;

class DeleteLocationAction
{
    public function __construct(
        protected LocationRepositoryInterface $repository
    ) {}

    public function execute(Location $location): bool
    {
        return $this->repository->delete($location);
    }
}
