<?php

namespace App\Features\StoreAllocation\Http\Controllers;

use App\Features\StoreAllocation\Actions\CreateStoreAllocationAction;
use App\Features\StoreAllocation\Http\Requests\StoreAllocationRequest;
use App\Features\StoreAllocation\Http\Resources\StoreAllocationResource;
use App\Features\StoreAllocation\Models\StoreAllocation;
use App\Features\StoreAllocation\Repositories\Contracts\StoreAllocationRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreAllocationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected StoreAllocationRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('store_allocations.view');

        $filters = $request->only([
            'search',
            'store_id',
            'technician_user_id',
            'technician_location_id',
            'date_from',
            'date_to',
            'sort_by',
            'sort_order',
        ]);

        $perPage = max(1, min(1000, (int) $request->get('per_page', 15)));

        $allocations = $this->repository->getPaginated($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => StoreAllocationResource::collection($allocations)->resolve(),
            'meta' => [
                'current_page' => $allocations->currentPage(),
                'last_page' => $allocations->lastPage(),
                'per_page' => $allocations->perPage(),
                'total' => $allocations->total(),
                'from' => $allocations->firstItem(),
                'to' => $allocations->lastItem(),
            ],
        ]);
    }

    public function store(StoreAllocationRequest $request, CreateStoreAllocationAction $action): JsonResponse
    {
        $allocation = $action->execute($request->validated(), $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Alokasi penggantian unit toko berhasil diproses.',
            'data' => new StoreAllocationResource($allocation),
        ], 201);
    }

    public function show(Request $request, StoreAllocation $storeAllocation): JsonResponse
    {
        $this->authorize('store_allocations.view');

        $storeAllocation->load([
            'technician',
            'location',
            'store',
            'creator',
            'items.product',
            'items.pulledProduct',
        ]);

        return response()->json([
            'success' => true,
            'data' => new StoreAllocationResource($storeAllocation),
        ]);
    }
}
