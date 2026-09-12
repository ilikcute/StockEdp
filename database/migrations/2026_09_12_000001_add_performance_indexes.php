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
        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasIndex('stock_movements', 'movement_type')) {
                $table->index('movement_type');
            }

            if (! Schema::hasIndex('stock_movements', 'reference_number')) {
                $table->index('reference_number');
            }

            if (! Schema::hasIndex('stock_movements', 'idx_movement_location_occurred')) {
                $table->index(['location_id', 'occurred_at'], 'idx_movement_location_occurred');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasIndex('users', 'name')) {
                $table->index('name');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasIndex('categories', 'categories_name_index')) {
                $table->index('name');
            }
        });

        Schema::table('units', function (Blueprint $table) {
            if (! Schema::hasIndex('units', 'units_name_symbol_index')) {
                $table->index(['name', 'symbol'], 'units_name_symbol_index');
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasIndex('suppliers', 'suppliers_name_contact_person_index')) {
                $table->index(['name', 'contact_person'], 'suppliers_name_contact_person_index');
            }
        });

        Schema::table('locations', function (Blueprint $table) {
            if (! Schema::hasIndex('locations', 'locations_name_index')) {
                $table->index('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            // FK location_id binds to the composite index on fresh DBs (MySQL auto-replaces
            // the redundant single-column index). Recreate the single-column index first so
            // the FK can rebind, then the composite index can be dropped safely.
            if (
                Schema::hasIndex('stock_movements', 'idx_movement_location_occurred')
                && ! Schema::hasIndex('stock_movements', 'stock_movements_location_id_index')
            ) {
                $table->index('location_id');
            }
            if (Schema::hasIndex('stock_movements', 'idx_movement_location_occurred')) {
                $table->dropIndex('idx_movement_location_occurred');
            }
            if (Schema::hasIndex('stock_movements', 'stock_movements_reference_number_index')) {
                $table->dropIndex('stock_movements_reference_number_index');
            }
            if (Schema::hasIndex('stock_movements', 'stock_movements_movement_type_index')) {
                $table->dropIndex('stock_movements_movement_type_index');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasIndex('users', 'users_name_index')) {
                $table->dropIndex('users_name_index');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasIndex('categories', 'categories_name_index')) {
                $table->dropIndex('categories_name_index');
            }
        });

        Schema::table('units', function (Blueprint $table) {
            if (Schema::hasIndex('units', 'units_name_symbol_index')) {
                $table->dropIndex('units_name_symbol_index');
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasIndex('suppliers', 'suppliers_name_contact_person_index')) {
                $table->dropIndex('suppliers_name_contact_person_index');
            }
        });

        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasIndex('locations', 'locations_name_index')) {
                $table->dropIndex('locations_name_index');
            }
        });
    }
};
