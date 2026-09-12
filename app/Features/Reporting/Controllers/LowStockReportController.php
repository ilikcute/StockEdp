<?php

namespace App\Features\Reporting\Controllers;

use App\Features\Reporting\Requests\LowStockReportRequest;
use App\Features\Reporting\Resources\LowStockReportResource;
use App\Features\Reporting\Services\LowStockReportQueryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LowStockReportController extends Controller
{
    public function __construct(
        private readonly LowStockReportQueryService $service
    ) {}

    public function index(LowStockReportRequest $request): JsonResponse
    {
        $allowedLocationIds = $request->user() ? $request->user()->getAllowedLocationIds() : [];
        $lowStockData = $this->service->getReport($allowedLocationIds, $request->validated());

        return response()->api(
            LowStockReportResource::collection($lowStockData)->response()->getData(true)
        );
    }
}
