<?php

namespace App\Features\Product\Repositories\Eloquent;

use App\Features\Product\Models\Product;
use App\Features\Product\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository implements ProductRepositoryInterface
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query()->with(['category', 'unit', 'createdBy', 'updatedBy']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['unit_id'])) {
            $query->where('unit_id', (int) $filters['unit_id']);
        }

        $allowedSorts = ['id', 'sku', 'name', 'minimum_stock', 'is_active', 'created_at'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSorts, true)
            ? $filters['sort_by']
            : 'created_at';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Product
    {
        return Product::with(['category', 'unit', 'createdBy', 'updatedBy'])->find($id);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->fresh(['category', 'unit', 'createdBy', 'updatedBy']);
    }
}
