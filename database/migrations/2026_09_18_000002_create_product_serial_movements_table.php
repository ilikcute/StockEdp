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
        Schema::create('product_serial_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_serial_id')->constrained('product_serials')->cascadeOnDelete();
            $table->string('movement_type', 50);
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('from_store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('to_store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('from_condition', 20)->nullable();
            $table->string('to_condition', 20);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_serial_id', 'occurred_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('reference_number');
            $table->index('movement_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_serial_movements');
    }
};
