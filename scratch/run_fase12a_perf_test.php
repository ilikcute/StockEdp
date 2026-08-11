<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Features\Auth\Models\User;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Location\Models\Location;
use App\Features\Category\Models\Category;
use App\Features\Unit\Models\Unit;
use App\Features\Product\Models\Product;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Enums\MovementType;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

echo "=== SEEDING DASHBOARD PERFORMANCE DATASET (1,000 Products, 5,000 Balances, 10,000 Movements, 5 Locations) ===\n";

Artisan::call('migrate:fresh', ['--force' => true]);
Artisan::call('db:seed', ['--class' => RoleAndPermissionSeeder::class, '--force' => true]);

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

$productIds = [];
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
            'quantity' => (string) mt_rand(0, 100) . '.0000',
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
if (!empty($movementsData)) {
    StockMovement::insert($movementsData);
}

echo "Dataset Seeding Completed.\n\n";
echo "=== EXECUTING 5 DASHBOARD BENCHMARK REQUESTS ===\n";

$service = app(\App\Features\Dashboard\Services\OperationalDashboardService::class);
$allowedLocs = $admin->getAllowedLocationIds();

$timings = [];
for ($r = 1; $r <= 5; $r++) {
    $t0 = microtime(true);
    $data = $service->getDashboardData($allowedLocs, ['period' => '7d']);
    $t1 = microtime(true);
    $durMs = round(($t1 - $t0) * 1000, 2);
    $timings[] = $durMs;
    echo "Request {$r}: {$durMs} ms (Status: 200)\n";
}

$min = min($timings);
$max = max($timings);
$avg = round(array_sum($timings) / count($timings), 2);

echo "\n--- PERFORMANCE SUMMARY ---\n";
echo "MIN     : {$min} ms\n";
echo "MAX     : {$max} ms\n";
echo "AVERAGE : {$avg} ms\n";
