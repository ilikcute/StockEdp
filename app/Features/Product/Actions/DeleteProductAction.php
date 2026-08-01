<?php

namespace App\Features\Product\Actions;

use App\Features\Product\Models\Product;
use App\Features\Product\Repositories\Contracts\ProductRepositoryInterface;

class DeleteProductAction
{
    public function __construct(
        protected ProductRepositoryInterface $repository
    ) {}

    public function execute(Product $product): bool
    {
        return $this->repository->delete($product);
    }
}
