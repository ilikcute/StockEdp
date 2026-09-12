<?php

namespace App\Features\Inventory\Controllers;

use App\Features\Inventory\Actions\CancelStockIssueAction;
use App\Features\Inventory\Actions\CreateStockIssueAction;
use App\Features\Inventory\Actions\PostStockIssueAction;
use App\Features\Inventory\Actions\UpdateStockIssueAction;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Repositories\Contracts\StockIssueRepositoryInterface;
use App\Features\Inventory\Requests\StockIssueRequest;
use App\Features\Inventory\Resources\StockIssueResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StockIssueController extends Controller
{
    public function __construct(
        private readonly StockIssueRepositoryInterface $repository,
        private readonly CreateStockIssueAction $createAction,
        private readonly UpdateStockIssueAction $updateAction,
        private readonly PostStockIssueAction $postAction,
        private readonly CancelStockIssueAction $cancelAction
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', StockIssue::class);

        $filters = $request->only(['status', 'start_date', 'end_date', 'search']);
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $perPage = (int) $request->input('per_page', 15);

        $issues = $this->repository->getPaginatedIssues($filters, $sortField, $sortDirection, $perPage);

        return response()->api(StockIssueResource::collection($issues));
    }

    public function store(StockIssueRequest $request): JsonResponse
    {
        Gate::authorize('create', StockIssue::class);

        $issue = $this->createAction->execute($request->validated(), $request->user()->id);

        return response()->api(new StockIssueResource($issue->load('items.product.unit', 'items.location', 'creator')), 'Success', 201);
    }

    public function show(StockIssue $stockIssue): JsonResponse
    {
        Gate::authorize('view', $stockIssue);

        $issue = $this->repository->findById($stockIssue->id);

        return response()->api(new StockIssueResource($issue));
    }

    public function update(StockIssueRequest $request, StockIssue $stockIssue): JsonResponse
    {
        Gate::authorize('update', $stockIssue);

        $issue = $this->updateAction->execute($stockIssue, $request->validated());

        return response()->api(new StockIssueResource($issue->load('items.product.unit', 'items.location', 'creator')));
    }

    public function post(Request $request, StockIssue $stockIssue): JsonResponse
    {
        Gate::authorize('post', $stockIssue);

        $issue = $this->postAction->execute($stockIssue, $request->user()->id);

        return response()->api(new StockIssueResource($issue->load('items.product.unit', 'items.location', 'creator')));
    }

    public function cancel(Request $request, StockIssue $stockIssue): JsonResponse
    {
        Gate::authorize('cancel', $stockIssue);

        $issue = $this->cancelAction->execute($stockIssue, $request->user()->id);

        return response()->api(new StockIssueResource($issue->load('items.product.unit', 'items.location', 'creator')));
    }
}
