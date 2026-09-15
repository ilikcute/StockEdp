<?php

namespace App\Features\Department\Http\Controllers;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Department\Actions\CreateDepartmentAction;
use App\Features\Department\Actions\SetDepartmentStatusAction;
use App\Features\Department\Actions\UpdateDepartmentAction;
use App\Features\Department\Http\Requests\StoreDepartmentRequest;
use App\Features\Department\Http\Requests\UpdateDepartmentRequest;
use App\Features\Department\Http\Resources\DepartmentResource;
use App\Features\Department\Models\Department;
use App\Features\Department\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected DepartmentRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('departments.view');

        $filters = $request->only(['search', 'is_active', 'sort_by', 'sort_order']);
        $perPage = max(1, min(1000, (int) $request->get('per_page', 15)));

        $departments = $this->repository->getPaginated($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => DepartmentResource::collection($departments)->resolve(),
            'meta' => [
                'current_page' => $departments->currentPage(),
                'last_page' => $departments->lastPage(),
                'per_page' => $departments->perPage(),
                'total' => $departments->total(),
                'from' => $departments->firstItem(),
                'to' => $departments->lastItem(),
            ],
        ]);
    }

    public function activeList(Request $request): JsonResponse
    {
        $user = $request->user();
        $canAccess = $user && (
            $user->hasRole('ADMIN') ||
            $user->hasPermissionTo(PermissionCode::DEPARTMENTS_VIEW) ||
            $user->hasPermissionTo(PermissionCode::STOCK_ISSUES_VIEW) ||
            $user->hasPermissionTo(PermissionCode::STOCK_ISSUES_CREATE)
        );

        if (! $canAccess) {
            abort(403, 'Unauthorized action.');
        }

        $departments = $this->repository->getActiveList();

        return response()->json([
            'success' => true,
            'data' => DepartmentResource::collection($departments)->resolve(),
        ]);
    }

    public function store(StoreDepartmentRequest $request, CreateDepartmentAction $action): JsonResponse
    {
        $department = $action->execute($request->validated(), $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Departemen berhasil dibuat.',
            'data' => new DepartmentResource($department->load(['createdBy', 'updatedBy'])),
        ], 201);
    }

    public function show(Request $request, Department $department): JsonResponse
    {
        $this->authorize('departments.view');

        return response()->json([
            'success' => true,
            'data' => new DepartmentResource($department->load(['createdBy', 'updatedBy'])),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department, UpdateDepartmentAction $action): JsonResponse
    {
        $updatedDepartment = $action->execute($department, $request->validated(), $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'Departemen berhasil diperbarui.',
            'data' => new DepartmentResource($updatedDepartment),
        ]);
    }

    public function changeStatus(Request $request, Department $department, SetDepartmentStatusAction $action): JsonResponse
    {
        $this->authorize('departments.change_status');

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $updatedDepartment = $action->execute($department, (bool) $request->boolean('is_active'), $request->user()?->id);

        $status = $updatedDepartment->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "Departemen berhasil {$status}.",
            'data' => new DepartmentResource($updatedDepartment),
        ]);
    }
}
