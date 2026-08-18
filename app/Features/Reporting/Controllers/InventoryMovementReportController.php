<?php

namespace App\Features\Reporting\Controllers;

use App\Features\Reporting\Requests\InventoryMovementReportRequest;
use App\Features\Reporting\Services\InventoryMovementReportQueryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class InventoryMovementReportController extends Controller
{
    public function __construct(
        private readonly InventoryMovementReportQueryService $service
    ) {}

    public function index(InventoryMovementReportRequest $request): JsonResponse
    {
        $allowedLocationIds = $request->user() ? $request->user()->getAllowedLocationIds() : [];
        $reportData = $this->service->getReport($allowedLocationIds, $request->validated());

        $paginator = $reportData['items'];
        $meta = $reportData['meta'];

        $meta['pagination'] = [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Laporan pergerakan persediaan berhasil dimuat.',
            'data' => $paginator->items(),
            'meta' => $meta,
        ]);
    }
}
