<?php

namespace App\Features\Store\Http\Controllers;

use App\Features\Store\Actions\CreateStoreAction;
use App\Features\Store\Actions\SetStoreStatusAction;
use App\Features\Store\Actions\UpdateStoreAction;
use App\Features\Store\Http\Requests\StoreStoreRequest;
use App\Features\Store\Http\Requests\UpdateStoreRequest;
use App\Features\Store\Http\Resources\StoreResource;
use App\Features\Store\Models\Store;
use App\Features\Store\Repositories\Contracts\StoreRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected StoreRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('stores.view');

        $filters = $request->only(['search', 'is_active', 'sort_by', 'sort_order']);
        $perPage = max(1, min(1000, (int) $request->get('per_page', 15)));

        $stores = $this->repository->getPaginated($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => StoreResource::collection($stores)->resolve(),
            'meta' => [
                'current_page' => $stores->currentPage(),
                'last_page' => $stores->lastPage(),
                'per_page' => $stores->perPage(),
                'total' => $stores->total(),
                'from' => $stores->firstItem(),
                'to' => $stores->lastItem(),
            ],
        ]);
    }

    public function store(StoreStoreRequest $request, CreateStoreAction $action): JsonResponse
    {
        $store = $action->execute($request->validated(), $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Toko berhasil dibuat.',
            'data' => new StoreResource($store->load(['createdBy', 'updatedBy'])),
        ], 201);
    }

    public function show(Request $request, Store $store): JsonResponse
    {
        $this->authorize('stores.view');

        return response()->json([
            'success' => true,
            'data' => new StoreResource($store->load(['createdBy', 'updatedBy'])),
        ]);
    }

    public function update(UpdateStoreRequest $request, Store $store, UpdateStoreAction $action): JsonResponse
    {
        $updatedStore = $action->execute($store, $request->validated(), $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Toko berhasil diperbarui.',
            'data' => new StoreResource($updatedStore),
        ]);
    }

    public function changeStatus(Request $request, Store $store, SetStoreStatusAction $action): JsonResponse
    {
        $this->authorize('stores.change_status');

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $updatedStore = $action->execute($store, (bool) $request->boolean('is_active'), $request->user()?->id);

        $status = $updatedStore->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "Toko berhasil {$status}.",
            'data' => new StoreResource($updatedStore),
        ]);
    }
}
