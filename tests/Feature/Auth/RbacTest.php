<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Location\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_assigned_role(): void
    {
        $role = Role::create([
            'code' => RoleCode::WAREHOUSE_OFFICER,
            'name' => 'Petugas Gudang',
        ]);

        $user = User::factory()->create();

        $user->assignRole(RoleCode::WAREHOUSE_OFFICER);

        $this->assertTrue($user->hasRole(RoleCode::WAREHOUSE_OFFICER));
        $this->assertFalse($user->hasRole(RoleCode::ADMIN));
    }

    public function test_user_with_role_has_corresponding_permissions(): void
    {
        $role = Role::create([
            'code' => RoleCode::WAREHOUSE_OFFICER,
            'name' => 'Petugas Gudang',
        ]);

        $permission = Permission::create([
            'code' => PermissionCode::PRODUCTS_VIEW,
            'name' => 'Melihat Daftar Produk',
            'group' => 'products',
        ]);

        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->assignRole(RoleCode::WAREHOUSE_OFFICER);

        $this->assertTrue($user->hasPermissionTo(PermissionCode::PRODUCTS_VIEW));
        $this->assertFalse($user->hasPermissionTo(PermissionCode::PRODUCTS_CREATE));
    }

    public function test_admin_has_all_permissions_implicitly(): void
    {
        Role::create([
            'code' => RoleCode::ADMIN,
            'name' => 'Administrator',
        ]);

        $user = User::factory()->create();
        $user->assignRole(RoleCode::ADMIN);

        $this->assertTrue($user->hasPermissionTo(PermissionCode::PRODUCTS_CREATE));
        $this->assertTrue($user->hasPermissionTo('any.random.permission'));
    }

    public function test_user_location_relationship_works(): void
    {
        $user = User::factory()->create();
        $location = Location::factory()->create();

        DB::table('user_locations')->insert([
            'user_id' => $user->id,
            'location_id' => $location->id,
        ]);

        $exists = DB::table('user_locations')
            ->where('user_id', $user->id)
            ->where('location_id', $location->id)
            ->exists();

        $this->assertTrue($exists);
    }
}
