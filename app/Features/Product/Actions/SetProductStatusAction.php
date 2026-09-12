<?php

namespace App\Features\Product\Actions;

use App\Features\Product\Models\Product;
use App\Features\Product\Repositories\Contracts\ProductRepositoryInterface;

class SetProductStatusAction
{
    public function __construct(
        protected ProductRepositoryInterface $repository
    ) {}

    public function execute(Product $product, bool $isActive, ?int $userId = null): Product
    {
        return $this->repository->update($product, [
            'is_active' => $isActive,
            'updated_by' => $userId,
        ]);
    }
}
