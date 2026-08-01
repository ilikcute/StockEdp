<?php

namespace App\Features\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $databaseStatus = 'ok';

        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $databaseStatus = 'error';
        }

        $isHealthy = $databaseStatus === 'ok';

        return ApiResponse::success(
            data: [
                'status' => $isHealthy ? 'healthy' : 'degraded',
                'services' => [
                    'database' => $databaseStatus,
                ],
                'timestamp' => now()->toIso8601String(),
            ],
            message: $isHealthy
                ? 'Layanan berjalan dengan baik.'
                : 'Layanan berjalan dengan gangguan.',
            status: $isHealthy ? 200 : 503,
        );
    }
}
