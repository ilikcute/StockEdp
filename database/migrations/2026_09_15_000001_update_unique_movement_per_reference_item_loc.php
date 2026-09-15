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
            $table->dropUnique('unique_movement_per_reference_item_loc');
            $table->unique(
                ['reference_type', 'reference_id', 'product_id', 'location_id', 'condition', 'movement_type'],
                'unique_movement_per_reference_item_loc'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropUnique('unique_movement_per_reference_item_loc');
            $table->unique(
                ['reference_type', 'reference_id', 'product_id', 'location_id'],
                'unique_movement_per_reference_item_loc'
            );
        });
    }
};
