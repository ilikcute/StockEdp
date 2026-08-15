<?php

namespace Tests\Feature\Replenishment;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\Inventory\Models\StockTransferItem;
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

    private User $admin;

    private Location $targetLocation;

    /** @var array<Location> */
    private array $allLocations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $locations = [];
        for ($i = 1; $i <= 5; $i++) {
            $locations[] = Location::create([
                'code' => "WH-PERF-0{$i}",
                'name' => "Gudang Benchmark 0{$i}",
                'is_active' => true,
            ]);
        }

        $this->allLocations = $locations;
        $this->targetLocation = $locations[0];

        $this->admin = User::factory()->create();
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole);
        $this->admin->locations()->attach(collect($locations)->pluck('id'));

        $category = Category::create(['code' => 'CAT-BENCH', 'name' => 'Kategori Benchmark', 'is_active' => true]);
        $unit = Unit::create(['code' => 'UNT-BENCH', 'name' => 'PCS', 'symbol' => 'pcs', 'is_active' => true]);

        // Seed 1,000 products (chunked inserts for performance)
        $now = now();
        $productRows = [];
        for ($p = 1; $p <= 1000; $p++) {
            $num = str_pad((string) $p, 4, '0', STR_PAD_LEFT);
            $productRows[] = [
                'sku' => "PRD-PERF-{$num}",
                'name' => "Produk Benchmark {$num}",
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'minimum_stock' => '20.0000',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($productRows, 250) as $chunk) {
            Product::insert($chunk);
        }

        $productIds = Product::orderBy('id', 'asc')->pluck('id')->all();

        // Seed ~5,000 balances (1 target + 4 sister locations per product)
        $balanceRows = [];
        foreach ($productIds as $idx => $prodId) {
            // Target location:
            // 70% low-stock (qty 2.0000 or 0.0000), 30% healthy (qty 30.0000)
            $targetQty = ($idx % 10 < 7) ? (($idx % 2 === 0) ? '0.0000' : '5.0000') : '30.0000';
            $balanceRows[] = [
                'product_id' => $prodId,
                'location_id' => $this->targetLocation->id,
                'quantity' => $targetQty,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Sister locations (surplus vs no surplus)
            for ($l = 1; $l < 5; $l++) {
                $sisterQty = ($idx % 3 === 0) ? '50.0000' : (($idx % 3 === 1) ? '15.0000' : '0.0000');
                $balanceRows[] = [
                    'product_id' => $prodId,
                    'location_id' => $this->allLocations[$l]->id,
                    'quantity' => $sisterQty,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($balanceRows, 500) as $chunk) {
            InventoryBalance::insert($chunk);
        }

        // Seed representative SENT inbound transfers
        for ($t = 1; $t <= 10; $t++) {
            $transfer = StockTransfer::create([
                'transfer_number' => "TRF-BENCH-{$t}",
                'transfer_date' => now()->toDateString(),
                'origin_location_id' => $this->allLocations[1]->id,
                'destination_location_id' => $this->targetLocation->id,
                'status' => TransferStatus::SENT->value,
                'created_by' => $this->admin->id,
            ]);
            StockTransferItem::create([
                'stock_transfer_id' => $transfer->id,
                'product_id' => $productIds[$t],
                'quantity' => '10.0000',
            ]);
        }
    }

    public function test_replenishment_endpoint_sla_over_5_http_requests(): void
    {
        $durations = [];

        for ($i = 1; $i <= 5; $i++) {
            $start = microtime(true);

            $response = $this->actingAs($this->admin, 'sanctum')
                ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->targetLocation->id.'&per_page=50');

            $elapsedMs = (microtime(true) - $start) * 1000;
            $durations[] = $elapsedMs;

            $response->assertStatus(200);
            $this->assertNotEmpty($response->json('data.data'));
            $this->assertLessThan(2000, $elapsedMs, "Request #{$i} took {$elapsedMs} ms, which exceeds SLA of 2000ms");

            echo sprintf(
                "Request %d : %.2f ms HTTP 200\n",
                $i,
                $elapsedMs
            );
        }

        $minMs = min($durations);
        $maxMs = max($durations);
        $avgMs = array_sum($durations) / count($durations);

        $this->assertLessThan(2000, $maxMs, 'Max response time must be under 2,000ms');

        echo sprintf(
            "\nMIN     : %.2f ms\nMAX     : %.2f ms\nAVERAGE : %.2f ms\n",
            $minMs,
            $maxMs,
            $avgMs
        );
    }

    public function test_query_count_is_bounded_and_has_zero_n_plus_one(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/replenishment-recommendations?location_id='.$this->targetLocation->id.'&per_page=50')
            ->assertStatus(200);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        $this->assertNotEmpty($response->json('data.data'));
        // 50 rows returned with bulk enrichment (0 per-row SQL queries)
        $this->assertLessThanOrEqual(25, $queryCount, "Total SQL queries ({$queryCount}) exceeds bounded threshold of 25 (0 N+1).");

        echo sprintf(
            "\nTOTAL SQL QUERIES = %d (Bounded, 0 N+1 for 50 recommendation rows across 1,000 products)\n",
            $queryCount
        );
    }
}
