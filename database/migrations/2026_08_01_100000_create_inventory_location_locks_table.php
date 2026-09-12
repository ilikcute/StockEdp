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
        Schema::create('inventory_location_locks', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->primary();
            $table->boolean('is_frozen')->default(false);
            $table->unsignedBigInteger('frozen_by_opname_id')->nullable();
            $table->timestamp('frozen_at')->nullable();
            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });

        // Population for existing locations
        $locations = DB::table('locations')->pluck('id');
        $now = now();
        $records = [];
        foreach ($locations as $locId) {
            $records[] = [
                'location_id' => $locId,
                'is_frozen' => false,
                'frozen_by_opname_id' => null,
                'frozen_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($records)) {
            DB::table('inventory_location_locks')->insertOrIgnore($records);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_location_locks');
    }
};
