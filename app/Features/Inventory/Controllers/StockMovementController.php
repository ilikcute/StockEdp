<?php

namespace App\Features\Inventory\Controllers;

use App\Features\Inventory\Models\StockMovement;
use App\Features\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Features\Inventory\Requests\StockMovementRequest;
use App\Features\Inventory\Resources\StockMovementResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $repository
    ) {}

    public function index(StockMovementRequest $request): JsonResponse
    {
        $filters = $request->only(['product_id', 'location_id', 'movement_type', 'start_date', 'end_date', 'search']);
        $sortField = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_order', 'desc');
        $perPage = $request->input('per_page', 15);

        $movements = $this->repository->getPaginatedMovements(
            $filters,
            $sortField,
            $sortDirection,
            (int) $perPage
        );

        return response()->api(
            StockMovementResource::collection($movements)->response()->getData(true)
        );
    }

    public function show(Request $request, StockMovement $stockMovement): JsonResponse
    {
        $stockMovement->load(['product', 'location', 'creator']);

        return response()->api(new StockMovementResource($stockMovement));
    }
}
