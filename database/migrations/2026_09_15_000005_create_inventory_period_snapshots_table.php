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
        Schema::create('inventory_period_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_period_id')->constrained('inventory_periods')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->enum('condition', ['GOOD', 'DEFECTIVE'])->default('GOOD');
            $table->decimal('opening_balance', 14, 4)->default(0);
            $table->decimal('total_in', 14, 4)->default(0);
            $table->decimal('total_out', 14, 4)->default(0);
            $table->decimal('total_adjustment', 14, 4)->default(0);
            $table->decimal('closing_balance', 14, 4)->default(0);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('total_value', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(
                ['inventory_period_id', 'product_id', 'location_id', 'condition'],
                'uniq_period_prod_loc_cond'
            );
            $table->index(['inventory_period_id', 'location_id'], 'idx_snap_period_loc');
            $table->index(['product_id', 'condition'], 'idx_snap_prod_cond');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_period_snapshots');
    }
};
