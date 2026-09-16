<?php

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['code' => PermissionCode::INVENTORY_RECONCILE->value],
            [
                'name' => 'Rekonsiliasi & Hitung Ulang Saldo Stok',
                'group' => 'inventory',
            ]
        );

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        if ($adminRole && $permission) {
            $adminRole->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::where('code', PermissionCode::INVENTORY_RECONCILE->value)->first();
        if ($permission) {
            $permission->roles()->detach();
            $permission->delete();
        }
    }
};
