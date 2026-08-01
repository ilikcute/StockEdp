<?php

namespace App\Features\Inventory\Controllers;

use App\Features\Inventory\Repositories\Contracts\InventoryBalanceRepositoryInterface;
use App\Features\Inventory\Requests\InventoryBalanceRequest;
use App\Features\Inventory\Resources\InventoryBalanceResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class InventoryBalanceController extends Controller
{
    public function __construct(
        private readonly InventoryBalanceRepositoryInterface $repository
    ) {}

    public function index(InventoryBalanceRequest $request): JsonResponse
    {
        $filters = $request->only(['product_id', 'location_id', 'search']);
        $sortField = $request->input('sort_by', 'id');
        $sortDirection = $request->input('sort_order', 'desc');
        $perPage = $request->input('per_page', 15);

        $balances = $this->repository->getPaginatedBalances(
            $filters,
            $sortField,
            $sortDirection,
            (int) $perPage
        );

        return response()->api(
            InventoryBalanceResource::collection($balances)->response()->getData(true)
        );
    }
}
