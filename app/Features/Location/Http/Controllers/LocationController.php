<?php

namespace App\Features\Location\Http\Controllers;

use App\Features\Location\Actions\CreateLocationAction;
use App\Features\Location\Actions\SetLocationStatusAction;
use App\Features\Location\Actions\UpdateLocationAction;
use App\Features\Location\Http\Requests\StoreLocationRequest;
use App\Features\Location\Http\Requests\UpdateLocationRequest;
use App\Features\Location\Http\Resources\LocationResource;
use App\Features\Location\Models\Location;
use App\Features\Location\Repositories\Contracts\LocationRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected LocationRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('locations.view');

        $filters = $request->only(['search', 'is_active', 'sort_by', 'sort_order']);
        $perPage = max(1, min(100, (int) $request->get('per_page', 15)));

        $locations = $this->repository->getPaginated($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => LocationResource::collection($locations)->resolve(),
            'meta' => [
                'current_page' => $locations->currentPage(),
                'last_page' => $locations->lastPage(),
                'per_page' => $locations->perPage(),
                'total' => $locations->total(),
            ],
        ]);
    }

    public function store(StoreLocationRequest $request, CreateLocationAction $action): JsonResponse
    {
        $location = $action->execute($request->validated(), $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Lokasi berhasil dibuat.',
            'data' => new LocationResource($location->load(['createdBy', 'updatedBy'])),
        ], 201);
    }

    public function show(Request $request, Location $location): JsonResponse
    {
        $this->authorize('locations.view');

        return response()->json([
            'success' => true,
            'data' => new LocationResource($location->load(['createdBy', 'updatedBy'])),
        ]);
    }

    public function update(UpdateLocationRequest $request, Location $location, UpdateLocationAction $action): JsonResponse
    {
        $updatedLocation = $action->execute($location, $request->validated(), $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Lokasi berhasil diperbarui.',
            'data' => new LocationResource($updatedLocation),
        ]);
    }

    public function changeStatus(Request $request, Location $location, SetLocationStatusAction $action): JsonResponse
    {
        $this->authorize('locations.change_status');

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $updatedLocation = $action->execute($location, (bool) $request->boolean('is_active'), $request->user()?->id);

        $status = $updatedLocation->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "Lokasi berhasil {$status}.",
            'data' => new LocationResource($updatedLocation),
        ]);
    }
}
