<?php

namespace App\Features\Reporting\Controllers;

use App\Features\Reporting\Requests\StockCardReportRequest;
use App\Features\Reporting\Resources\StockCardReportResource;
use App\Features\Reporting\Services\StockCardReportQueryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StockCardReportController extends Controller
{
    public function __construct(
        private readonly StockCardReportQueryService $service
    ) {}

    public function index(StockCardReportRequest $request): JsonResponse
    {
        $allowedLocationIds = $request->user() ? $request->user()->getAllowedLocationIds() : [];
        $result = $this->service->getReport($allowedLocationIds, $request->validated());

        $movementsResponse = StockCardReportResource::collection($result['movements'])
            ->response()
            ->getData(true);

        $responsePayload = array_merge($movementsResponse, [
            'meta' => array_merge($result['meta'], $movementsResponse['meta'] ?? []),
        ]);

        return response()->api($responsePayload);
    }
}
