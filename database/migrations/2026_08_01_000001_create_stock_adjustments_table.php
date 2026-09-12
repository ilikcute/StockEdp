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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number', 50)->unique();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->date('adjustment_date');
            $table->string('direction', 10); // INCREASE, DECREASE
            $table->string('reason_code', 30);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('DRAFT'); // DRAFT, POSTED, CANCELED

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('canceled_by')->nullable()->constrained('users')->restrictOnDelete();

            $table->timestamp('posted_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'adjustment_date']);
            $table->index('location_id');
            $table->index('adjustment_date');
            $table->index('status');
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 14, 4)->unsigned();
            $table->text('item_notes')->nullable();
            $table->timestamps();

            $table->unique(['stock_adjustment_id', 'product_id'], 'adj_items_adj_prod_unique');
        });

        // Add unique constraint to stock_movements to prevent duplicate movements per item/location in a reference document
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unique(
                ['reference_type', 'reference_id', 'product_id', 'location_id'],
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
        });

        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
    }
};
