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
        Schema::create('product_serials', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number', 100)->unique();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('current_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('current_store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('current_condition', 20)->default('GOOD'); // GOOD, DEFECTIVE
            $table->string('status', 30)->default('IN_STOCK'); // IN_STOCK, INSTALLED, DEFECTIVE, RETURNED_TO_VENDOR, DISPOSED
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['current_location_id', 'status']);
            $table->index(['current_store_id', 'status']);
            $table->index('current_condition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_serials');
    }
};
