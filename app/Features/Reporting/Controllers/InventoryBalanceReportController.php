<?php

namespace App\Features\Reporting\Controllers;

use App\Features\Reporting\Requests\InventoryBalanceReportRequest;
use App\Features\Reporting\Resources\InventoryBalanceReportResource;
use App\Features\Reporting\Services\InventoryBalanceReportQueryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class InventoryBalanceReportController extends Controller
{
    public function __construct(
        private readonly InventoryBalanceReportQueryService $service
    ) {}

    public function index(InventoryBalanceReportRequest $request): JsonResponse
    {
        $allowedLocationIds = $request->user() ? $request->user()->getAllowedLocationIds() : [];
        $balances = $this->service->getReport($allowedLocationIds, $request->validated());

        return response()->api(
            InventoryBalanceReportResource::collection($balances)->response()->getData(true)
        );
    }
}
