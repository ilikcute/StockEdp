<?php

namespace App\Features\Audit\Http\Controllers;

use App\Features\Audit\Http\Resources\ActivityLogResource;
use App\Features\Audit\Services\AuditLogService;
use App\Http\Controllers\Controller;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(
        protected AuditLogService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'module',
            'action',
            'user_id',
            'date_from',
            'date_to',
            'per_page',
        ]);

        $logs = $this->service->list($filters);

        return ApiResponse::success(
            data: ActivityLogResource::collection($logs),
            message: 'Daftar log aktivitas berhasil dimuat.',
            meta: [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
        );
    }

    public function modules(): JsonResponse
    {
        return ApiResponse::success(
            data: $this->service->getModules(),
            message: 'Daftar modul aktivitas berhasil dimuat.'
        );
    }
}