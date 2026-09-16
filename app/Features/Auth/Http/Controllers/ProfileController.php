<?php

namespace App\Features\Auth\Http\Controllers;

use App\Features\Auth\Actions\UpdatePasswordAction;
use App\Features\Auth\Actions\UpdateProfileAction;
use App\Features\Auth\Http\Requests\UpdatePasswordRequest;
use App\Features\Auth\Http\Requests\UpdateProfileRequest;
use App\Features\Auth\Http\Resources\UserResource;
use App\Http\Controllers\Controller;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function update(
        UpdateProfileRequest $request,
        UpdateProfileAction $action
    ): JsonResponse {
        $user = Auth::user();

        $updated = $action->execute(
            $user,
            $request->validated()
        );

        return ApiResponse::success(
            data: new UserResource($updated),
            message: 'Profil berhasil diperbarui.'
        );
    }

    public function updatePassword(
        UpdatePasswordRequest $request,
        UpdatePasswordAction $action
    ): JsonResponse {
        $action->execute(
            Auth::user(),
            $request->validated()['current_password'],
            $request->validated()['password']
        );

        return ApiResponse::success(
            data: new UserResource(Auth::user()->fresh()->load('roles.permissions')),
            message: 'Kata sandi berhasil diperbarui.'
        );
    }
}