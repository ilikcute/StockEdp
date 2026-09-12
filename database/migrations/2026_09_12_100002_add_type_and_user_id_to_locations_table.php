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
        Schema::table('locations', function (Blueprint $table) {
            $table->enum('type', ['MAIN_WAREHOUSE', 'FIELD_PERSONNEL', 'DAMAGED_STORAGE'])
                ->default('MAIN_WAREHOUSE')
                ->after('name');
            $table->foreignId('user_id')
                ->nullable()
                ->after('type')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['type']);
            $table->dropColumn(['type', 'user_id']);
        });
    }
};
