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
        Schema::create('inventory_periods', function (Blueprint $table) {
            $table->id();
            $table->string('period_key', 7)->unique(); // e.g. '2026-08'
            $table->unsignedSmallInteger('year');       // e.g. 2026
            $table->unsignedTinyInteger('month');       // 1 - 12
            $table->date('start_date');                 // 2026-08-01
            $table->date('end_date');                   // 2026-08-31
            $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reopen_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['year', 'month']);
            $table->index(['start_date', 'end_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_periods');
    }
};
