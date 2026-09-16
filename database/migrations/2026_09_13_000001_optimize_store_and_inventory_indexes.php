<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Hapus indeks redundan pada stores dan inventory_balances,
     * serta optimalkan susunan indeks komposit pada store_allocations.
     */
    public function up(): void
    {
        // 1. Hapus redundant index pada tabel stores (stores_code_index terduplikasi oleh stores_code_unique)
        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasIndex('stores', 'stores_code_index')) {
                $table->dropIndex('stores_code_index');
            }
        });

        // 2. Hapus redundant index pada tabel inventory_balances (idx_balances_product_id tertutup oleh composite unique prod_loc_cond_unique)
        Schema::table('inventory_balances', function (Blueprint $table) {
            if (Schema::hasIndex('inventory_balances', 'idx_balances_product_id')) {
                $table->dropIndex('idx_balances_product_id');
            }
        });

        // 3. Optimasi indeks komposit store_allocations (Equality before Range: store_id, allocated_at)
        Schema::table('store_allocations', function (Blueprint $table) {
            if (Schema::hasIndex('store_allocations', 'store_allocations_allocated_at_store_id_index')) {
                $table->dropIndex('store_allocations_allocated_at_store_id_index');
            }
            if (! Schema::hasIndex('store_allocations', 'idx_store_alloc_store_date')) {
                $table->index(['store_id', 'allocated_at'], 'idx_store_alloc_store_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_allocations', function (Blueprint $table) {
            if (! Schema::hasIndex('store_allocations', 'store_allocations_store_id_index')) {
                $table->index('store_id', 'store_allocations_store_id_index');
            }
            if (Schema::hasIndex('store_allocations', 'idx_store_alloc_store_date')) {
                $table->dropIndex('idx_store_alloc_store_date');
            }
            if (! Schema::hasIndex('store_allocations', 'store_allocations_allocated_at_store_id_index')) {
                $table->index(['allocated_at', 'store_id'], 'store_allocations_allocated_at_store_id_index');
            }
        });

        Schema::table('inventory_balances', function (Blueprint $table) {
            if (! Schema::hasIndex('inventory_balances', 'idx_balances_product_id')) {
                $table->index('product_id', 'idx_balances_product_id');
            }
        });

        Schema::table('stores', function (Blueprint $table) {
            if (! Schema::hasIndex('stores', 'stores_code_index')) {
                $table->index('code', 'stores_code_index');
            }
        });
    }
};
