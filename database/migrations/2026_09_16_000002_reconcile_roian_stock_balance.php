<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Reconcile orphaned physical balance of FAN PROSESOR LGA1155 (SKU: 63710)
     * at location ROIAN back to 2.0000 (matching official transfer TRF-202609-0022 and stock_movements)
     * and restore 2.0000 units to primary warehouse ADM EDP (location code: ADM).
     */
    public function up(): void
    {
        $product = DB::table('products')->where('sku', '63710')->first();
        $locRoian = DB::table('locations')->where('code', 'ROIAN')->first();
        $locAdm = DB::table('locations')->where('code', 'ADM')->first();

        if ($product && $locRoian) {
            DB::table('inventory_balances')
                ->where('product_id', $product->id)
                ->where('location_id', $locRoian->id)
                ->where('condition', 'GOOD')
                ->update([
                    'quantity' => '2.0000',
                    'updated_at' => now(),
                ]);
        }

        if ($product && $locAdm) {
            DB::table('inventory_balances')
                ->where('product_id', $product->id)
                ->where('location_id', $locAdm->id)
                ->where('condition', 'GOOD')
                ->update([
                    'quantity' => '22.0000',
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data reconciliation patch does not require rollback.
    }
};
