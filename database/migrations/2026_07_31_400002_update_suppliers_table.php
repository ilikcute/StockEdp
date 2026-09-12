<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Rename contact_name to contact_person for consistency with spec
            $table->renameColumn('contact_name', 'contact_person');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            // Add missing columns
            $table->string('tax_number', 30)->nullable()->after('address');
            $table->foreignId('created_by')->nullable()->after('tax_number')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['tax_number', 'created_by', 'updated_by']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->renameColumn('contact_person', 'contact_name');
        });
    }
};
