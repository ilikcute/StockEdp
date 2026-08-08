<?php

namespace App\Console\Commands;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Location\Models\Location;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateInitialAdminCommand extends Command
{
    protected $signature = 'app:create-initial-admin';

    protected $description = 'Membuat akun administrator awal secara aman';

    public function handle(): int
    {
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();

        if (! $adminRole) {
            $this->error('Administrator role belum tersedia.');
            $this->line('Jalankan RoleAndPermissionSeeder terlebih dahulu.');

            return self::FAILURE;
        }

        $name = (string) $this->ask('Nama Pengguna');
        $username = (string) $this->ask('Username');
        $email = (string) $this->ask('Email');
        $password = (string) $this->secret('Password (minimal 12 karakter)');
        $passwordConfirmation = (string) $this->secret('Konfirmasi Password');

        $validator = Validator::make([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username', 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::defaults()->min(12)->mixedCase()->letters()->numbers()->symbols(),
            ],
        ], [
            'username.unique' => 'Username sudah digunakan.',
            'email.unique' => 'Email sudah digunakan.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (strtolower($password) === strtolower($username)) {
            $this->error('Password tidak boleh sama dengan username.');

            return self::FAILURE;
        }

        if (strtolower($password) === strtolower($email)) {
            $this->error('Password tidak boleh sama dengan email.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $username, $email, $password, $adminRole) {
            $user = User::create([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($password),
                'is_active' => true,
            ]);

            $user->roles()->attach($adminRole->id);

            $locationIds = Location::pluck('id')->toArray();
            if (! empty($locationIds)) {
                $user->locations()->attach($locationIds);
            }
        });

        $this->info('Akun administrator berhasil dibuat.');
        $this->line("Nama     : {$name}");
        $this->line("Username : {$username}");
        $this->line("Email    : {$email}");

        return self::SUCCESS;
    }
}
