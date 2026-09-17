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
        // 1. Dapatkan atau buat permission store_allocations.create_own jika belum ada
        $createOwnPerm = DB::table('permissions')->where('code', 'store_allocations.create_own')->first();
        if (! $createOwnPerm) {
            $createOwnId = DB::table('permissions')->insertGetId([
                'code' => 'store_allocations.create_own',
                'name' => 'Membuat Alokasi Toko Sendiri (Teknisi)',
                'group' => 'store_allocations',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $createOwnId = $createOwnPerm->id;
        }

        // 2. Cari permission lama store_allocations.create
        $oldPerm = DB::table('permissions')->where('code', 'store_allocations.create')->first();

        if ($oldPerm) {
            // Ambil semua role yang memiliki permission lama
            $roleIds = DB::table('permission_role')
                ->where('permission_id', $oldPerm->id)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                // Pastikan role tersebut memiliki store_allocations.create_own
                $exists = DB::table('permission_role')
                    ->where('permission_id', $createOwnId)
                    ->where('role_id', $roleId)
                    ->exists();

                if (! $exists) {
                    DB::table('permission_role')->insert([
                        'permission_id' => $createOwnId,
                        'role_id' => $roleId,
                    ]);
                }
            }

            // Hapus relasi permission lama dari permission_role
            DB::table('permission_role')->where('permission_id', $oldPerm->id)->delete();

            // Hapus permission lama dari tabel permissions
            DB::table('permissions')->where('id', $oldPerm->id)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $oldPerm = DB::table('permissions')->where('code', 'store_allocations.create')->first();
        if (! $oldPerm) {
            DB::table('permissions')->insert([
                'code' => 'store_allocations.create',
                'name' => 'Membuat Alokasi Toko',
                'group' => 'store_allocations',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
