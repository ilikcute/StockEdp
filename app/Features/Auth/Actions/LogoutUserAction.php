<?php

namespace App\Features\Auth\Actions;

use Illuminate\Support\Facades\Auth;

class LogoutUserAction
{
    public function execute(): void
    {
        $user = Auth::user();
        if ($user) {
            app(\App\Features\Audit\Services\ActivityLogger::class)->record(
                module: 'auth',
                action: 'logout',
                description: "Pengguna {$user->name} ({$user->username}) logout dari sistem",
                subject: $user,
                userId: $user->id
            );
        }

        Auth::guard('web')->logout();

        request()->session()->invalidate();

        request()->session()->regenerateToken();
    }
}
