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
                    $item->sku ?? '',
                    $item->product_name ?? '',
                    $item->category_name ?? '',
                    $item->unit_name ?? '',
                    $item->location_code ?? '',
                    $item->location_name ?? '',
                    DecimalQuantity::normalize($item->quantity),
                    DecimalQuantity::normalize($item->minimum_stock ?? 0),
                    ($item->is_product_active ?? true) ? 'Aktif' : 'Nonaktif',
                    ($item->is_frozen ?? false) ? 'Dibekukan' : 'Normal',
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

    public function exportStockCard(array $allowedLocationIds, array $filters): StreamedResponse
    {
        $productId = (int) $filters['product_id'];
        $locationId = (int) $filters['location_id'];

        $startDate = $filters['start_date'] ?? CarbonImmutable::now('Asia/Jakarta')->startOfMonth()->toDateString();
        $endDate = $filters['end_date'] ?? CarbonImmutable::now('Asia/Jakarta')->toDateString();

        $startDateTime = CarbonImmutable::parse($startDate, 'Asia/Jakarta')->startOfDay()->format('Y-m-d H:i:s');
        $endNextDayDateTime = CarbonImmutable::parse($endDate, 'Asia/Jakarta')->startOfDay()->addDay()->format('Y-m-d H:i:s');

        $cursor = $this->repository->getCursorStockCardMovements(
            $allowedLocationIds,
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
                $quantityBefore = DecimalQuantity::normalize((string) $m->quantity_before);
                $quantityAfter = DecimalQuantity::normalize((string) $m->quantity_after);

                $delta = bcsub($quantityAfter, $quantityBefore, 4);

                if (bccomp($delta, '0.0000', 4) > 0) {
                    $quantityIn = $delta;
                    $quantityOut = '0.0000';
                } elseif (bccomp($delta, '0.0000', 4) < 0) {
                    $quantityIn = '0.0000';
                    $quantityOut = bcsub('0.0000', $delta, 4);
                } else {
                    $quantityIn = '0.0000';
                    $quantityOut = '0.0000';
                }

                $postedAt = $m->created_at ? CarbonImmutable::parse($m->created_at, 'Asia/Jakarta')->format('Y-m-d H:i:s') : '-';
                $refNumber = ! empty($m->reference_number) ? $m->reference_number : ($m->movement_id ?? '');

                yield [
                    $m->occurred_at ? CarbonImmutable::parse($m->occurred_at, 'Asia/Jakarta')->format('Y-m-d') : '-',
                    $postedAt,
                    $m->movement_type,
                    $refNumber,
                    $m->sku ?? '',
                    $m->product_name ?? '',
                    $m->location_code ?? '',
                    $m->location_name ?? '',
                    $quantityBefore,
                    $quantityIn,
                    $quantityOut,
                    $quantityAfter,
                    $m->creator_name ?? ($m->creator_username ?? '-'),
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
                $docDate = $item->receipt_date
                    ? CarbonImmutable::parse($item->receipt_date, 'Asia/Jakarta')->format('Y-m-d')
                    : '-';

                yield [
                    $item->receipt_number ?? '',
                    $docDate,
                    $postedAt,
                    $item->supplier_name ?? '',
                    $item->location_code ?? '',
                    $item->location_name ?? '',
                    $item->sku ?? '',
                    $item->product_name ?? '',
                    $item->unit_name ?? '',
                    DecimalQuantity::normalize($item->quantity),
                    $item->creator_name ?? ($item->creator_username ?? '-'),
                    $item->poster_name ?? ($item->poster_username ?? '-'),
                    $item->notes ?? '',
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
                $docDate = $item->issue_date
                    ? CarbonImmutable::parse($item->issue_date, 'Asia/Jakarta')->format('Y-m-d')
                    : '-';

                yield [
                    $item->issue_number ?? '',
                    $docDate,
                    $postedAt,
                    $item->location_code ?? '',
                    $item->location_name ?? '',
                    $item->purpose ?? '',
                    $item->sku ?? '',
                    $item->product_name ?? '',
                    $item->unit_name ?? '',
                    DecimalQuantity::normalize($item->quantity),
                    $item->creator_name ?? ($item->creator_username ?? '-'),
                    $item->poster_name ?? ($item->poster_username ?? '-'),
                    $item->notes ?? '',
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
                $sentAt = $item->sent_at ? CarbonImmutable::parse($item->sent_at, 'Asia/Jakarta')->format('Y-m-d H:i:s') : '-';
                $receivedAt = $item->received_at ? CarbonImmutable::parse($item->received_at, 'Asia/Jakarta')->format('Y-m-d H:i:s') : '-';
                $docDate = $item->transfer_date ? CarbonImmutable::parse($item->transfer_date, 'Asia/Jakarta')->format('Y-m-d') : '-';

                $isInTransit = ($item->status === 'SENT');
                $transitSeconds = null;
                if ($item->sent_at) {
                    $start = CarbonImmutable::parse($item->sent_at);
                    $end = $item->received_at ? CarbonImmutable::parse($item->received_at) : CarbonImmutable::now();
                    $transitSeconds = max(0, $start->diffInSeconds($end));
                }

                yield [
                    $item->transfer_number ?? '',
                    $docDate,
                    $item->status ?? '',
                    $item->origin_location_name ?? '',
                    $item->destination_location_name ?? '',
                    $item->sku ?? '',
                    $item->product_name ?? '',
                    $item->unit_name ?? '',
                    DecimalQuantity::normalize($item->quantity),
                    $item->sender_name ?? ($item->sender_username ?? '-'),
                    $sentAt,
                    $item->receiver_name ?? ($item->receiver_username ?? '-'),
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
                $postedAt = $item->posted_at ? CarbonImmutable::parse($item->posted_at, 'Asia/Jakarta')->format('Y-m-d H:i:s') : '-';
                $docDate = $item->adjustment_date ? CarbonImmutable::parse($item->adjustment_date, 'Asia/Jakarta')->format('Y-m-d') : '-';

                $reasonCode = $item->reason_code ?? '';
                $reasonLabel = $reasonCode;
                try {
                    $reasonEnum = AdjustmentReason::tryFrom($reasonCode);
                    if ($reasonEnum) {
                        $reasonLabel = $reasonEnum->label();
                    }
                } catch (\Throwable) {
                }

                yield [
                    $item->adjustment_number ?? '',
                    $docDate,
                    $postedAt,
                    $item->direction ?? '',
                    $reasonCode,
                    $reasonLabel,
                    $item->location_code ?? '',
                    $item->location_name ?? '',
                    $item->sku ?? '',
                    $item->product_name ?? '',
                    $item->unit_name ?? '',
                    DecimalQuantity::normalize($item->quantity),
                    $item->poster_name ?? ($item->poster_username ?? '-'),
                    $item->notes ?? '',
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
                $postedAt = $item->posted_at ? CarbonImmutable::parse($item->posted_at, 'Asia/Jakarta')->format('Y-m-d H:i:s') : '-';
                $docDate = $item->opname_date ? CarbonImmutable::parse($item->opname_date, 'Asia/Jakarta')->format('Y-m-d') : '-';

                $variance = DecimalQuantity::normalize($item->variance_quantity);
                $signedVariance = ((float) $item->variance_quantity >= 0 ? '+' : '').$variance;

                $movementDirection = '-';
                if ((float) $item->variance_quantity > 0) {
                    $movementDirection = 'Selisih Masuk';
                } elseif ((float) $item->variance_quantity < 0) {
                    $movementDirection = 'Selisih Keluar';
                }

                yield [
                    $item->opname_number ?? '',
                    $docDate,
                    $postedAt,
                    $item->location_code ?? '',
                    $item->location_name ?? '',
                    $item->sku ?? '',
                    $item->product_name ?? '',
                    $item->unit_name ?? '',
                    DecimalQuantity::normalize($item->snapshot_quantity),
                    DecimalQuantity::normalize($item->counted_quantity),
                    $signedVariance,
                    $movementDirection,
                    $item->is_unexpected ? 'Ya' : 'Tidak',
                    $item->counter_name ?? ($item->counter_username ?? '-'),
                    $item->poster_name ?? ($item->poster_username ?? '-'),
                    $item->item_notes ?? ($item->opname_notes ?? ''),
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
