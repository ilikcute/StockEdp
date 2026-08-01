<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Daftarkan route pengujian sementara yang dilindungi middleware 'permission'
        Route::middleware(['api', 'permission:'.PermissionCode::PRODUCTS_CREATE->value])
            ->get('/api/v1/__test/protected-action', fn () => response()->json(['success' => true]));
    }

    public function test_unauthenticated_user_cannot_access_protected_route(): void
    {
        $response = $this->getJson('/api/v1/__test/protected-action');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_without_permission_receives_403_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user, 'web')->getJson('/api/v1/__test/protected-action');

        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Akses ditolak. Anda tidak memiliki izin untuk melakukan tindakan ini.');
    }

    public function test_authenticated_user_with_permission_can_access_route(): void
    {
        $role = Role::create([
            'code' => RoleCode::WAREHOUSE_OFFICER,
            'name' => 'Petugas Gudang',
        ]);

        $permission = Permission::create([
            'code' => PermissionCode::PRODUCTS_CREATE,
            'name' => 'Mengelola Produk',
            'group' => 'products',
        ]);

        $role->permissions()->attach($permission);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(RoleCode::WAREHOUSE_OFFICER);

        $response = $this->actingAs($user, 'web')->getJson('/api/v1/__test/protected-action');

        $response
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_bypasses_permission_check(): void
    {
        Role::create([
            'code' => RoleCode::ADMIN,
            'name' => 'Administrator',
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(RoleCode::ADMIN);

        $response = $this->actingAs($user, 'web')->getJson('/api/v1/__test/protected-action');

        $response
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_permission_not_registered_is_denied(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        // Permission yang tidak terdaftar/tidak dimiliki oleh user mana pun
        $this->assertFalse($user->hasPermissionTo('nonexistent.permission'));
    }

    public function test_permission_via_multiple_roles_works(): void
    {
        $role1 = Role::create(['code' => RoleCode::WAREHOUSE_OFFICER, 'name' => 'Petugas Gudang']);
        $role2 = Role::create(['code' => RoleCode::INVENTORY_SUPERVISOR, 'name' => 'Supervisor']);

        $perm1 = Permission::create(['code' => PermissionCode::PRODUCTS_VIEW, 'name' => 'View', 'group' => 'products']);
        $perm2 = Permission::create(['code' => PermissionCode::STOCK_ADJUSTMENTS_VIEW, 'name' => 'Adjust View', 'group' => 'stock_adjustments']);

        $role1->permissions()->attach($perm1);
        $role2->permissions()->attach($perm2);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(RoleCode::WAREHOUSE_OFFICER, RoleCode::INVENTORY_SUPERVISOR);

        $this->assertTrue($user->hasPermissionTo(PermissionCode::PRODUCTS_VIEW));
        $this->assertTrue($user->hasPermissionTo(PermissionCode::STOCK_ADJUSTMENTS_VIEW));
        $this->assertFalse($user->hasPermissionTo(PermissionCode::PRODUCTS_CREATE));
    }

    public function test_multiple_roles_do_not_trigger_query_per_role(): void
    {
        // Setup 2 role berbeda
        $role1 = Role::create(['code' => RoleCode::WAREHOUSE_OFFICER, 'name' => 'Gudang']);
        $role2 = Role::create(['code' => RoleCode::INVENTORY_SUPERVISOR, 'name' => 'Supervisor']);

        $perm = Permission::create(['code' => PermissionCode::PRODUCTS_VIEW, 'name' => 'View', 'group' => 'products']);
        $role1->permissions()->attach($perm);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(RoleCode::WAREHOUSE_OFFICER, RoleCode::INVENTORY_SUPERVISOR);

        // Refresh untuk menghapus cache relasi internal pada model instance ini
        $user = User::find($user->id);

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Pengecekan permission pertama kali
        $hasPerm = $user->hasPermissionTo(PermissionCode::PRODUCTS_VIEW);

        $this->assertTrue($hasPerm);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Kueri yang diharapkan: 1 untuk load roles + 1 untuk load permissions. Total = 2 kueri.
        // Jika ada N+1 query, jumlah kueri akan menjadi 1 (roles) + 3 (per-role permissions) = 4 kueri.
        $this->assertEquals(2, $queryCount, 'Otorisasi harus melakukan tepat 2 kueri (eager loading roles dan permissions), tidak boleh N+1.');

        // Pengecekan kedua kali pada instance user yang sama tidak boleh memicu kueri SQL baru sama sekali (cached)
        DB::flushQueryLog();
        $user->hasPermissionTo(PermissionCode::PRODUCTS_VIEW);
        $this->assertEmpty(DB::getQueryLog(), 'Pengecekan permission berulang pada instance User yang sama tidak boleh memicu query baru.');
    }
}
