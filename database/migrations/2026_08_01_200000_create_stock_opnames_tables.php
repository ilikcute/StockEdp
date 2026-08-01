<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('opname_number', 50)->unique();
            $table->unsignedBigInteger('location_id');
            $table->date('opname_date');
            $table->string('status', 20)->default('DRAFT');
            $table->text('notes')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->unsignedBigInteger('started_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('canceled_by')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();

            // Virtual/Stored generated column for DB-level active location uniqueness (IN_PROGRESS or COUNTED)
            $table->unsignedBigInteger('active_location_id')
                ->storedAs("CASE WHEN status IN ('IN_PROGRESS', 'COUNTED') THEN location_id ELSE NULL END")
                ->nullable();

            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->onDelete('restrict');
            $table->foreign('started_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('completed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('posted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('canceled_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['active_location_id'], 'unique_active_opname_per_location');
        });

        // Foreign key in inventory_location_locks pointing to stock_opnames
        Schema::table('inventory_location_locks', function (Blueprint $table) {
            $table->foreign('frozen_by_opname_id')->references('id')->on('stock_opnames')->onDelete('set null');
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_opname_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('snapshot_quantity', 14, 4)->default(0.0000);
            $table->decimal('counted_quantity', 14, 4)->nullable();
            $table->decimal('variance_quantity', 14, 4)->nullable();
            $table->unsignedInteger('count_version')->default(0);
            $table->unsignedBigInteger('counted_by')->nullable();
            $table->timestamp('counted_at')->nullable();
            $table->text('item_notes')->nullable();
            $table->boolean('is_unexpected')->default(false);
            $table->timestamps();

            $table->foreign('stock_opname_id')->references('id')->on('stock_opnames')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('counted_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['stock_opname_id', 'product_id'], 'unique_opname_product');
        });

        Schema::create('stock_opname_count_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_opname_item_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('previous_quantity', 14, 4)->nullable();
            $table->decimal('new_quantity', 14, 4);
            $table->unsignedInteger('count_version');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('stock_opname_item_id')->references('id')->on('stock_opname_items')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('stock_opname_reopen_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_opname_id');
            $table->unsignedBigInteger('reopened_by');
            $table->text('reason');
            $table->timestamp('reopened_at')->useCurrent();

            $table->foreign('stock_opname_id')->references('id')->on('stock_opnames')->onDelete('cascade');
            $table->foreign('reopened_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_reopen_logs');
        Schema::dropIfExists('stock_opname_count_logs');
        Schema::dropIfExists('stock_opname_items');

        Schema::table('inventory_location_locks', function (Blueprint $table) {
            $table->dropForeign(['frozen_by_opname_id']);
        });

        Schema::dropIfExists('stock_opnames');
    }
};
