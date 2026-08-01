<?php

namespace App\Features\Auth\Actions;

use Illuminate\Support\Facades\Auth;

class LogoutUserAction
{
    public function execute(): void
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();

        request()->session()->regenerateToken();
    }
}
