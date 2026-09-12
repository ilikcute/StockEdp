<?php

namespace App\Features\Inventory\Controllers;

use App\Features\Inventory\Actions\CancelStockAdjustmentAction;
use App\Features\Inventory\Actions\CreateStockAdjustmentAction;
use App\Features\Inventory\Actions\PostStockAdjustmentAction;
use App\Features\Inventory\Actions\UpdateStockAdjustmentAction;
use App\Features\Inventory\Models\StockAdjustment;
use App\Features\Inventory\Repositories\Contracts\StockAdjustmentRepositoryInterface;
use App\Features\Inventory\Requests\CreateStockAdjustmentRequest;
use App\Features\Inventory\Requests\UpdateStockAdjustmentRequest;
use App\Features\Inventory\Resources\StockAdjustmentResource;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly StockAdjustmentRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockAdjustment::class);

        $allowedLocations = $request->user() ? $request->user()->getAllowedLocationIds() : [];

        $filters = $request->only([
            'status',
            'location_id',
            'direction',
            'reason_code',
            'start_date',
            'end_date',
            'search',
        ]);

        $adjustments = $this->repository->getPaginatedAdjustments(
            $allowedLocations,
            $filters,
            $request->input('sort_field', 'id'),
            $request->input('sort_direction', 'desc'),
            (int) $request->input('per_page', 15)
        );

        return response()->api(
            StockAdjustmentResource::collection($adjustments)->response()->getData(true)
        );
    }

    public function store(
        CreateStockAdjustmentRequest $request,
        CreateStockAdjustmentAction $action
    ): JsonResponse {
        $this->authorize('create', StockAdjustment::class);

        $adjustment = $action->execute($request->validated(), $request->user()->id);

        return response()->api(new StockAdjustmentResource($adjustment), 'Stock adjustment berhasil dibuat.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $adjustment = $this->repository->findById($id);

        if (! $adjustment) {
            return response()->api(null, 'Stock adjustment tidak ditemukan.', 404);
        }

        $this->authorize('view', $adjustment);

        return response()->api(new StockAdjustmentResource($adjustment));
    }

    public function update(
        UpdateStockAdjustmentRequest $request,
        int $id,
        UpdateStockAdjustmentAction $action
    ): JsonResponse {
        $adjustment = $this->repository->findById($id);

        if (! $adjustment) {
            return response()->api(null, 'Stock adjustment tidak ditemukan.', 404);
        }

        $this->authorize('update', $adjustment);

        $updatedAdjustment = $action->execute($adjustment, $request->validated(), $request->user()->id);

        return response()->api(new StockAdjustmentResource($updatedAdjustment), 'Stock adjustment berhasil diperbarui.');
    }

    public function post(
        int $id,
        PostStockAdjustmentAction $action,
        Request $request
    ): JsonResponse {
        $adjustment = $this->repository->findById($id);

        if (! $adjustment) {
            return response()->api(null, 'Stock adjustment tidak ditemukan.', 404);
        }

        $this->authorize('post', $adjustment);

        $postedAdjustment = $action->execute($adjustment, $request->user()->id);

        return response()->api(new StockAdjustmentResource($postedAdjustment), 'Stock adjustment berhasil diposting.');
    }

    public function cancel(
        int $id,
        CancelStockAdjustmentAction $action,
        Request $request
    ): JsonResponse {
        $adjustment = $this->repository->findById($id);

        if (! $adjustment) {
            return response()->api(null, 'Stock adjustment tidak ditemukan.', 404);
        }

        $this->authorize('cancel', $adjustment);

        $canceledAdjustment = $action->execute($adjustment, $request->user()->id);

        return response()->api(new StockAdjustmentResource($canceledAdjustment), 'Stock adjustment berhasil dibatalkan.');
    }
}
