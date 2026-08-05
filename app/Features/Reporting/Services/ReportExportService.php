<?php

namespace App\Features\Reporting\Services;

use App\Features\Inventory\Enums\AdjustmentReason;
use App\Features\Reporting\Exports\CsvStreamWriter;
use App\Features\Reporting\Helpers\DecimalQuantity;
use App\Features\Reporting\Repositories\Contracts\ReportingRepositoryInterface;
use Carbon\CarbonImmutable;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function __construct(
        private readonly ReportingRepositoryInterface $repository
    ) {}

    public function exportBalances(array $allowedLocationIds, array $filters): StreamedResponse
    {
        $sortField = $filters['sort_by'] ?? 'id';
        $sortDirection = $filters['sort_order'] ?? 'desc';

        $cursor = $this->repository->getCursorBalances($allowedLocationIds, $filters, $sortField, $sortDirection);

        $headers = [
            'SKU', 'Nama Produk', 'Kategori', 'Satuan',
            'Kode Lokasi', 'Nama Lokasi', 'Saldo', 'Stok Minimum',
            'Status Produk', 'Status Lokasi',
        ];

        $generator = function () use ($cursor) {
            foreach ($cursor as $item) {
                yield [
                    $item->product->sku ?? '',
                    $item->product->name ?? '',
                    $item->product->category->name ?? '',
                    $item->product->unit->name ?? '',
                    $item->location->code ?? '',
                    $item->location->name ?? '',
                    DecimalQuantity::normalize($item->quantity),
                    DecimalQuantity::normalize($item->product->minimum_stock ?? 0),
                    ($item->product->is_active ?? true) ? 'Aktif' : 'Nonaktif',
                    ($item->location->operationLock?->is_frozen) ? 'Dibekukan' : 'Normal',
                ];
            }
        };

        return $this->downloadStream('inventory-balances', $headers, $generator());
    }

    public function exportLowStock(array $allowedLocationIds, array $filters): StreamedResponse
    {
        $sortField = $filters['sort_by'] ?? 'shortage_quantity';
        $sortDirection = $filters['sort_order'] ?? 'desc';

        $cursor = $this->repository->getCursorLowStock($allowedLocationIds, $filters, $sortField, $sortDirection);

        $headers = [
            'SKU', 'Nama Produk', 'Kategori', 'Satuan',
            'Kode Lokasi', 'Nama Lokasi', 'Saldo Tersedia', 'Stok Minimum',
            'Kekurangan', 'Status Produk',
        ];

        $generator = function () use ($cursor) {
            foreach ($cursor as $item) {
                yield [
                    $item->sku ?? '',
                    $item->product_name ?? '',
                    $item->category_name ?? '',
                    $item->unit_name ?? '',
                    $item->location_code ?? '',
                    $item->location_name ?? '',
                    DecimalQuantity::normalize($item->on_hand_quantity),
                    DecimalQuantity::normalize($item->minimum_stock),
                    DecimalQuantity::normalize($item->shortage_quantity),
                    $item->is_product_active ? 'Aktif' : 'Nonaktif',
                ];
            }
        };

        return $this->downloadStream('low-stock', $headers, $generator());
    }

    public function exportStockCard(array $filters): StreamedResponse
    {
        $productId = (int) $filters['product_id'];
        $locationId = (int) $filters['location_id'];

        $startDate = $filters['start_date'] ?? CarbonImmutable::now('Asia/Jakarta')->startOfMonth()->toDateString();
        $endDate = $filters['end_date'] ?? CarbonImmutable::now('Asia/Jakarta')->toDateString();

        $startDateTime = CarbonImmutable::parse($startDate, 'Asia/Jakarta')->startOfDay()->format('Y-m-d H:i:s');
        $endNextDayDateTime = CarbonImmutable::parse($endDate, 'Asia/Jakarta')->startOfDay()->addDay()->format('Y-m-d H:i:s');

        $cursor = $this->repository->getCursorStockCardMovements(
            $productId,
            $locationId,
            $startDateTime,
            $endNextDayDateTime
        );

        $headers = [
            'Tanggal Dokumen', 'Waktu Posting', 'Jenis Movement', 'Nomor Referensi',
            'SKU', 'Nama Produk', 'Kode Lokasi', 'Nama Lokasi',
            'Quantity Sebelum', 'Quantity Masuk', 'Quantity Keluar', 'Quantity Sesudah',
            'Pengguna', 'Catatan',
        ];

        $generator = function () use ($cursor) {
            foreach ($cursor as $m) {
                $change = (float) $m->quantity_change;
                $qtyIn = $change > 0 ? DecimalQuantity::normalize($m->quantity) : '0.0000';
                $qtyOut = $change < 0 ? DecimalQuantity::normalize($m->quantity) : '0.0000';
                $postedAt = $m->created_at ? CarbonImmutable::parse($m->created_at, 'Asia/Jakarta')->format('Y-m-d H:i:s') : '-';

                yield [
                    $m->occurred_at ? CarbonImmutable::parse($m->occurred_at, 'Asia/Jakarta')->format('Y-m-d') : '-',
                    $postedAt,
                    $m->movement_type,
                    $m->movement_id,
                    $m->product->sku ?? '',
                    $m->product->name ?? '',
                    $m->location->code ?? '',
                    $m->location->name ?? '',
                    DecimalQuantity::normalize($m->quantity_before),
                    $qtyIn,
                    $qtyOut,
                    DecimalQuantity::normalize($m->quantity_after),
                    $m->creator->name ?? ($m->creator->username ?? '-'),
                    $m->notes ?? '',
                ];
            }
        };

        return $this->downloadStream('stock-card', $headers, $generator());
    }

    public function exportStockReceipts(array $allowedLocationIds, array $filters): StreamedResponse
    {
        $sortField = $filters['sort_by'] ?? 'posted_at';
        $sortDirection = $filters['sort_order'] ?? 'desc';

        $cursor = $this->repository->getCursorStockReceiptReport($allowedLocationIds, $filters, $sortField, $sortDirection);

        $headers = [
            'Nomor Penerimaan', 'Tanggal Dokumen', 'Waktu Posting', 'Supplier',
            'Kode Lokasi', 'Nama Lokasi', 'SKU', 'Nama Produk', 'Satuan',
            'Quantity', 'Dibuat Oleh', 'Diposting Oleh', 'Catatan',
        ];

        $generator = function () use ($cursor) {
            foreach ($cursor as $item) {
                $postedAt = $item->movement_posted_at
                    ? CarbonImmutable::parse($item->movement_posted_at, 'Asia/Jakarta')->format('Y-m-d H:i:s')
                    : '-';
                $docDate = $item->receipt->date
                    ? CarbonImmutable::parse($item->receipt->date, 'Asia/Jakarta')->format('Y-m-d')
                    : '-';

                yield [
                    $item->receipt->receipt_number ?? '',
                    $docDate,
                    $postedAt,
                    $item->receipt->supplier->name ?? '',
                    $item->location->code ?? '',
                    $item->location->name ?? '',
                    $item->product->sku ?? '',
                    $item->product->name ?? '',
                    $item->product->unit->name ?? '',
                    DecimalQuantity::normalize($item->quantity),
                    $item->receipt->creator->name ?? ($item->receipt->creator->username ?? '-'),
                    $item->receipt->poster->name ?? ($item->receipt->poster->username ?? '-'),
                    $item->receipt->notes ?? '',
                ];
            }
        };

        return $this->downloadStream('stock-receipts', $headers, $generator());
    }

    public function exportStockIssues(array $allowedLocationIds, array $filters): StreamedResponse
    {
        $sortField = $filters['sort_by'] ?? 'posted_at';
        $sortDirection = $filters['sort_order'] ?? 'desc';

        $cursor = $this->repository->getCursorStockIssueReport($allowedLocationIds, $filters, $sortField, $sortDirection);

        $headers = [
            'Nomor Pengeluaran', 'Tanggal Dokumen', 'Waktu Posting',
            'Kode Lokasi', 'Nama Lokasi', 'Tujuan', 'SKU', 'Nama Produk', 'Satuan',
            'Quantity', 'Dibuat Oleh', 'Diposting Oleh', 'Catatan',
        ];

        $generator = function () use ($cursor) {
            foreach ($cursor as $item) {
                $postedAt = $item->movement_posted_at
                    ? CarbonImmutable::parse($item->movement_posted_at, 'Asia/Jakarta')->format('Y-m-d H:i:s')
                    : '-';
                $docDate = $item->issue->date
                    ? CarbonImmutable::parse($item->issue->date, 'Asia/Jakarta')->format('Y-m-d')
                    : '-';

                yield [
                    $item->issue->issue_number ?? '',
                    $docDate,
                    $postedAt,
                    $item->location->code ?? '',
                    $item->location->name ?? '',
                    $item->issue->purpose ?? '',
                    $item->product->sku ?? '',
                    $item->product->name ?? '',
                    $item->product->unit->name ?? '',
                    DecimalQuantity::normalize($item->quantity),
                    $item->issue->creator->name ?? ($item->issue->creator->username ?? '-'),
                    $item->issue->poster->name ?? ($item->issue->poster->username ?? '-'),
                    $item->issue->notes ?? '',
                ];
            }
        };

        return $this->downloadStream('stock-issues', $headers, $generator());
    }

    public function exportStockTransfers(array $allowedLocationIds, array $filters): StreamedResponse
    {
        $sortField = $filters['sort_by'] ?? 'sent_at';
        $sortDirection = $filters['sort_order'] ?? 'desc';

        $cursor = $this->repository->getCursorStockTransferReport($allowedLocationIds, $filters, $sortField, $sortDirection);

        $headers = [
            'Nomor Transfer', 'Tanggal Dokumen', 'Status', 'Lokasi Asal', 'Lokasi Tujuan',
            'SKU', 'Nama Produk', 'Satuan', 'Quantity',
            'Dikirim Oleh', 'Waktu Dikirim', 'Diterima Oleh', 'Waktu Diterima',
            'Status Dalam Perjalanan', 'Durasi Transit Detik',
        ];

        $generator = function () use ($cursor) {
            foreach ($cursor as $item) {
                $transfer = $item->transfer;
                $sentAt = $transfer->sent_at ? CarbonImmutable::parse($transfer->sent_at, 'Asia/Jakarta')->format('Y-m-d H:i:s') : '-';
                $receivedAt = $transfer->received_at ? CarbonImmutable::parse($transfer->received_at, 'Asia/Jakarta')->format('Y-m-d H:i:s') : '-';
                $docDate = $transfer->transfer_date ? CarbonImmutable::parse($transfer->transfer_date, 'Asia/Jakarta')->format('Y-m-d') : '-';

                $isInTransit = ($transfer->status === 'SENT');
                $transitSeconds = null;
                if ($transfer->sent_at) {
                    $start = CarbonImmutable::parse($transfer->sent_at);
                    $end = $transfer->received_at ? CarbonImmutable::parse($transfer->received_at) : CarbonImmutable::now();
                    $transitSeconds = max(0, $start::parse($start)->diffInSeconds($end));
                }

                yield [
                    $transfer->transfer_number ?? '',
                    $docDate,
                    $transfer->status ?? '',
                    $transfer->originLocation->name ?? '',
                    $transfer->destinationLocation->name ?? '',
                    $item->product->sku ?? '',
                    $item->product->name ?? '',
                    $item->product->unit->name ?? '',
                    DecimalQuantity::normalize($item->quantity),
                    $transfer->sender->name ?? ($transfer->sender->username ?? '-'),
                    $sentAt,
                    $transfer->receiver->name ?? ($transfer->receiver->username ?? '-'),
                    $receivedAt,
                    $isInTransit ? 'Ya' : 'Tidak',
                    $transitSeconds !== null ? (string) $transitSeconds : '-',
                ];
            }
        };

        return $this->downloadStream('stock-transfers', $headers, $generator());
    }

    public function exportStockAdjustments(array $allowedLocationIds, array $filters): StreamedResponse
    {
        $sortField = $filters['sort_by'] ?? 'posted_at';
        $sortDirection = $filters['sort_order'] ?? 'desc';

        $cursor = $this->repository->getCursorStockAdjustmentReport($allowedLocationIds, $filters, $sortField, $sortDirection);

        $headers = [
            'Nomor Adjustment', 'Tanggal Dokumen', 'Waktu Posting', 'Direction', 'Reason Code',
            'Alasan', 'Kode Lokasi', 'Nama Lokasi', 'SKU', 'Nama Produk', 'Satuan',
            'Quantity', 'Diposting Oleh', 'Catatan',
        ];

        $generator = function () use ($cursor) {
            foreach ($cursor as $item) {
                $adj = $item->adjustment;
                $postedAt = $adj->posted_at ? CarbonImmutable::parse($adj->posted_at, 'Asia/Jakarta')->format('Y-m-d H:i:s') : '-';
                $docDate = $adj->adjustment_date ? CarbonImmutable::parse($adj->adjustment_date, 'Asia/Jakarta')->format('Y-m-d') : '-';

                $reasonCode = $adj->reason_code ?? '';
                $reasonLabel = $reasonCode;
                try {
                    $reasonEnum = AdjustmentReason::tryFrom($reasonCode);
                    if ($reasonEnum) {
                        $reasonLabel = $reasonEnum->label();
                    }
                } catch (\Throwable) {
                }

                yield [
                    $adj->adjustment_number ?? '',
                    $docDate,
                    $postedAt,
                    $adj->direction ?? '',
                    $reasonCode,
                    $reasonLabel,
                    $adj->location->code ?? '',
                    $adj->location->name ?? '',
                    $item->product->sku ?? '',
                    $item->product->name ?? '',
                    $item->product->unit->name ?? '',
                    DecimalQuantity::normalize($item->quantity),
                    $adj->poster->name ?? ($adj->poster->username ?? '-'),
                    $adj->notes ?? '',
                ];
            }
        };

        return $this->downloadStream('stock-adjustments', $headers, $generator());
    }

    public function exportStockOpnames(array $allowedLocationIds, array $filters): StreamedResponse
    {
        $sortField = $filters['sort_by'] ?? 'posted_at';
        $sortDirection = $filters['sort_order'] ?? 'desc';

        $cursor = $this->repository->getCursorStockOpnameReport($allowedLocationIds, $filters, $sortField, $sortDirection);

        $headers = [
            'Nomor Opname', 'Tanggal Dokumen', 'Waktu Posting', 'Kode Lokasi', 'Nama Lokasi',
            'SKU', 'Nama Produk', 'Satuan', 'Snapshot Quantity', 'Counted Quantity',
            'Signed Variance', 'Movement Direction', 'Produk Tidak Terduga',
            'Dihitung Oleh', 'Diposting Oleh', 'Catatan',
        ];

        $generator = function () use ($cursor) {
            foreach ($cursor as $item) {
                $opname = $item->opname;
                $postedAt = $opname->posted_at ? CarbonImmutable::parse($opname->posted_at, 'Asia/Jakarta')->format('Y-m-d H:i:s') : '-';
                $docDate = $opname->opname_date ? CarbonImmutable::parse($opname->opname_date, 'Asia/Jakarta')->format('Y-m-d') : '-';

                $variance = DecimalQuantity::normalize($item->variance_quantity);
                $signedVariance = ((float) $item->variance_quantity >= 0 ? '+' : '').$variance;

                $movementDirection = '-';
                if ((float) $item->variance_quantity > 0) {
                    $movementDirection = 'Selisih Masuk';
                } elseif ((float) $item->variance_quantity < 0) {
                    $movementDirection = 'Selisih Keluar';
                }

                yield [
                    $opname->opname_number ?? '',
                    $docDate,
                    $postedAt,
                    $opname->location->code ?? '',
                    $opname->location->name ?? '',
                    $item->product->sku ?? '',
                    $item->product->name ?? '',
                    $item->product->unit->name ?? '',
                    DecimalQuantity::normalize($item->snapshot_quantity),
                    DecimalQuantity::normalize($item->counted_quantity),
                    $signedVariance,
                    $movementDirection,
                    $item->is_unexpected ? 'Ya' : 'Tidak',
                    $item->counter->name ?? ($item->counter->username ?? '-'),
                    $opname->poster->name ?? ($opname->poster->username ?? '-'),
                    $item->item_notes ?? ($opname->notes ?? ''),
                ];
            }
        };

        return $this->downloadStream('stock-opnames', $headers, $generator());
    }

    private function downloadStream(string $slug, array $headers, iterable $rows): StreamedResponse
    {
        $timestamp = CarbonImmutable::now('Asia/Jakarta')->format('Ymd-His');
        $filename = "{$slug}-{$timestamp}.csv";

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            CsvStreamWriter::writeStream($handle, $headers, $rows);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
