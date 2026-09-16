<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan indeks single-column pada inventory_balances.location_id.
     *
     * Index unik komposit prod_loc_cond_unique (product_id, location_id, condition)
     * ber-prefix product_id sehingga query yang hanya menyaring location_id
     * (laporan saldo per lokasi, low-stock, dashboard, replenishment) melakukan
     * full scan. Index ini menutupi celah tersebut.
     */
    public function up(): void
    {
        Schema::table('inventory_balances', function (Blueprint $table) {
            if (! Schema::hasIndex('inventory_balances', 'idx_balances_location_id')) {
                $table->index('location_id', 'idx_balances_location_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventory_balances')) {
            return;
        }

        try {
            Schema::table('inventory_balances', function (Blueprint $table) {
                if (Schema::hasIndex('inventory_balances', 'idx_balances_location_id')) {
                    $table->dropIndex('idx_balances_location_id');
                }
            });
        } catch (\Throwable $e) {
            Schema::table('inventory_balances', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropIndex('idx_balances_location_id');
                $table->foreign('location_id')->references('id')->on('locations')->restrictOnDelete();
            });
        }
    }
};