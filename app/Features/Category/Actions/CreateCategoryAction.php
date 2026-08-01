<?php

namespace App\Features\Category\Actions;

use App\Features\Category\Models\Category;
use App\Features\Category\Repositories\Contracts\CategoryRepositoryInterface;

class CreateCategoryAction
{
    public function __construct(
        protected CategoryRepositoryInterface $repository
    ) {}

    public function execute(array $data): Category
    {
        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = isset($data['is_active']) ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) : true;
        $data['created_by'] = auth()->id();

        return $this->repository->create($data);
    }
}
