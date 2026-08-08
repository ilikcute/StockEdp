<?php

namespace Tests\Feature\Release;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Location\Models\Location;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateInitialAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_admin_command_creates_active_administrator(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $this->artisan('app:create-initial-admin')
            ->expectsQuestion('Nama Pengguna', 'Super Administrator')
            ->expectsQuestion('Username', 'superadmin')
            ->expectsQuestion('Email', 'superadmin@example.com')
            ->expectsQuestion('Password (minimal 12 karakter)', 'AdminPassword123!')
            ->expectsQuestion('Konfirmasi Password', 'AdminPassword123!')
            ->expectsOutput('Akun administrator berhasil dibuat.')
            ->assertExitCode(0);

        $user = User::where('username', 'superadmin')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Super Administrator', $user->name);
        $this->assertEquals('superadmin@example.com', $user->email);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('AdminPassword123!', $user->password));

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->assertTrue($user->roles->contains($adminRole->id));
    }

    public function test_weak_password_is_rejected(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $this->artisan('app:create-initial-admin')
            ->expectsQuestion('Nama Pengguna', 'Super Administrator')
            ->expectsQuestion('Username', 'superadmin')
            ->expectsQuestion('Email', 'superadmin@example.com')
            ->expectsQuestion('Password (minimal 12 karakter)', 'weak')
            ->expectsQuestion('Konfirmasi Password', 'weak')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['username' => 'superadmin']);
    }

    public function test_password_confirmation_mismatch_is_rejected(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $this->artisan('app:create-initial-admin')
            ->expectsQuestion('Nama Pengguna', 'Super Administrator')
            ->expectsQuestion('Username', 'superadmin')
            ->expectsQuestion('Email', 'superadmin@example.com')
            ->expectsQuestion('Password (minimal 12 karakter)', 'AdminPassword123!')
            ->expectsQuestion('Konfirmasi Password', 'DifferentPassword123!')
            ->expectsOutput('Konfirmasi password tidak cocok.')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['username' => 'superadmin']);
    }

    public function test_duplicate_username_is_rejected(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        User::factory()->create(['username' => 'superadmin']);

        $this->artisan('app:create-initial-admin')
            ->expectsQuestion('Nama Pengguna', 'Super Administrator Two')
            ->expectsQuestion('Username', 'superadmin')
            ->expectsQuestion('Email', 'newadmin@example.com')
            ->expectsQuestion('Password (minimal 12 karakter)', 'AdminPassword123!')
            ->expectsQuestion('Konfirmasi Password', 'AdminPassword123!')
            ->expectsOutput('Username sudah digunakan.')
            ->assertExitCode(1);

        $this->assertEquals(1, User::where('username', 'superadmin')->count());
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        User::factory()->create(['email' => 'admin@example.com']);

        $this->artisan('app:create-initial-admin')
            ->expectsQuestion('Nama Pengguna', 'Super Administrator Two')
            ->expectsQuestion('Username', 'admin2')
            ->expectsQuestion('Email', 'admin@example.com')
            ->expectsQuestion('Password (minimal 12 karakter)', 'AdminPassword123!')
            ->expectsQuestion('Konfirmasi Password', 'AdminPassword123!')
            ->expectsOutput('Email sudah digunakan.')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['username' => 'admin2']);
    }

    public function test_command_fails_when_admin_role_missing(): void
    {
        // Do not seed roles
        $this->artisan('app:create-initial-admin')
            ->expectsOutput('Administrator role belum tersedia.')
            ->expectsOutput('Jalankan RoleAndPermissionSeeder terlebih dahulu.')
            ->assertExitCode(1);
    }

    public function test_output_does_not_leak_password_or_hash(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $this->artisan('app:create-initial-admin')
            ->expectsQuestion('Nama Pengguna', 'Super Admin')
            ->expectsQuestion('Username', 'secretadmin')
            ->expectsQuestion('Email', 'secretadmin@example.com')
            ->expectsQuestion('Password (minimal 12 karakter)', 'SuperSecretPass123!')
            ->expectsQuestion('Konfirmasi Password', 'SuperSecretPass123!')
            ->expectsOutput('Akun administrator berhasil dibuat.')
            ->expectsOutput('Nama     : Super Admin')
            ->expectsOutput('Username : secretadmin')
            ->expectsOutput('Email    : secretadmin@example.com')
            ->doesntExpectOutput('SuperSecretPass123!')
            ->assertExitCode(0);

        $user = User::where('username', 'secretadmin')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('SuperSecretPass123!', $user->password));
    }

    public function test_user_creation_rolls_back_when_role_attachment_fails(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        // Register Eloquent listener on User::created to simulate DB/attachment failure inside transaction
        User::created(function () {
            throw new \RuntimeException('Simulated role attachment failure during transaction');
        });

        try {
            $this->artisan('app:create-initial-admin')
                ->expectsQuestion('Nama Pengguna', 'Rollback Admin')
                ->expectsQuestion('Username', 'rollbackadmin')
                ->expectsQuestion('Email', 'rollbackadmin@example.com')
                ->expectsQuestion('Password (minimal 12 karakter)', 'AdminPassword123!')
                ->expectsQuestion('Konfirmasi Password', 'AdminPassword123!');
        } catch (\Throwable $e) {
            $this->assertEquals('Simulated role attachment failure during transaction', $e->getMessage());
        } finally {
            User::flushEventListeners();
        }

        // Assert user was not created and pivot is empty
        $this->assertDatabaseMissing('users', ['username' => 'rollbackadmin']);
        $this->assertEquals(0, DB::table('role_user')->count());
    }

    public function test_initial_admin_receives_all_existing_locations(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $loc1 = Location::factory()->create(['code' => 'LOC-01']);
        $loc2 = Location::factory()->create(['code' => 'LOC-02']);
        $loc3 = Location::factory()->create(['code' => 'LOC-03']);

        $this->artisan('app:create-initial-admin')
            ->expectsQuestion('Nama Pengguna', 'Location Admin')
            ->expectsQuestion('Username', 'locadmin')
            ->expectsQuestion('Email', 'locadmin@example.com')
            ->expectsQuestion('Password (minimal 12 karakter)', 'AdminPassword123!')
            ->expectsQuestion('Konfirmasi Password', 'AdminPassword123!')
            ->assertExitCode(0);

        $user = User::where('username', 'locadmin')->first();
        $this->assertNotNull($user);

        $assignedLocationIds = $user->locations()->pluck('locations.id')->sort()->values()->toArray();
        $expectedLocationIds = [$loc1->id, $loc2->id, $loc3->id];
        sort($expectedLocationIds);

        $this->assertSame($expectedLocationIds, $assignedLocationIds);
    }
}
