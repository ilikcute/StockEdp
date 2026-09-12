<?php

namespace App\Shared\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     *
     * @throws AccessDeniedHttpException
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        // Pastikan user sudah terotentikasi
        if (! $request->user()) {
            throw new AuthenticationException;
        }

        // Jalankan pengecekan melalui Gate Laravel
        if (Gate::denies($permission)) {
            throw new AccessDeniedHttpException('Akses ditolak. Anda tidak memiliki izin untuk melakukan tindakan ini.');
        }

        return $next($request);
    }
}
