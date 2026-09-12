<?php

namespace App\Features\Auth\Http\Controllers;

use App\Features\Auth\Actions\AuthenticateUserAction;
use App\Features\Auth\Http\Requests\LoginRequest;
use App\Features\Auth\Http\Resources\UserResource;
use App\Http\Controllers\Controller;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, AuthenticateUserAction $action): JsonResponse
    {
        $request->ensureIsNotRateLimited();

        try {
            $user = $action->execute(
                $request->input('login'),
                $request->input('password')
            );

            // Log masuk menggunakan Session Guard web
            Auth::guard('web')->login($user, $request->boolean('remember'));

            // Regenerasi session untuk mencegah session fixation
            $request->session()->regenerate();

            // Clear rate-limiter
            $request->clearThrottle();

            // Ambil relasi role dan permission agar terikut di resource
            $user->load('roles.permissions');

            return ApiResponse::success(
                data: [
                    'user' => new UserResource($user),
                ],
                message: 'Login berhasil.'
            );
        } catch (ValidationException $e) {
            $request->hitThrottle();
            throw $e;
        }
    }
}
