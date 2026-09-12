<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Buang index non-unique yang menduplikasi unique index pada kolom yang sama
     * dan index single-column yang tertutup prefix composite index.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasIndex('categories', 'categories_code_index')) {
                $table->dropIndex('categories_code_index');
            }
        });

        Schema::table('units', function (Blueprint $table) {
            if (Schema::hasIndex('units', 'units_code_index')) {
                $table->dropIndex('units_code_index');
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasIndex('suppliers', 'suppliers_code_index')) {
                $table->dropIndex('suppliers_code_index');
            }
        });

        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasIndex('locations', 'locations_code_index')) {
                $table->dropIndex('locations_code_index');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasIndex('products', 'products_sku_index')) {
                $table->dropIndex('products_sku_index');
            }
            if (Schema::hasIndex('products', 'products_barcode_index')) {
                $table->dropIndex('products_barcode_index');
            }
        });

        Schema::table('stock_adjustments', function (Blueprint $table) {
            if (Schema::hasIndex('stock_adjustments', 'stock_adjustments_status_index')) {
                $table->dropIndex('stock_adjustments_status_index');
            }
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            if (Schema::hasIndex('stock_transfers', 'stock_transfers_status_index')) {
                $table->dropIndex('stock_transfers_status_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasIndex('categories', 'categories_code_index')) {
                $table->index('code');
            }
        });

        Schema::table('units', function (Blueprint $table) {
            if (! Schema::hasIndex('units', 'units_code_index')) {
                $table->index('code');
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasIndex('suppliers', 'suppliers_code_index')) {
                $table->index('code');
            }
        });

        Schema::table('locations', function (Blueprint $table) {
            if (! Schema::hasIndex('locations', 'locations_code_index')) {
                $table->index('code');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasIndex('products', 'products_sku_index')) {
                $table->index('sku');
            }
            if (! Schema::hasIndex('products', 'products_barcode_index')) {
                $table->index('barcode');
            }
        });

        Schema::table('stock_adjustments', function (Blueprint $table) {
            if (! Schema::hasIndex('stock_adjustments', 'stock_adjustments_status_index')) {
                $table->index('status');
            }
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            if (! Schema::hasIndex('stock_transfers', 'stock_transfers_status_index')) {
                $table->index('status', 'stock_transfers_status_index');
            }
        });
    }
};
