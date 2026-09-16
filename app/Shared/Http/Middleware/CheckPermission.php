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
     * Mendukung single permission atau multiple permissions dengan logika OR (menggunakan variadic, '|', atau ','):
     * Contoh: ->middleware('permission:perm1|perm2') atau ->middleware('permission:perm1,perm2')
     *
     * @return mixed
     *
     * @throws AccessDeniedHttpException
     */
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        // Pastikan user sudah terotentikasi
        if (! $request->user()) {
            throw new AuthenticationException;
        }

        $allPermissions = [];
        foreach ($permissions as $p) {
            foreach (preg_split('/[,|]/', $p) as $single) {
                $trimmed = trim($single);
                if ($trimmed !== '') {
                    $allPermissions[] = $trimmed;
                }
            }
        }

        if (empty($allPermissions)) {
            return $next($request);
        }

        // Pengguna diizinkan jika memiliki minimal salah satu dari permission yang ditentukan
        $hasAccess = false;
        foreach ($allPermissions as $permission) {
            if (Gate::allows($permission)) {
                $hasAccess = true;
                break;
            }
        }

        if (! $hasAccess) {
            throw new AccessDeniedHttpException('Akses ditolak. Anda tidak memiliki izin untuk melakukan tindakan ini.');
        }

        return $next($request);
    }
}
