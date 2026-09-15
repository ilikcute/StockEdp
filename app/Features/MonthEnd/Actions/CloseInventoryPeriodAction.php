<?php

namespace App\Features\MonthEnd\Actions;

use App\Features\Inventory\Enums\AdjustmentStatus;
use App\Features\Inventory\Enums\IssueStatus;
use App\Features\Inventory\Enums\MovementType;
use App\Features\Inventory\Enums\OpnameStatus;
use App\Features\Inventory\Enums\ReceiptStatus;
use App\Features\Inventory\Enums\StockCondition;
use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Inventory\Models\InventoryBalance;
use App\Features\Inventory\Models\StockAdjustment;
use App\Features\Inventory\Models\StockIssue;
use App\Features\Inventory\Models\StockMovement;
use App\Features\Inventory\Models\StockOpname;
use App\Features\Inventory\Models\StockReceipt;
use App\Features\Inventory\Models\StockTransfer;
use App\Features\MonthEnd\Enums\PeriodStatus;
use App\Features\MonthEnd\Models\InventoryPeriod;
use App\Features\MonthEnd\Models\InventoryPeriodSnapshot;
use App\Features\Product\Models\Product;
use App\Shared\Exceptions\DomainException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CloseInventoryPeriodAction
{
    /**
     * Eksekusi Tutup Buku Bulanan.
     *
     * @param int $year
     * @param int $month
     * @param int $userId
     * @param string|null $notes
     * @param bool $force
     * @return InventoryPeriod
     * @throws DomainException
     */
    public function execute(int $year, int $month, int $userId, ?string $notes = null, bool $force = false): InventoryPeriod
    {
        if ($month < 1 || $month > 12) {
            throw new DomainException('Bulan tidak valid (harus 1-12).', 422);
        }

        if ($year < 2020 || $year > 2099) {
            throw new DomainException('Tahun tidak valid.', 422);
        }

        $periodKey = sprintf('%04d-%02d', $year, $month);
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        // Validasi jika periode sudah ditutup
        /** @var InventoryPeriod|null $existingPeriod */
        $existingPeriod = InventoryPeriod::query()->where('period_key', $periodKey)->first();
        if ($existingPeriod instanceof InventoryPeriod && $existingPeriod->isClosed()) {
            throw new DomainException("Periode {$periodKey} sudah dalam status DITUTUP (CLOSED).", 422);
        }

        // Cek transaksi draft yang belum diposting jika tidak di-force
        if (! $force) {
            $pendingIssues = $this->checkPendingDocuments($startDate, $endDate);
            if (! empty($pendingIssues)) {
                $details = implode(', ', $pendingIssues);
                throw new DomainException(
                    "Terdapat transaksi persediaan yang masih berstatus DRAFT pada periode ini: {$details}. "
                    ."Harap selesaikan/posting transaksi tersebut sebelum melakukan tutup buku, atau centang konfirmasi paksa.",
                    422,
                    ['pending_documents' => $pendingIssues]
                );
            }
        }

        return DB::transaction(function () use ($periodKey, $year, $month, $startDate, $endDate, $userId, $notes, $existingPeriod) {
            // 1. Buat atau update record InventoryPeriod
            $period = $existingPeriod ?? new InventoryPeriod();
            $period->period_key = $periodKey;
            $period->year = $year;
            $period->month = $month;
            $period->start_date = $startDate;
            $period->end_date = $endDate;
            $period->status = PeriodStatus::CLOSED;
            $period->closed_at = Carbon::now();
            $period->closed_by = $userId;
            $period->notes = $notes;
            $period->save();

            // 2. Kumpulkan snapshot per produk, lokasi, dan kondisi
            $this->generateSnapshots($period, $startDate, $endDate);

            return $period->load(['snapshots.product', 'snapshots.location', 'closedByUser']);
        });
    }

    /**
     * Memeriksa keberadaan dokumen draft dalam rentang tanggal periode.
     */
    public function checkPendingDocuments(string $startDate, string $endDate): array
    {
        $pending = [];

        $draftReceipts = StockReceipt::where('status', ReceiptStatus::DRAFT->value)
            ->whereBetween('date', [$startDate, $endDate])
            ->count();
        if ($draftReceipts > 0) {
            $pending[] = "{$draftReceipts} Penerimaan Barang (Receipts) Draft";
        }

        $draftIssues = StockIssue::where('status', IssueStatus::DRAFT->value)
            ->whereBetween('date', [$startDate, $endDate])
            ->count();
        if ($draftIssues > 0) {
            $pending[] = "{$draftIssues} Pengeluaran Barang (Issues) Draft";
        }

        $draftTransfers = StockTransfer::whereIn('status', [TransferStatus::DRAFT->value, TransferStatus::IN_TRANSIT->value])
            ->whereBetween('transfer_date', [$startDate, $endDate])
            ->count();
        if ($draftTransfers > 0) {
            $pending[] = "{$draftTransfers} Transfer Antar Gudang Draft/In-Transit";
        }

        $draftAdjustments = StockAdjustment::where('status', AdjustmentStatus::DRAFT->value)
            ->whereBetween('adjustment_date', [$startDate, $endDate])
            ->count();
        if ($draftAdjustments > 0) {
            $pending[] = "{$draftAdjustments} Stock Adjustment Draft";
        }

        $pendingOpnames = StockOpname::whereIn('status', [OpnameStatus::DRAFT->value, OpnameStatus::IN_PROGRESS->value, OpnameStatus::COUNTED->value])
            ->whereBetween('opname_date', [$startDate, $endDate])
            ->count();
        if ($pendingOpnames > 0) {
            $pending[] = "{$pendingOpnames} Stock Opname Belum Diposting";
        }

        return $pending;
    }

    /**
     * Hitung dan simpan snapshot saldo persediaan.
     */
    protected function generateSnapshots(InventoryPeriod $period, string $startDate, string $endDate): void
    {
        // Hapus snapshot lama jika ada (misal re-close)
        InventoryPeriodSnapshot::where('inventory_period_id', $period->id)->delete();

        // Ambil harga satuan seluruh produk yang aktif/ada
        $productPrices = Product::pluck('unit_price', 'id')->toArray();

        // Ambil snapshot periode sebelumnya untuk opening_balance bila ada
        $prevMonthDate = Carbon::parse($startDate)->subMonth();
        $prevPeriodKey = sprintf('%04d-%02d', $prevMonthDate->year, $prevMonthDate->month);
        $prevPeriod = InventoryPeriod::where('period_key', $prevPeriodKey)->first();

        $prevBalances = [];
        if ($prevPeriod) {
            $prevBalances = InventoryPeriodSnapshot::where('inventory_period_id', $prevPeriod->id)
                ->get()
                ->keyBy(function ($row) {
                    $cond = $row->condition instanceof StockCondition ? $row->condition->value : (string) $row->condition;
                    return "{$row->product_id}_{$row->location_id}_{$cond}";
                })
                ->map(fn ($row) => (float) $row->closing_balance)
                ->toArray();
        }

        // Ambil data mutasi selama periode ini
        $movements = StockMovement::query()
            ->whereBetween('occurred_at', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"])
            ->select([
                'product_id',
                'location_id',
                'condition',
                'movement_type',
                DB::raw('SUM(quantity) as total_qty'),
            ])
            ->groupBy('product_id', 'location_id', 'condition', 'movement_type')
            ->get();

        $movementSummary = [];
        foreach ($movements as $m) {
            $cond = $m->condition instanceof StockCondition ? $m->condition->value : (string) $m->condition;
            $key = "{$m->product_id}_{$m->location_id}_{$cond}";

            if (! isset($movementSummary[$key])) {
                $movementSummary[$key] = [
                    'product_id' => $m->product_id,
                    'location_id' => $m->location_id,
                    'condition' => $cond,
                    'in' => 0.0,
                    'out' => 0.0,
                    'adj' => 0.0,
                ];
            }

            $qty = (float) $m->total_qty;
            $type = $m->movement_type;

            // Klasifikasi masuk vs keluar vs penyesuaian
            if (in_array($type, [
                MovementType::RECEIPT->value,
                MovementType::RECEIPT_GA->value,
                MovementType::TRANSFER_IN->value,
                MovementType::RETURN_TO_WAREHOUSE->value,
                MovementType::REPLACEMENT_PULL->value,
            ], true)) {
                $movementSummary[$key]['in'] += $qty;
            } elseif (in_array($type, [
                MovementType::ISSUE->value,
                MovementType::TRANSFER_OUT->value,
                MovementType::STORE_ALLOCATION->value,
            ], true)) {
                $movementSummary[$key]['out'] += $qty;
            } elseif (in_array($type, [
                MovementType::ADJUSTMENT_IN->value,
                MovementType::OPNAME_IN->value,
            ], true)) {
                $movementSummary[$key]['adj'] += $qty;
                $movementSummary[$key]['in'] += $qty;
            } elseif (in_array($type, [
                MovementType::ADJUSTMENT_OUT->value,
                MovementType::OPNAME_OUT->value,
            ], true)) {
                $movementSummary[$key]['adj'] -= $qty;
                $movementSummary[$key]['out'] += $qty;
            }
        }

        // Ambil seluruh saldo berjalan dari inventory_balances
        $liveBalances = InventoryBalance::query()->get();
        $targetKeys = [];

        foreach ($liveBalances as $b) {
            $cond = $b->condition instanceof StockCondition ? $b->condition->value : (string) $b->condition;
            $key = "{$b->product_id}_{$b->location_id}_{$cond}";
            $targetKeys[$key] = [
                'product_id' => $b->product_id,
                'location_id' => $b->location_id,
                'condition' => $cond,
                'current_balance' => (float) $b->quantity,
            ];
        }

        // Gabungkan kombinasi dari mutasi yang mungkin saldonya saat ini 0 tapi ada riwayat
        foreach ($movementSummary as $key => $mData) {
            if (! isset($targetKeys[$key])) {
                $targetKeys[$key] = [
                    'product_id' => $mData['product_id'],
                    'location_id' => $mData['location_id'],
                    'condition' => $mData['condition'],
                    'current_balance' => 0.0,
                ];
            }
        }

        $snapshotRows = [];
        $now = Carbon::now();

        foreach ($targetKeys as $key => $info) {
            $prodId = $info['product_id'];
            $locId = $info['location_id'];
            $cond = $info['condition'];
            $currentBal = $info['current_balance'];

            $mIn = $movementSummary[$key]['in'] ?? 0.0;
            $mOut = $movementSummary[$key]['out'] ?? 0.0;
            $mAdj = $movementSummary[$key]['adj'] ?? 0.0;

            // Jika ada saldo awal dari periode sebelumnya
            if (isset($prevBalances[$key])) {
                $openingBal = (float) $prevBalances[$key];
                $closingBal = $openingBal + $mIn - $mOut;
            } else {
                // Estimasi saldo awal berdasarkan mutasi periode ini
                $closingBal = $currentBal;
                $openingBal = max(0.0, $closingBal - $mIn + $mOut);
            }

            // Jika semua 0, lewati agar snapshot ringkas
            if ($openingBal == 0 && $closingBal == 0 && $mIn == 0 && $mOut == 0) {
                continue;
            }

            $unitPrice = (float) ($productPrices[$prodId] ?? 0);
            $totalValue = round($closingBal * $unitPrice, 2);

            $snapshotRows[] = [
                'inventory_period_id' => $period->id,
                'product_id' => $prodId,
                'location_id' => $locId,
                'condition' => $cond,
                'opening_balance' => $openingBal,
                'total_in' => $mIn,
                'total_out' => $mOut,
                'total_adjustment' => $mAdj,
                'closing_balance' => $closingBal,
                'unit_price' => $unitPrice,
                'total_value' => $totalValue,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($snapshotRows)) {
            // Bulk insert chunk
            foreach (array_chunk($snapshotRows, 250) as $chunk) {
                InventoryPeriodSnapshot::insert($chunk);
            }
        }
    }
}
