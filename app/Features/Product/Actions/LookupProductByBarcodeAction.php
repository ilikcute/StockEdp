<?php

namespace App\Features\Product\Actions;

use App\Features\Product\Models\Product;

class LookupProductByBarcodeAction
{
    /**
     * Look up product by exact barcode string, preserving leading zeros.
     *
     * @return array{product: ?Product, status: string}
     */
    public function execute(string $barcode): array
    {
        $normalizedBarcode = trim($barcode);

        if ($normalizedBarcode === '') {
            return ['product' => null, 'status' => 'BARCODE_NOT_FOUND'];
        }

        $product = Product::with(['category', 'unit'])
            ->where('barcode', $normalizedBarcode)
            ->first();

        if (! $product) {
            return ['product' => null, 'status' => 'BARCODE_NOT_FOUND'];
        }

        if (! $product->is_active) {
            return ['product' => $product, 'status' => 'PRODUCT_INACTIVE'];
        }

        return ['product' => $product, 'status' => 'SUCCESS'];
    }
}
