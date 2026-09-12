<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_balances', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_balances', 'condition')) {
                $table->enum('condition', ['GOOD', 'DEFECTIVE'])
                    ->default('GOOD')
                    ->after('location_id');
            }

            $table->index('product_id', 'idx_balances_product_id');
            $table->dropUnique('prod_loc_unique');
            $table->unique(['product_id', 'location_id', 'condition'], 'prod_loc_cond_unique');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'condition')) {
                $table->enum('condition', ['GOOD', 'DEFECTIVE'])
                    ->default('GOOD')
                    ->after('location_id');
            }

            $table->index(['product_id', 'location_id', 'condition'], 'idx_mov_prod_loc_cond');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_mov_prod_loc_cond');
            $table->dropColumn('condition');
        });

        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->dropUnique('prod_loc_cond_unique');
            $table->dropColumn('condition');
            $table->unique(['product_id', 'location_id'], 'prod_loc_unique');
        });
    }
};
