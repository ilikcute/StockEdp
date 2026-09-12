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
        Schema::table('stock_transfers', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_transfers', 'transfer_type')) {
                $table->string('transfer_type', 20)->default('TRANSFER')->after('status');
            }
        });

        DB::table('stock_transfers')
            ->whereNull('transfer_type')
            ->orWhere('transfer_type', '')
            ->update(['transfer_type' => 'TRANSFER']);

        DB::table('stock_transfers')
            ->where('status', 'SENT')
            ->update(['status' => 'IN_TRANSIT']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('stock_transfers')
            ->where('status', 'IN_TRANSIT')
            ->update(['status' => 'SENT']);

        Schema::table('stock_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('stock_transfers', 'transfer_type')) {
                $table->dropColumn('transfer_type');
            }
        });
    }
};
