<?php

namespace App\Features\Unit\Http\Controllers;

use App\Features\Unit\Actions\CreateUnitAction;
use App\Features\Unit\Actions\SetUnitStatusAction;
use App\Features\Unit\Actions\UpdateUnitAction;
use App\Features\Unit\Http\Requests\StoreUnitRequest;
use App\Features\Unit\Http\Requests\UpdateUnitRequest;
use App\Features\Unit\Http\Resources\UnitResource;
use App\Features\Unit\Models\Unit;
use App\Features\Unit\Repositories\Contracts\UnitRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected UnitRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('units.view');

        $filters = $request->only(['search', 'is_active', 'sort_by', 'sort_order']);
        $perPage = max(1, min(100, (int) $request->get('per_page', 15)));

        $units = $this->repository->getPaginated($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => UnitResource::collection($units)->resolve(),
            'meta' => [
                'current_page' => $units->currentPage(),
                'last_page' => $units->lastPage(),
                'per_page' => $units->perPage(),
                'total' => $units->total(),
            ],
        ]);
    }

    public function store(StoreUnitRequest $request, CreateUnitAction $action): JsonResponse
    {
        $unit = $action->execute($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Satuan berhasil dibuat.',
            'data' => new UnitResource($unit->load(['createdBy', 'updatedBy'])),
        ], 201);
    }

    public function show(Request $request, Unit $unit): JsonResponse
    {
        $this->authorize('units.view');

        return response()->json([
            'success' => true,
            'data' => new UnitResource($unit->load(['createdBy', 'updatedBy'])),
        ]);
    }

    public function update(UpdateUnitRequest $request, Unit $unit, UpdateUnitAction $action): JsonResponse
    {
        $updatedUnit = $action->execute($unit, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Satuan berhasil diperbarui.',
            'data' => new UnitResource($updatedUnit),
        ]);
    }

    public function changeStatus(Request $request, Unit $unit, SetUnitStatusAction $action): JsonResponse
    {
        $this->authorize('units.change_status');

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $updatedUnit = $action->execute($unit, (bool) $request->boolean('is_active'));

        $status = $updatedUnit->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "Satuan berhasil {$status}.",
            'data' => new UnitResource($updatedUnit),
        ]);
    }
}
