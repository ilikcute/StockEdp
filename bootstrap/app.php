<?php

use App\Shared\Exceptions\DomainException;
use App\Shared\Http\Middleware\CheckPermission;
use App\Shared\Http\Responses\ApiResponse;
use App\Shared\Providers\FeatureRouteServiceProvider;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        FeatureRouteServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->alias([
            'permission' => CheckPermission::class,
        ]);
        $middleware->redirectGuestsTo(fn (Request $request) => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: 'Data yang dikirim tidak valid.',
                status: 422,
                errors: $exception->errors(),
            );
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: 'Unauthenticated.',
                status: 401
            );
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: $exception->getMessage() && $exception->getMessage() !== 'This action is unauthorized.'
                    ? $exception->getMessage()
                    : 'Anda tidak memiliki hak akses untuk melakukan tindakan ini atau mengakses lokasi ini.',
                status: 403,
            );
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: $exception->getMessage() && $exception->getMessage() !== 'This action is unauthorized.'
                    ? $exception->getMessage()
                    : 'Anda tidak memiliki hak akses untuk melakukan tindakan ini atau mengakses lokasi ini.',
                status: 403,
            );
        });

        $exceptions->render(function (TooManyRequestsHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: 'Terlalu banyak permintaan. Silakan coba kembali nanti.',
                status: 429,
            )->withHeaders($exception->getHeaders());
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: 'Terlalu banyak permintaan. Silakan coba kembali nanti.',
                status: 429,
            )->withHeaders($exception->getHeaders());
        });

        $exceptions->render(function (DomainException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                message: $exception->getMessage(),
                status: $exception->status(),
                errors: $exception->errors(),
            );
        });

        $exceptions->render(function (HttpResponseException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return $exception->getResponse();
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            Log::error('API Exception: '.$exception->getMessage(), [
                'exception' => $exception,
                'url' => $request->fullUrl(),
                'user_id' => $request->user()?->id,
            ]);

            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            $message = match (true) {
                $status === 429 => 'Terlalu banyak permintaan. Silakan coba kembali nanti.',
                $status >= 500 => 'Terjadi kesalahan pada server.',
                $exception->getMessage() !== '' => $exception->getMessage(),
                default => 'Permintaan tidak dapat diproses.',
            };

            return ApiResponse::error(
                message: $message,
                status: $status,
            );
        });
    })->create();
