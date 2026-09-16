<?php

namespace App\Console\Commands;

use App\Features\Inventory\Services\InventoryReconciliationService;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use Illuminate\Console\Command;

class ReconcileInventoryBalancesCommand extends Command
{
    protected $signature = 'inventory:reconcile-balances
                            {--dry-run : Hanya pindai dan tampilkan selisih tanpa mengubah data}
                            {--force : Jalankan sinkronisasi tanpa konfirmasi interaktif}
                            {--location= : Filter ID atau Kode Lokasi}
                            {--sku= : Filter SKU Produk}';

    protected $description = 'Pindai dan sinkronkan saldo inventory_balances dengan buku besar mutasi stock_movements';

    public function handle(InventoryReconciliationService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $locationInput = $this->option('location');
        $skuInput = $this->option('sku');

        $locationId = null;
        if ($locationInput) {
            $loc = is_numeric($locationInput)
                ? Location::find($locationInput)
                : Location::where('code', $locationInput)->first();
            if (! $loc) {
                $this->error("Lokasi '{$locationInput}' tidak ditemukan.");
                return self::FAILURE;
            }
            $locationId = $loc->id;
            $this->info("Filter Lokasi: {$loc->code} - {$loc->name}");
        }

        $productId = null;
        if ($skuInput) {
            $prod = Product::where('sku', $skuInput)->first();
            if (! $prod) {
                $this->error("Produk SKU '{$skuInput}' tidak ditemukan.");
                return self::FAILURE;
            }
            $productId = $prod->id;
            $this->info("Filter Produk: {$prod->sku} - {$prod->name}");
        }

        $this->info('Memindai perbandingan saldo fisik vs mutasi transaksi...');
        $scan = $service->scanDiscrepancies($locationId, $productId);
        $summary = $scan['summary'];
        $discrepancies = $scan['discrepancies'];

        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Total Baris Pasangan Diperiksa', $summary['total_scanned_pairs']],
                ['Baris Klop / Sinkron', $summary['matching_pairs']],
                ['Baris Berselisih (Anomali)', $summary['discrepant_pairs']],
                ['Status', $summary['status']],
                ['Waktu Pemindaian', $summary['scanned_at']],
            ]
        );

        if (empty($discrepancies)) {
            $this->info('✓ Luar biasa! Semua saldo fisik dan buku besar mutasi telah 100% SINKRON.');
            return self::SUCCESS;
        }

        $this->warn('Ditemukan '.count($discrepancies).' baris data dengan selisih:');

        $rows = array_map(function ($d) {
            return [
                $d['location_code'].' - '.$d['location_name'],
                $d['product_sku'].' - '.$d['product_name'],
                $d['condition'],
                $d['current_quantity'],
                $d['expected_quantity'],
                $d['difference'],
            ];
        }, $discrepancies);

        $this->table(
            ['Lokasi', 'Produk', 'Kondisi', 'Saldo Fisik Saat Ini', 'Saldo Seharusnya (Mutasi)', 'Selisih'],
            $rows
        );

        if ($dryRun) {
            $this->comment('Mode --dry-run aktif. Tidak ada perubahan yang dilakukan ke basis data.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Apakah Anda yakin ingin menyinkronkan saldo fisik di atas agar sesuai dengan mutasi transaksi?', true)) {
            $this->comment('Operasi dibatalkan.');
            return self::SUCCESS;
        }

        $this->info('Menyinkronkan saldo...');
        $keys = array_column($discrepancies, 'key');
        $result = $service->reconcileBalances($keys, 1);

        $this->info("✓ Selesai! {$result['message']}");

        return self::SUCCESS;
    }
}
