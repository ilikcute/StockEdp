<?php

namespace App\Features\Product\Actions;

use App\Features\Product\Models\Product;
use App\Features\Product\Repositories\Contracts\ProductRepositoryInterface;

class UpdateProductAction
{
    public function __construct(
        protected ProductRepositoryInterface $repository
    ) {}

    public function execute(Product $product, array $data, ?int $userId = null): Product
    {
        if (isset($data['sku'])) {
            $data['sku'] = strtoupper(trim($data['sku']));
        }
        if (array_key_exists('barcode', $data)) {
            $data['barcode'] = ! empty($data['barcode']) ? trim($data['barcode']) : null;
        }
        $data['updated_by'] = $userId;

        return $this->repository->update($product, $data);
    }
}
