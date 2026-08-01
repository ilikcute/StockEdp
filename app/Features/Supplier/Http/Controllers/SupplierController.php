<?php

namespace App\Features\Supplier\Http\Controllers;

use App\Features\Supplier\Actions\CreateSupplierAction;
use App\Features\Supplier\Actions\SetSupplierStatusAction;
use App\Features\Supplier\Actions\UpdateSupplierAction;
use App\Features\Supplier\Http\Requests\StoreSupplierRequest;
use App\Features\Supplier\Http\Requests\UpdateSupplierRequest;
use App\Features\Supplier\Http\Resources\SupplierResource;
use App\Features\Supplier\Models\Supplier;
use App\Features\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected SupplierRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('suppliers.view');

        $filters = $request->only(['search', 'is_active', 'sort_by', 'sort_order']);
        $perPage = max(1, min(100, (int) $request->get('per_page', 15)));

        $suppliers = $this->repository->getPaginated($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => SupplierResource::collection($suppliers)->resolve(),
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
            ],
        ]);
    }

    public function store(StoreSupplierRequest $request, CreateSupplierAction $action): JsonResponse
    {
        $supplier = $action->execute($request->validated(), $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil dibuat.',
            'data' => new SupplierResource($supplier->load(['createdBy', 'updatedBy'])),
        ], 201);
    }

    public function show(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('suppliers.view');

        return response()->json([
            'success' => true,
            'data' => new SupplierResource($supplier->load(['createdBy', 'updatedBy'])),
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier, UpdateSupplierAction $action): JsonResponse
    {
        $updatedSupplier = $action->execute($supplier, $request->validated(), $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil diperbarui.',
            'data' => new SupplierResource($updatedSupplier),
        ]);
    }

    public function changeStatus(Request $request, Supplier $supplier, SetSupplierStatusAction $action): JsonResponse
    {
        $this->authorize('suppliers.change_status');

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $updatedSupplier = $action->execute($supplier, (bool) $request->boolean('is_active'), $request->user()?->id);

        $status = $updatedSupplier->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "Supplier berhasil {$status}.",
            'data' => new SupplierResource($updatedSupplier),
        ]);
    }
}
