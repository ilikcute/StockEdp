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
            ['code' => PermissionCode::AUDIT_LOGS_VIEW->value],
            [
                'name' => 'Melihat Log Aktivitas (Audit Trail)',
                'group' => 'audit_logs',
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
        $permission = Permission::where('code', PermissionCode::AUDIT_LOGS_VIEW->value)->first();
        if ($permission) {
            $permission->roles()->detach();
            $permission->delete();
        }
    }
};