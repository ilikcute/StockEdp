<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_receipts', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_receipts', 'memo_number')) {
                $table->string('memo_number', 100)->nullable()->after('receipt_number');
            }
            if (! Schema::hasColumn('stock_receipts', 'source_type')) {
                $table->string('source_type', 50)->default('GA_PROCUREMENT')->after('memo_number');
            }
        });

        // Make supplier_id nullable
        DB::statement('ALTER TABLE stock_receipts MODIFY supplier_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE stock_receipts MODIFY supplier_id BIGINT UNSIGNED NOT NULL');

        Schema::table('stock_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('stock_receipts', 'source_type')) {
                $table->dropColumn('source_type');
            }
            if (Schema::hasColumn('stock_receipts', 'memo_number')) {
                $table->dropColumn('memo_number');
            }
        });
    }
};
