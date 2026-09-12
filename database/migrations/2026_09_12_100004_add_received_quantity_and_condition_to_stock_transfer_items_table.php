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
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->enum('condition', ['GOOD', 'DEFECTIVE'])
                ->default('GOOD')
                ->after('product_id');
            $table->decimal('received_quantity', 14, 4)
                ->unsigned()
                ->nullable()
                ->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropColumn(['received_quantity', 'condition']);
        });
    }
};
