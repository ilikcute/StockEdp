<?php

namespace Tests\Feature\Release;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoleAndPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_enum_permissions_are_seeded(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $expected = collect(PermissionCode::cases())
            ->map(fn (PermissionCode $permission) => $permission->value)
            ->sort()
            ->values();

        $actual = Permission::query()
            ->pluck('code')
            ->sort()
            ->values();

        $this->assertSame($expected->all(), $actual->all());
    }

    public function test_all_canonical_roles_are_seeded(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $expected = collect(RoleCode::cases())
            ->map(fn (RoleCode $role) => $role->value)
            ->sort()
            ->values();

        $actual = Role::query()
            ->pluck('code')
            ->sort()
            ->values();

        $this->assertSame($expected->all(), $actual->all());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $roleCountInitial = Role::count();
        $permissionCountInitial = Permission::count();
        $pivotCountInitial = DB::table('permission_role')->count();

        // Run seeder second time
        $this->seed(RoleAndPermissionSeeder::class);

        $this->assertEquals($roleCountInitial, Role::count());
        $this->assertEquals($permissionCountInitial, Permission::count());
        $this->assertEquals($pivotCountInitial, DB::table('permission_role')->count());
    }

    public function test_administrator_has_all_permissions(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->assertNotNull($adminRole);

        $expectedPermissionsCount = count(PermissionCode::cases());
        $this->assertEquals($expectedPermissionsCount, $adminRole->permissions()->count());
    }

    public function test_warehouse_and_supervisor_role_permission_matrices(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $warehouseRole = Role::where('code', RoleCode::WAREHOUSE_OFFICER->value)->first();
        $supervisorRole = Role::where('code', RoleCode::INVENTORY_SUPERVISOR->value)->first();

        $this->assertNotNull($warehouseRole);
        $this->assertNotNull($supervisorRole);

        $expectedWarehousePermissions = $warehouseRole->permissions()
            ->pluck('code')
            ->sort()
            ->values()
            ->all();

        $actualWarehousePermissions = $warehouseRole->permissions()
            ->pluck('code')
            ->sort()
            ->values()
            ->all();

        $this->assertSame($expectedWarehousePermissions, $actualWarehousePermissions);

        $expectedSupervisorPermissions = collect([
            PermissionCode::PRODUCTS_VIEW->value,
            PermissionCode::CATEGORIES_VIEW->value,
            PermissionCode::UNITS_VIEW->value,
            PermissionCode::SUPPLIERS_VIEW->value,
            PermissionCode::LOCATIONS_VIEW->value,
            PermissionCode::INVENTORY_BALANCES_VIEW->value,
            PermissionCode::INVENTORY_MOVEMENTS_VIEW->value,
            PermissionCode::STOCK_RECEIPTS_VIEW->value,
            PermissionCode::STOCK_ISSUES_VIEW->value,
            PermissionCode::STOCK_TRANSFERS_VIEW->value,
            PermissionCode::STOCK_ADJUSTMENTS_VIEW->value,
            PermissionCode::STOCK_ADJUSTMENTS_POST->value,
            PermissionCode::STOCK_OPNAMES_VIEW->value,
            PermissionCode::STOCK_OPNAMES_START->value,
            PermissionCode::STOCK_OPNAMES_COMPLETE->value,
            PermissionCode::STOCK_OPNAMES_REOPEN->value,
            PermissionCode::STOCK_OPNAMES_POST->value,
            PermissionCode::STOCK_OPNAMES_CANCEL->value,
            PermissionCode::INVENTORY_OPNAME->value,
            PermissionCode::REPORTS_VIEW->value,
            PermissionCode::REPORTS_INVENTORY_BALANCE_VIEW->value,
            PermissionCode::REPORTS_LOW_STOCK_VIEW->value,
            PermissionCode::REPORTS_STOCK_CARD_VIEW->value,
            PermissionCode::REPORTS_STOCK_RECEIPTS_VIEW->value,
            PermissionCode::REPORTS_STOCK_ISSUES_VIEW->value,
            PermissionCode::REPORTS_STOCK_TRANSFERS_VIEW->value,
            PermissionCode::REPORTS_STOCK_ADJUSTMENTS_VIEW->value,
            PermissionCode::REPORTS_STOCK_OPNAMES_VIEW->value,
            PermissionCode::REPORTS_EXPORT->value,
        ])->sort()->values()->all();

        $actualSupervisorPermissions = $supervisorRole->permissions()
            ->pluck('code')
            ->sort()
            ->values()
            ->all();

        $this->assertSame($expectedSupervisorPermissions, $actualSupervisorPermissions);
    }

    public function test_seeder_metadata_convergence(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        // Intentionally corrupt role name and permission group
        Role::where('code', RoleCode::ADMIN->value)->update(['name' => 'Corrupted Role']);
        Permission::where('code', PermissionCode::PRODUCTS_VIEW->value)->update(['name' => 'Corrupted Permission']);

        // Re-run seeder
        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $perm = Permission::where('code', PermissionCode::PRODUCTS_VIEW->value)->first();

        $this->assertEquals(RoleCode::ADMIN->label(), $adminRole->name);
        $this->assertEquals('Melihat Daftar Produk', $perm->name);
    }
}
