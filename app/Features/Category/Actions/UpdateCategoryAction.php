<?php

namespace App\Features\Category\Actions;

use App\Features\Category\Models\Category;
use App\Features\Category\Repositories\Contracts\CategoryRepositoryInterface;

class UpdateCategoryAction
{
    public function __construct(
        protected CategoryRepositoryInterface $repository
    ) {}

    public function execute(Category $category, array $data): Category
    {
        if (isset($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }
        $data['updated_by'] = auth()->id();

        return $this->repository->update($category, $data);
    }
}
