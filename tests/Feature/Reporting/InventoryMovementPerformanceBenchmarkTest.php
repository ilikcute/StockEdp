<?php

namespace Tests\Feature\Reporting;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryMovementPerformanceBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $category = Category::factory()->create();
        $unit = Unit::factory()->create();

        // 1. Create 5 Locations
        $locations = Location::factory()->count(5)->create(['is_active' => true]);
        $locationIds = $locations->pluck('id')->all();

        // 2. Setup user with allowed locations
        $this->user = User::factory()->create();
        $this->user->roles()->attach(Role::where('code', RoleCode::ADMIN->value)->first()->id);
        $this->user->locations()->attach($locationIds);

        // 3. Bulk insert 1,000 products
        $now = now();
        $productsData = [];
        for ($i = 1; $i <= 1000; $i++) {
            $productsData[] = [
                'name' => "Benchmark Product {$i}",
                'sku' => "SKU-BM-{$i}",
                'barcode' => "899{$i}000",
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($productsData, 500) as $chunk) {
            Product::insert($chunk);
        }

        $allProductIds = Product::pluck('id')->all();

        // 4. Bulk insert 5,000 balances (1000 products x 5 locations)
        $balancesData = [];
        foreach ($allProductIds as $pId) {
            foreach ($locationIds as $locId) {
                $balancesData[] = [
                    'product_id' => $pId,
                    'location_id' => $locId,
                    'quantity' => '100.0000',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk($balancesData, 500) as $chunk) {
            InventoryBalance::insert($chunk);
        }

        // 5. Bulk insert stock movements with all schema columns
        $movementsData = [];
        for ($i = 0; $i < 200; $i++) {
            $movementsData[] = [
                'movement_id' => (string) Str::uuid(),
                'product_id' => $allProductIds[$i],
                'location_id' => $locationIds[$i % 5],
                'movement_type' => MovementType::ISSUE->value,
                'quantity' => '15.0000',
                'quantity_before' => '100.0000',
                'quantity_after' => '85.0000',
                'reference_type' => 'App\Features\Inventory\Models\StockIssue',
                'reference_id' => $i + 1,
                'occurred_at' => CarbonImmutable::now('Asia/Jakarta')->subDays($i % 30),
                'created_by' => $this->user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        StockMovement::insert($movementsData);
    }

    public function test_performance_sla_under_2000ms_over_5_requests(): void
    {
        $durations = [];

        for ($i = 1; $i <= 5; $i++) {
            $start = microtime(true);

            $response = $this->actingAs($this->user)->getJson('/api/v1/reports/inventory-movement?type=slow-moving&period=90');
            $response->assertStatus(200);

            $durationMs = (microtime(true) - $start) * 1000;
            $durations[] = $durationMs;
        }

        $avgDuration = array_sum($durations) / count($durations);
        $maxDuration = max($durations);

        $this->assertLessThan(2000, $avgDuration, "Average latency ({$avgDuration} ms) exceeded 2000ms SLA.");
        $this->assertLessThan(2000, $maxDuration, "Max latency ({$maxDuration} ms) exceeded 2000ms SLA.");
    }

    public function test_query_count_is_bounded_and_zero_n_plus_one(): void
    {
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)->getJson('/api/v1/reports/inventory-movement?type=slow-moving&period=90&per_page=15');
        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Query count should be strictly bounded (auth/RBAC + locations + summary counts + paginated list + total count <= 15 queries total, 0 N+1)
        $this->assertLessThanOrEqual(15, count($queries), 'Query count is too high, possible N+1 query regression.');
    }
}
