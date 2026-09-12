<?php

namespace App\Features\Category\Actions;

use App\Features\Category\Models\Category;
use App\Features\Category\Repositories\Contracts\CategoryRepositoryInterface;

class SetCategoryStatusAction
{
    public function __construct(
        protected CategoryRepositoryInterface $repository
    ) {}

    public function execute(Category $category, bool $isActive): Category
    {
        return $this->repository->update($category, [
            'is_active' => $isActive,
            'updated_by' => auth()->id(),
        ]);
    }
}
