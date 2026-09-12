<?php

namespace Tests\Feature\Dashboard;

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
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPerformanceBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_endpoint_response_time_on_large_dataset_under_2000ms(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('code', RoleCode::ADMIN->value)->first();
        $admin = User::factory()->create(['name' => 'Perf Admin']);
        $admin->roles()->attach($adminRole);

        $locations = [];
        for ($i = 1; $i <= 5; $i++) {
            $loc = Location::create(['code' => "PERF-LOC-0{$i}", 'name' => "Perf Location {$i}", 'is_active' => true, 'is_frozen' => false]);
            $locations[] = $loc;
            $admin->locations()->attach($loc->id);
        }

        $cat = Category::create(['code' => 'PERF-CAT', 'name' => 'Perf Category', 'is_active' => true]);
        $unit = Unit::create(['code' => 'PERF-UNT', 'name' => 'Perf Unit', 'symbol' => 'pu', 'is_active' => true]);

        $productsData = [];
        for ($i = 1; $i <= 1000; $i++) {
            $productsData[] = [
                'sku' => sprintf('PERF-PRD-%04d', $i),
                'name' => "Perf Product {$i}",
                'category_id' => $cat->id,
                'unit_id' => $unit->id,
                'minimum_stock' => '10.0000',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        Product::insert($productsData);
        $productIds = Product::pluck('id')->all();

        $balancesData = [];
        foreach ($productIds as $pid) {
            foreach ($locations as $loc) {
                $balancesData[] = [
                    'product_id' => $pid,
                    'location_id' => $loc->id,
                    'quantity' => '50.0000',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        InventoryBalance::insert($balancesData);

        $movementsData = [];
        $types = [MovementType::RECEIPT->value, MovementType::ISSUE->value, MovementType::TRANSFER_IN->value, MovementType::TRANSFER_OUT->value];
        for ($i = 1; $i <= 10000; $i++) {
            $pid = $productIds[array_rand($productIds)];
            $loc = $locations[array_rand($locations)];
            $type = $types[array_rand($types)];
            $movementsData[] = [
                'movement_id' => sprintf('MOV-PERF-%05d', $i),
                'reference_type' => 'App\Features\Inventory\Models\StockReceipt',
                'reference_id' => $i,
                'product_id' => $pid,
                'location_id' => $loc->id,
                'movement_type' => $type,
                'quantity' => '10.0000',
                'quantity_before' => '50.0000',
                'quantity_after' => '60.0000',
                'reference_number' => sprintf('REF-%05d', $i),
                'created_by' => $admin->id,
                'occurred_at' => now()->subDays(mt_rand(0, 5)),
                'created_at' => now()->subDays(mt_rand(0, 5)),
                'updated_at' => now(),
            ];
            if (count($movementsData) >= 1000) {
                StockMovement::insert($movementsData);
                $movementsData = [];
            }
        }
        if (! empty($movementsData)) {
            StockMovement::insert($movementsData);
        }

        $durations = [];
        for ($req = 1; $req <= 5; $req++) {
            $t0 = microtime(true);
            $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/dashboard?period=7d');
            $t1 = microtime(true);

            $response->assertOk();
            $durMs = round(($t1 - $t0) * 1000, 2);
            $durations[] = $durMs;

            $this->assertLessThan(2000.0, $durMs, "Request {$req} exceeded 2000ms threshold");
        }

        $maxMs = max($durations);
        $this->assertLessThan(2000.0, $maxMs);
    }
}
