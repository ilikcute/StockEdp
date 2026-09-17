<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            [
                'code' => 'product_serials.view',
                'name' => 'Melihat Data Serial Number',
                'group' => 'product_serials',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'product_serials.manage',
                'name' => 'Mengelola Data Serial Number',
                'group' => 'product_serials',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($permissions as $perm) {
            $existing = DB::table('permissions')->where('code', $perm['code'])->first();
            if (! $existing) {
                $permId = DB::table('permissions')->insertGetId($perm);
            } else {
                $permId = $existing->id;
            }

            // Assign product_serials.view to admin, inventory_supervisor, field_technician
            // Assign product_serials.manage to admin, inventory_supervisor
            $roles = DB::table('roles')->whereIn('code', ['admin', 'inventory_supervisor', 'field_technician'])->get();
            foreach ($roles as $role) {
                if ($perm['code'] === 'product_serials.manage' && $role->code === 'field_technician') {
                    continue;
                }

                $hasRolePerm = DB::table('permission_role')
                    ->where('permission_id', $permId)
                    ->where('role_id', $role->id)
                    ->exists();

                if (! $hasRolePerm) {
                    DB::table('permission_role')->insert([
                        'permission_id' => $permId,
                        'role_id' => $role->id,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permIds = DB::table('permissions')
            ->whereIn('code', ['product_serials.view', 'product_serials.manage'])
            ->pluck('id');

        DB::table('permission_role')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('id', $permIds)->delete();
    }
};
