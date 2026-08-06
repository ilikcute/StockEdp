<?php

namespace App\Console\Commands;

use App\Features\Auth\Models\User;
use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class SortParityAuditCommand extends Command
{
    protected $signature = 'audit:sort-parity {--port=8099}';

    protected $description = 'Fase 8C2 — Sort parity HTTP audit';

    public function handle(): int
    {
        $port = $this->option('port');
        $baseUrl = "http://127.0.0.1:{$port}";

        $user = User::first();
        if (! $user) {
            $this->error('No user found in database.');

            return 1;
        }

        $token = $user->createToken('sort-parity-audit')->plainTextToken;
        $this->line("Token generated for: {$user->email}");

        $results = [];

        $this->newLine();
        $this->info('=== INVENTORY BALANCE SORT PARITY ===');
        foreach (['id', 'product_id', 'location_id', 'quantity', 'created_at'] as $s) {
            $j = $this->apiGet("$baseUrl/api/v1/reports/inventory-balances?sort_by=$s", $token);
            $c = $this->apiGet("$baseUrl/api/v1/reports/inventory-balances/export?sort_by=$s", $token);
            $this->line("  JSON sort_by=$s => $j");
            $this->line("  CSV  sort_by=$s => $c");
            $results["ib_json_$s"] = $j;
            $results["ib_csv_$s"] = $c;
        }

        $this->newLine();
        $this->info('=== LOW STOCK SORT PARITY ===');
        foreach (['shortage_quantity', 'minimum_stock', 'on_hand_quantity', 'product_name', 'sku'] as $s) {
            $j = $this->apiGet("$baseUrl/api/v1/reports/low-stocks?sort_by=$s", $token);
            $c = $this->apiGet("$baseUrl/api/v1/reports/low-stocks/export?sort_by=$s", $token);
            $this->line("  JSON sort_by=$s => $j");
            $this->line("  CSV  sort_by=$s => $c");
            $results["ls_json_$s"] = $j;
            $results["ls_csv_$s"] = $c;
        }

        $this->newLine();
        $this->info('=== FORBIDDEN SORT (expected 422) ===');
        $fj = $this->apiGet("$baseUrl/api/v1/reports/inventory-balances?sort_by=product_name", $token);
        $fc = $this->apiGet("$baseUrl/api/v1/reports/inventory-balances/export?sort_by=product_name", $token);
        $this->line("  IB JSON sort_by=product_name => $fj (expected 422)");
        $this->line("  IB CSV  sort_by=product_name => $fc (expected 422)");

        $fj2 = $this->apiGet("$baseUrl/api/v1/reports/low-stocks?sort_by=id", $token);
        $fc2 = $this->apiGet("$baseUrl/api/v1/reports/low-stocks/export?sort_by=id", $token);
        $this->line("  LS JSON sort_by=id => $fj2 (expected 422)");
        $this->line("  LS CSV  sort_by=id => $fc2 (expected 422)");

        $this->newLine();
        $this->info('=== SUMMARY ===');
        $fail = false;
        foreach ($results as $key => $status) {
            if ($status !== 200) {
                $this->error("  FAIL: $key => HTTP $status");
                $fail = true;
            }
        }
        if (! $fail) {
            $this->info('  All canonical sort options => HTTP 200');
        }
        foreach ([
            'IB forbidden product_name JSON' => $fj,
            'IB forbidden product_name CSV' => $fc,
            'LS forbidden id JSON' => $fj2,
            'LS forbidden id CSV' => $fc2,
        ] as $label => $status) {
            if ($status === 422) {
                $this->info("  PASS forbidden: $label => 422");
            } else {
                $this->warn("  WARN forbidden: $label => $status (expected 422)");
            }
        }

        PersonalAccessToken::where('name', 'sort-parity-audit')->delete();
        $this->line("\nToken cleaned up.");

        return $fail ? 1 : 0;
    }

    private function apiGet(string $url, string $token): int
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $status;
    }
}
