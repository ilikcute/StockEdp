<?php

namespace App\Features\Product\Http\Controllers;

use App\Features\Product\Actions\CreateProductAction;
use App\Features\Product\Actions\LookupProductByBarcodeAction;
use App\Features\Product\Actions\SetProductStatusAction;
use App\Features\Product\Actions\UpdateProductAction;
use App\Features\Product\Http\Requests\ProductBarcodeLookupRequest;
use App\Features\Product\Http\Requests\StoreProductRequest;
use App\Features\Product\Http\Requests\UpdateProductRequest;
use App\Features\Product\Http\Resources\ProductResource;
use App\Features\Product\Models\Product;
use App\Features\Product\Repositories\Contracts\ProductRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ProductRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('products.view');

        $filters = $request->only(['search', 'is_active', 'category_id', 'unit_id', 'sort_by', 'sort_order']);
        $perPage = max(1, min(100, (int) $request->get('per_page', 15)));

        $products = $this->repository->getPaginated($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products)->resolve(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function store(StoreProductRequest $request, CreateProductAction $action): JsonResponse
    {
        $product = $action->execute($request->validated(), $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dibuat.',
            'data' => new ProductResource($product->load(['category', 'unit', 'createdBy', 'updatedBy'])),
        ], 201);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $this->authorize('products.view');

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product->load(['category', 'unit', 'createdBy', 'updatedBy'])),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProductAction $action): JsonResponse
    {
        $updatedProduct = $action->execute($product, $request->validated(), $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui.',
            'data' => new ProductResource($updatedProduct),
        ]);
    }

    public function changeStatus(Request $request, Product $product, SetProductStatusAction $action): JsonResponse
    {
        $this->authorize('products.change_status');

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $updatedProduct = $action->execute($product, $request->boolean('is_active'), $request->user()?->id);

        $status = $updatedProduct->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "Produk berhasil {$status}.",
            'data' => new ProductResource($updatedProduct),
        ]);
    }

    public function barcodeLookup(
        ProductBarcodeLookupRequest $request,
        LookupProductByBarcodeAction $action
    ): JsonResponse {
        $result = $action->execute((string) $request->validated('barcode'));

        if ($result['status'] === 'BARCODE_NOT_FOUND') {
            return response()->json([
                'success' => false,
                'code' => 'BARCODE_NOT_FOUND',
                'message' => 'Barcode tidak ditemukan.',
            ], 404);
        }

        if ($result['status'] === 'PRODUCT_INACTIVE') {
            return response()->json([
                'success' => false,
                'code' => 'PRODUCT_INACTIVE',
                'message' => 'Produk dengan barcode ini sudah tidak aktif.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'data' => new ProductResource($result['product']),
        ]);
    }
}
