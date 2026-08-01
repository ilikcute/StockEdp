<?php

namespace App\Features\Location\Repositories\Contracts;

use App\Features\Location\Models\Location;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface LocationRepositoryInterface
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getAllActive(): Collection;

    public function findById(int $id): ?Location;

    public function create(array $data): Location;

    public function update(Location $location, array $data): Location;
}
