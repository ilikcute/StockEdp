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
        if (! Schema::hasTable('store_allocations')) {
            Schema::create('store_allocations', function (Blueprint $table) {
                $table->id();
                $table->string('allocation_number', 50)->unique();
                $table->foreignId('technician_user_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('technician_location_id')->constrained('locations')->restrictOnDelete();
                $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
                $table->date('allocated_at');
                $table->text('notes')->nullable();

                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
                $table->timestamps();

                $table->index(['allocated_at', 'store_id']);
                $table->index('technician_user_id');
                $table->index('technician_location_id');
            });
        }

        if (! Schema::hasTable('store_allocation_items')) {
            Schema::create('store_allocation_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_allocation_id')->constrained('store_allocations')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
                $table->decimal('quantity', 14, 4)->unsigned();
                $table->string('serial_number', 100)->nullable();

                $table->foreignId('pulled_product_id')->nullable()->constrained('products')->restrictOnDelete();
                $table->decimal('pulled_quantity', 14, 4)->unsigned()->nullable();
                $table->string('pulled_serial_number', 100)->nullable();
                $table->string('defective_reason', 255)->nullable();
                $table->timestamps();

                $table->index('product_id');
                $table->index('pulled_product_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_allocation_items');
        Schema::dropIfExists('store_allocations');
    }
};
