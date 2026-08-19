<?php

namespace Tests\Feature\Replenishment;

use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\Unit\Models\Unit;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReplenishmentPerformanceBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Location $targetLocation;

    protected array $allLocations = [];

    protected array $products = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->user = User::factory()->create(['is_active' => true]);
        $adminRole = DB::table('roles')->where('code', 'ADMIN')->first();
        if ($adminRole) {
            $this->user->roles()->attach($adminRole->id);
        }

        // Create 5 locations
        for ($l = 1; $l <= 5; $l++) {
            $loc = Location::create([
                'code' => "LOC-PERF-{$l}",
                'name' => "Location Perf {$l}",
                'is_active' => true,
            ]);
            $this->allLocations[] = $loc;
            $this->user->locations()->attach($loc->id);
        }
        $this->targetLocation = $this->allLocations[0];

        $category = Category::factory()->create(['is_active' => true]);
        $unit = Unit::factory()->create(['is_active' => true]);

        // Bulk insert 1,000 products
        $now = now();
        $productsData = [];
        for ($i = 1; $i <= 1000; $i++) {
            $productsData[] = [
                'name' => "Benchmark Product {$i}",
                'sku' => sprintf('SKU-REC-%04d', $i),
                'barcode' => sprintf('899%010d', $i),
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'minimum_stock' => '20.0000',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Product::insert($productsData);
        $this->products = Product::all()->all();

        // Bulk insert 5,000 inventory balances (1,000 products x 5 locations)
        $balancesData = [];
        foreach ($this->products as $p) {
            foreach ($this->allLocations as $locIdx => $loc) {
                // Target location has shortage (on_hand = 5.0000 < min 20.0000)
                // Other locations have surplus (on_hand = 50.0000 > min 20.0000)
                $qty = ($loc->id === $this->targetLocation->id) ? '5.0000' : '50.0000';
                $balancesData[] = [
                    'location_id' => $loc->id,
                    'product_id' => $p->id,
                    'quantity' => $qty,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Chunk insert balances
        foreach (array_chunk($balancesData, 1000) as $chunk) {
            InventoryBalance::insert($chunk);
        }
    }

    public function test_recommendation_benchmark_under_2000ms_sla(): void
    {
        $durations = [];

        for ($i = 1; $i <= 3; $i++) {
            $start = microtime(true);

            $response = $this->actingAs($this->user)->getJson(
                "/api/v1/replenishment-recommendations?location_id={$this->targetLocation->id}&per_page=15"
            );
            $response->assertStatus(200);

            $durationMs = (microtime(true) - $start) * 1000;
            $durations[] = $durationMs;
        }

        $avgDuration = array_sum($durations) / count($durations);
        $maxDuration = max($durations);

        $this->assertLessThan(2000, $avgDuration, "Average latency ({$avgDuration} ms) exceeded 2000ms SLA.");
        $this->assertLessThan(2000, $maxDuration, "Max latency ({$maxDuration} ms) exceeded 2000ms SLA.");
    }

    public function test_validate_action_benchmark_and_bounded_query_count(): void
    {
        $sampleProduct = $this->products[0];
        $sampleSource = $this->allLocations[1];

        DB::enableQueryLog();

        $start = microtime(true);
        $response = $this->actingAs($this->user)->postJson('/api/v1/replenishment-recommendations/validate-action', [
            'target_location_id' => $this->targetLocation->id,
            'items' => [
                [
                    'product_id' => $sampleProduct->id,
                    'source_location_id' => $sampleSource->id,
                    'requested_quantity' => '15.0000',
                ],
            ],
        ]);
        $durationMs = (microtime(true) - $start) * 1000;

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(200)
            ->assertJsonPath('data.valid', true);

        $this->assertLessThan(2000, $durationMs, "Validation latency ({$durationMs} ms) exceeded 2000ms SLA.");
        // Bounded O(1) query count
        $this->assertLessThanOrEqual(25, count($queries), 'Validation query count is too high.');
    }
}
