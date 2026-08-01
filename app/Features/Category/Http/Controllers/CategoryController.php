<?php

namespace App\Features\Category\Http\Controllers;

use App\Features\Category\Actions\CreateCategoryAction;
use App\Features\Category\Actions\SetCategoryStatusAction;
use App\Features\Category\Actions\UpdateCategoryAction;
use App\Features\Category\Http\Requests\StoreCategoryRequest;
use App\Features\Category\Http\Requests\UpdateCategoryRequest;
use App\Features\Category\Http\Resources\CategoryResource;
use App\Features\Category\Models\Category;
use App\Features\Category\Repositories\Contracts\CategoryRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected CategoryRepositoryInterface $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('categories.view');

        $filters = $request->only(['search', 'is_active', 'sort_by', 'sort_order']);
        $perPage = max(1, min(100, (int) $request->get('per_page', 15)));

        $categories = $this->repository->getPaginated($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories)->resolve(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    public function store(StoreCategoryRequest $request, CreateCategoryAction $action): JsonResponse
    {
        $category = $action->execute($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dibuat.',
            'data' => new CategoryResource($category->load(['createdBy', 'updatedBy'])),
        ], 201);
    }

    public function show(Request $request, Category $category): JsonResponse
    {
        $this->authorize('categories.view');

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category->load(['createdBy', 'updatedBy'])),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category, UpdateCategoryAction $action): JsonResponse
    {
        $updatedCategory = $action->execute($category, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui.',
            'data' => new CategoryResource($updatedCategory),
        ]);
    }

    public function changeStatus(Request $request, Category $category, SetCategoryStatusAction $action): JsonResponse
    {
        $this->authorize('categories.change_status');

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $updatedCategory = $action->execute($category, (bool) $request->boolean('is_active'));

        $status = $updatedCategory->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "Kategori berhasil {$status}.",
            'data' => new CategoryResource($updatedCategory),
        ]);
    }
}
