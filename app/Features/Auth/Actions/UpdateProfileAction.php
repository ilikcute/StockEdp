<?php

namespace App\Features\Auth\Actions;

use App\Features\Auth\Models\User;
use Illuminate\Validation\ValidationException;

class UpdateProfileAction
{
    public function execute(User $user, array $data): User
    {
        $emailTaken = User::query()
            ->where('email', $data['email'])
            ->where('id', '!=', $user->id)
            ->exists();

        if ($emailTaken) {
            throw ValidationException::withMessages([
                'email' => ['Alamat email sudah digunakan akun lain.'],
            ]);
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        return $user->fresh()->load('roles.permissions');
    }
}