<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->decimal('quantity', 14, 4)->default(0.0000);
            $table->timestamps();

            $table->unique(['product_id', 'location_id'], 'prod_loc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_balances');
    }
};
