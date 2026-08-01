<?php

namespace App\Features\Auth\Http\Controllers;

use App\Features\Auth\Http\Resources\UserResource;
use App\Http\Controllers\Controller;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $user = Auth::user();

        // Load relasi agar data role dan permission terangkut di resource
        $user->load('roles.permissions');

        return ApiResponse::success(
            data: new UserResource($user),
            message: 'Data pengguna berhasil diambil.'
        );
    }
}
