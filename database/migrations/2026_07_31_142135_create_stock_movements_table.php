<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('movement_id')->unique();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->string('movement_type', 30);
            $table->decimal('quantity', 14, 4);
            $table->decimal('quantity_before', 14, 4);
            $table->decimal('quantity_after', 14, 4);
            $table->string('reference_type', 100);
            $table->unsignedBigInteger('reference_id');
            $table->string('reference_number', 50)->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->index(['reference_type', 'reference_id'], 'idx_ref_type_id');
            $table->index('occurred_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
