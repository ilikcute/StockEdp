<?php

namespace Tests\Feature\Product;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBarcodePerformanceBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_barcode_lookup_endpoint_response_time_under_1000ms_on_large_dataset(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $admin = User::factory()->create(['name' => 'Barcode Perf Admin']);
        $admin->roles()->attach($adminRole);

        $cat = Category::create(['code' => 'BAR-PERF-CAT', 'name' => 'Perf Category', 'is_active' => true]);
        $unit = Unit::create(['code' => 'BAR-PERF-UNT', 'name' => 'Perf Unit', 'symbol' => 'bpu', 'is_active' => true]);

        // Seed 10,000 products with unique barcodes
        $productsData = [];
        for ($i = 1; $i <= 10000; $i++) {
            $productsData[] = [
                'sku' => sprintf('PERF-BAR-%05d', $i),
                'barcode' => sprintf('000%09d', $i),
                'name' => "Perf Barcode Product {$i}",
                'category_id' => $cat->id,
                'unit_id' => $unit->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($productsData) >= 1000) {
                Product::insert($productsData);
                $productsData = [];
            }
        }
        if (! empty($productsData)) {
            Product::insert($productsData);
        }

        $targetBarcode = '000000005000'; // Product #5000 in dataset

        $durations = [];
        for ($req = 1; $req <= 5; $req++) {
            $t0 = microtime(true);
            $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/products/barcode-lookup?barcode={$targetBarcode}");
            $t1 = microtime(true);

            $response->assertOk()
                ->assertJsonPath('data.barcode', $targetBarcode);

            $durMs = round(($t1 - $t0) * 1000, 2);
            $durations[] = $durMs;

            fwrite(STDOUT, "\nRequest {$req} : {$durMs} ms HTTP 200");
            $this->assertLessThan(1000.0, $durMs, "Barcode lookup Request {$req} exceeded 1000ms threshold");
        }

        $minMs = min($durations);
        $maxMs = max($durations);
        $avgMs = round(array_sum($durations) / count($durations), 2);

        fwrite(STDOUT, "\nMIN     : {$minMs} ms\nMAX     : {$maxMs} ms\nAVERAGE : {$avgMs} ms\n");

        $this->assertLessThan(1000.0, $maxMs);
    }
}
