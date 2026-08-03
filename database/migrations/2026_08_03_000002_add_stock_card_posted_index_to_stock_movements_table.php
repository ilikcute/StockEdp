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
            $table->index(['product_id', 'location_id', 'created_at', 'id'], 'idx_stock_card_posted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index('product_id', 'stock_movements_product_id_posted_idx');
            $table->dropIndex('idx_stock_card_posted');
        });
    }
};
