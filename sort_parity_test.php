<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Laravel\Sanctum\PersonalAccessToken;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$user = User::first();
if (! $user) {
    echo "ERROR: No user found\n";
    exit(1);
}
$token = $user->createToken('sort-parity-audit')->plainTextToken;
echo "Bearer token generated.\n\n";

$baseUrl = 'http://127.0.0.1:8099';
$authHdr = "Authorization: Bearer $token";

function httpGet(string $url, string $auth): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [$auth, 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    return ['status' => $status ?: 'ERR:'.$err, 'body' => $body];
}

echo "=== INVENTORY BALANCE SORT PARITY ===\n";
foreach (['id', 'product_id', 'location_id', 'quantity', 'created_at'] as $s) {
    $r = httpGet("$baseUrl/api/v1/reports/inventory-balances?sort_by=$s", $authHdr);
    $r2 = httpGet("$baseUrl/api/v1/reports/inventory-balances/export?sort_by=$s", $authHdr);
    echo "  JSON sort_by=$s => {$r['status']}\n";
    echo "  CSV  sort_by=$s => {$r2['status']}\n";
}

echo "\n=== LOW STOCK SORT PARITY ===\n";
foreach (['shortage_quantity', 'minimum_stock', 'on_hand_quantity', 'product_name', 'sku'] as $s) {
    $r = httpGet("$baseUrl/api/v1/reports/low-stocks?sort_by=$s", $authHdr);
    $r2 = httpGet("$baseUrl/api/v1/reports/low-stocks/export?sort_by=$s", $authHdr);
    echo "  JSON sort_by=$s => {$r['status']}\n";
    echo "  CSV  sort_by=$s => {$r2['status']}\n";
}

echo "\n=== FORBIDDEN SORT (expected 422) ===\n";
$r = httpGet("$baseUrl/api/v1/reports/inventory-balances?sort_by=product_name", $authHdr);
$r2 = httpGet("$baseUrl/api/v1/reports/inventory-balances/export?sort_by=product_name", $authHdr);
echo "  inventory-balances JSON sort_by=product_name => {$r['status']}\n";
echo "  inventory-balances CSV  sort_by=product_name => {$r2['status']}\n";

$r = httpGet("$baseUrl/api/v1/reports/low-stocks?sort_by=id", $authHdr);
$r2 = httpGet("$baseUrl/api/v1/reports/low-stocks/export?sort_by=id", $authHdr);
echo "  low-stocks JSON sort_by=id => {$r['status']}\n";
echo "  low-stocks CSV  sort_by=id => {$r2['status']}\n";

PersonalAccessToken::where('name', 'sort-parity-audit')->delete();
echo "\nToken cleaned up.\n";
