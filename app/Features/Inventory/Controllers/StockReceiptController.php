<?php

namespace App\Features\Inventory\Controllers;

use App\Features\Inventory\Actions\CancelStockReceiptAction;
use App\Features\Inventory\Actions\CreateStockReceiptAction;
use App\Features\Inventory\Actions\PostStockReceiptAction;
use App\Features\Inventory\Actions\UpdateStockReceiptAction;
use App\Features\Inventory\Models\StockReceipt;
use App\Features\Inventory\Repositories\Contracts\StockReceiptRepositoryInterface;
use App\Features\Inventory\Requests\StockReceiptRequest;
use App\Features\Inventory\Resources\StockReceiptResource;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockReceiptController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly StockReceiptRepositoryInterface $repository,
        private readonly CreateStockReceiptAction $createAction,
        private readonly UpdateStockReceiptAction $updateAction,
        private readonly PostStockReceiptAction $postAction,
        private readonly CancelStockReceiptAction $cancelAction
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockReceipt::class);

        $filters = $request->only(['status', 'supplier_id', 'start_date', 'end_date', 'search']);
        $sortField = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_order', 'desc');
        $perPage = $request->input('per_page', 15);

        $receipts = $this->repository->getPaginatedReceipts(
            $filters,
            $sortField,
            $sortDirection,
            (int) $perPage
        );

        return response()->api(
            StockReceiptResource::collection($receipts)->response()->getData(true)
        );
    }

    public function store(StockReceiptRequest $request): JsonResponse
    {
        $this->authorize('create', StockReceipt::class);

        $receipt = $this->createAction->execute($request->validated(), $request->user()->id);

        return response()->api(new StockReceiptResource($receipt->load('items.product.unit', 'items.location', 'supplier', 'creator')), 'Success', 201);
    }

    public function show(StockReceipt $stockReceipt): JsonResponse
    {
        $this->authorize('view', $stockReceipt);

        $stockReceipt->load(['items.product.unit', 'items.location', 'supplier', 'creator']);

        return response()->api(new StockReceiptResource($stockReceipt));
    }

    public function update(StockReceiptRequest $request, StockReceipt $stockReceipt): JsonResponse
    {
        $this->authorize('update', $stockReceipt);

        $receipt = $this->updateAction->execute($stockReceipt, $request->validated());

        return response()->api(new StockReceiptResource($receipt->load('items.product.unit', 'items.location', 'supplier', 'creator')));
    }

    public function post(Request $request, StockReceipt $stockReceipt): JsonResponse
    {
        $this->authorize('post', $stockReceipt);

        $receipt = $this->postAction->execute($stockReceipt, $request->user()->id);

        return response()->api(new StockReceiptResource($receipt->load('items.product.unit', 'items.location', 'supplier', 'creator')));
    }

    public function cancel(Request $request, StockReceipt $stockReceipt): JsonResponse
    {
        $this->authorize('cancel', $stockReceipt);

        $receipt = $this->cancelAction->execute($stockReceipt, $request->user()->id);

        return response()->api(new StockReceiptResource($receipt->load('items.product.unit', 'items.location', 'supplier', 'creator')));
    }
}
