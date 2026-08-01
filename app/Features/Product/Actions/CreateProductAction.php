<?php

namespace App\Features\Product\Actions;

use App\Features\Product\Models\Product;
use App\Features\Product\Repositories\Contracts\ProductRepositoryInterface;

class CreateProductAction
{
    public function __construct(
        protected ProductRepositoryInterface $repository
    ) {}

    public function execute(array $data, ?int $userId = null): Product
    {
        $data['sku'] = strtoupper(trim($data['sku']));
        $data['barcode'] = ! empty($data['barcode']) ? trim($data['barcode']) : null;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['minimum_stock'] = $data['minimum_stock'] ?? 0;
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;

        return $this->repository->create($data);
    }
}
