<?php

namespace App\Features\Auth\Http\Controllers;

use App\Features\Auth\Actions\LogoutUserAction;
use App\Http\Controllers\Controller;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class LogoutController extends Controller
{
    public function __invoke(LogoutUserAction $action): JsonResponse
    {
        $action->execute();

        return ApiResponse::success(
            message: 'Berhasil keluar dari sistem.'
        );
    }
}
