<?php

namespace App\Features\Auth\Actions;

use App\Features\Auth\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthenticateUserAction
{
    public function execute(string $login, string $password): User
    {
        // Cari pengguna berdasarkan email atau username
        $user = User::where('email', $login)
            ->orWhere('username', $login)
            ->first();

        // Gunakan error umum jika user tidak ditemukan atau password salah
        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Kredensial yang diberikan tidak cocok dengan catatan kami.'],
            ]);
        }

        // Cek status aktif
        if (! $user->is_active) {
            Log::warning('Percobaan login ditolak untuk pengguna nonaktif.', [
                'user_id' => $user->id,
                'username' => $user->username,
            ]);

            throw ValidationException::withMessages([
                'login' => ['Kredensial yang diberikan tidak cocok dengan catatan kami.'],
            ]);
        }

        // Catat metadata login berhasil
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        return $user;
    }
}
