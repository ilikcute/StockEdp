<?php

namespace App\Features\Category\Repositories\Contracts;

use App\Features\Category\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getAllActive(): Collection;

    public function findById(int $id): ?Category;

    public function create(array $data): Category;

    public function update(Category $category, array $data): Category;
}
