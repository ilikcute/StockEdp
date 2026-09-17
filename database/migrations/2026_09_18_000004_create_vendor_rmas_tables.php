<?php

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendor_rmas')) {
            Schema::create('vendor_rmas', function (Blueprint $table) {
                $table->id();
                $table->string('rma_number', 50)->unique();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->foreignId('origin_location_id')->constrained('locations')->cascadeOnDelete();
                $table->string('status', 30)->default('DRAFT')->index();
                $table->date('dispatch_date')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->index(['status', 'created_at']);
            });
        }

        if (! Schema::hasTable('vendor_rma_items')) {
            Schema::create('vendor_rma_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_rma_id')->constrained('vendor_rmas')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('product_serial_id')->nullable()->constrained('product_serials')->nullOnDelete();
                $table->string('serial_number', 100)->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->text('fault_description')->nullable();
                $table->string('status', 30)->default('PENDING')->index();
                $table->timestamps();

                $table->index(['vendor_rma_id', 'product_id']);
            });
        }

        // Add permissions
        $viewPerm = Permission::firstOrCreate(
            ['code' => 'vendor_rmas.view'],
            ['name' => 'Melihat Data RMA Vendor', 'group' => 'vendor_rmas']
        );

        $managePerm = Permission::firstOrCreate(
            ['code' => 'vendor_rmas.manage'],
            ['name' => 'Mengelola & Dispatch RMA Vendor', 'group' => 'vendor_rmas']
        );

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        if ($adminRole) {
            $adminRole->permissions()->syncWithoutDetaching([$viewPerm->id, $managePerm->id]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_rma_items');
        Schema::dropIfExists('vendor_rmas');
    }
};
