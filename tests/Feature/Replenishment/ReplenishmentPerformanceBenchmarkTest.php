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
use Tests\TestCase;

class ReplenishmentPerformanceBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Location $targetLocation;

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

        $this->targetLocation = $locations[0];

        $this->admin = User::factory()->create();
        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $this->admin->roles()->attach($adminRole);
        $this->admin->locations()->attach(collect($locations)->pluck('id'));

        $category = Category::create(['code' => 'CAT-BENCH', 'name' => 'Kategori Benchmark', 'is_active' => true]);
        $unit = Unit::create(['code' => 'UNT-BENCH', 'name' => 'PCS', 'symbol' => 'pcs', 'is_active' => true]);

        // Seed 100 products (low stock + surplus at sister warehouses)
        $productRows = [];
        for ($p = 1; $p <= 100; $p++) {
            $sku = sprintf('PRD-BENCH-%04d', $p);
            $productRows[] = [
                'sku' => $sku,
                'name' => "Produk Benchmark {$p}",
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'minimum_stock' => '20.00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        Product::insert($productRows);
        $allProducts = Product::all();

        // Seed balances
        $balanceRows = [];
        foreach ($allProducts as $idx => $prod) {
            // Target location: low stock (qty 5.0000, min 20.0000)
            $balanceRows[] = [
                'product_id' => $prod->id,
                'location_id' => $this->targetLocation->id,
                'quantity' => '5.0000',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Other locations: surplus
            for ($l = 1; $l < 5; $l++) {
                $balanceRows[] = [
                    'product_id' => $prod->id,
                    'location_id' => $locations[$l]->id,
                    'quantity' => ($idx % 2 === 0) ? '35.0000' : '10.0000',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        InventoryBalance::insert($balanceRows);

        // Seed representative SENT transfers
        for ($t = 1; $t <= 5; $t++) {
            $transfer = StockTransfer::create([
                'transfer_number' => "TRF-BENCH-{$t}",
                'transfer_date' => now()->toDateString(),
                'origin_location_id' => $locations[1]->id,
                'destination_location_id' => $this->targetLocation->id,
                'status' => TransferStatus::SENT->value,
                'created_by' => $this->admin->id,
            ]);
            StockTransferItem::create([
                'stock_transfer_id' => $transfer->id,
                'product_id' => $allProducts[$t]->id,
                'quantity' => '5.0000',
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
        }

        $minMs = min($durations);
        $maxMs = max($durations);
        $avgMs = array_sum($durations) / count($durations);

        $this->assertLessThan(2000, $maxMs, 'Max response time must be under 2,000ms');

        // Output metrics for review
        echo sprintf(
            "\n[Replenishment Performance Benchmark] MIN: %.2f ms | MAX: %.2f ms | AVG: %.2f ms\n",
            $minMs,
            $maxMs,
            $avgMs
        );
    }
}
