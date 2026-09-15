<?php

namespace App\Features\MonthEnd\Http\Controllers;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\MonthEnd\Actions\CloseInventoryPeriodAction;
use App\Features\MonthEnd\Actions\ReopenInventoryPeriodAction;
use App\Features\MonthEnd\Enums\PeriodStatus;
use App\Features\MonthEnd\Http\Requests\ClosePeriodRequest;
use App\Features\MonthEnd\Http\Requests\ReopenPeriodRequest;
use App\Features\MonthEnd\Models\InventoryPeriod;
use App\Features\MonthEnd\Models\InventoryPeriodSnapshot;
use App\Http\Controllers\Controller;
use App\Shared\Http\Responses\ApiResponse;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonthEndController extends Controller
{
    use AuthorizesRequests;
    /**
     * Tampilkan daftar periode tutup buku.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize(PermissionCode::MONTH_END_VIEW->value);

        $periods = InventoryPeriod::query()
            ->with(['closedByUser:id,name,username', 'reopenedByUser:id,name,username'])
            ->withCount('snapshots')
            ->withSum('snapshots as total_valuation', 'total_value')
            ->withSum('snapshots as total_closing_qty', 'closing_balance')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function (InventoryPeriod $period) {
                return [
                    'id' => $period->id,
                    'period_key' => $period->period_key,
                    'year' => $period->year,
                    'month' => $period->month,
                    'month_name' => $period->month_name,
                    'start_date' => $period->start_date?->toDateString(),
                    'end_date' => $period->end_date?->toDateString(),
                    'status' => $period->status instanceof PeriodStatus ? $period->status->value : (string) $period->status,
                    'status_label' => $period->status instanceof PeriodStatus ? $period->status->label() : ($period->status === 'CLOSED' ? 'Ditutup (Closed)' : 'Terbuka (Open)'),
                    'closed_at' => $period->closed_at?->toIso8601String(),
                    'closed_by' => $period->closedByUser ? [
                        'id' => $period->closedByUser->id,
                        'name' => $period->closedByUser->name,
                        'username' => $period->closedByUser->username,
                    ] : null,
                    'reopened_at' => $period->reopened_at?->toIso8601String(),
                    'reopened_by' => $period->reopenedByUser ? [
                        'id' => $period->reopenedByUser->id,
                        'name' => $period->reopenedByUser->name,
                        'username' => $period->reopenedByUser->username,
                    ] : null,
                    'reopen_reason' => $period->reopen_reason,
                    'notes' => $period->notes,
                    'snapshots_count' => (int) $period->snapshots_count,
                    'total_valuation' => (float) ($period->total_valuation ?? 0),
                    'total_closing_qty' => (float) ($period->total_closing_qty ?? 0),
                ];
            });

        return ApiResponse::success($periods);
    }

    /**
     * Ringkasan status periode & cek pre-closing warning.
     */
    public function summary(Request $request, CloseInventoryPeriodAction $action): JsonResponse
    {
        $this->authorize(PermissionCode::MONTH_END_VIEW->value);

        $now = Carbon::now();
        $targetYear = (int) $request->input('year', $now->year);
        $targetMonth = (int) $request->input('month', $now->month);

        $startDate = Carbon::createFromDate($targetYear, $targetMonth, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromDate($targetYear, $targetMonth, 1)->endOfMonth()->toDateString();
        $periodKey = sprintf('%04d-%02d', $targetYear, $targetMonth);

        /** @var InventoryPeriod|null $period */
        $period = InventoryPeriod::query()->where('period_key', $periodKey)->first();
        $pendingDocs = $action->checkPendingDocuments($startDate, $endDate);

        $isClosed = $period instanceof InventoryPeriod && $period->isClosed();
        $status = $period instanceof InventoryPeriod && $period->status instanceof PeriodStatus
            ? $period->status->value
            : ($period?->status ?? 'OPEN');

        return ApiResponse::success([
            'target_period' => [
                'period_key' => $periodKey,
                'year' => $targetYear,
                'month' => $targetMonth,
                'month_name' => Carbon::createFromDate($targetYear, $targetMonth, 1)->locale('id')->translatedFormat('F Y'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_closed' => $isClosed,
                'status' => $status,
            ],
            'pending_documents' => $pendingDocs,
            'pending_count' => count($pendingDocs),
        ]);
    }

    /**
     * Dapatkan snapshot saldo per produk & lokasi untuk periode tertentu.
     */
    public function snapshots(Request $request, int $id): JsonResponse
    {
        $this->authorize(PermissionCode::MONTH_END_VIEW->value);

        $period = InventoryPeriod::with(['closedByUser:id,name', 'reopenedByUser:id,name'])->findOrFail($id);

        $query = InventoryPeriodSnapshot::query()
            ->where('inventory_period_id', $period->id)
            ->with([
                'product:id,sku,name,barcode,unit_price,unit_id,category_id',
                'product.unit:id,name,symbol',
                'product.category:id,name',
                'location:id,code,name,type',
            ]);

        // Filter lokasi
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        // Filter kondisi
        if ($request->filled('condition')) {
            $query->where('condition', $request->input('condition'));
        }

        // Search SKU / Nama Produk
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Total ringkasan sebelum pagination
        $summary = (clone $query)->selectRaw('
            COUNT(*) as total_items,
            COALESCE(SUM(closing_balance), 0) as total_closing_qty,
            COALESCE(SUM(total_value), 0) as total_valuation,
            COALESCE(SUM(opening_balance), 0) as total_opening_qty,
            COALESCE(SUM(total_in), 0) as total_in_qty,
            COALESCE(SUM(total_out), 0) as total_out_qty
        ')->first();

        $perPage = max(1, min(500, (int) $request->input('per_page', 50)));
        $snapshots = $query->paginate($perPage);

        return ApiResponse::success([
            'period' => [
                'id' => $period->id,
                'period_key' => $period->period_key,
                'year' => $period->year,
                'month' => $period->month,
                'month_name' => $period->month_name,
                'start_date' => $period->start_date?->toDateString(),
                'end_date' => $period->end_date?->toDateString(),
                'status' => $period->status instanceof PeriodStatus ? $period->status->value : (string) $period->status,
                'closed_at' => $period->closed_at?->toIso8601String(),
                'closed_by' => $period->closedByUser?->name,
                'notes' => $period->notes,
            ],
            'summary' => [
                'total_items' => (int) ($summary->total_items ?? 0),
                'total_closing_qty' => (float) ($summary->total_closing_qty ?? 0),
                'total_valuation' => (float) ($summary->total_valuation ?? 0),
                'total_opening_qty' => (float) ($summary->total_opening_qty ?? 0),
                'total_in_qty' => (float) ($summary->total_in_qty ?? 0),
                'total_out_qty' => (float) ($summary->total_out_qty ?? 0),
            ],
            'snapshots' => $snapshots->items(),
            'pagination' => [
                'current_page' => $snapshots->currentPage(),
                'per_page' => $snapshots->perPage(),
                'total' => $snapshots->total(),
                'last_page' => $snapshots->lastPage(),
            ],
        ]);
    }

    /**
     * Eksekusi Tutup Buku.
     */
    public function close(ClosePeriodRequest $request, CloseInventoryPeriodAction $action): JsonResponse
    {
        $period = $action->execute(
            year: (int) $request->input('year'),
            month: (int) $request->input('month'),
            userId: $request->user()->id,
            notes: $request->input('notes'),
            force: $request->boolean('force', false)
        );

        return ApiResponse::success(
            data: $period,
            message: "Periode {$period->period_key} ({$period->month_name}) berhasil ditutup buku dan saldo akhir telah dibekukan."
        );
    }

    /**
     * Eksekusi Buka Kembali (Re-Open) Periode.
     */
    public function reopen(ReopenPeriodRequest $request, int $id, ReopenInventoryPeriodAction $action): JsonResponse
    {
        $period = $action->execute(
            periodId: $id,
            userId: $request->user()->id,
            reopenReason: $request->input('reopen_reason')
        );

        return ApiResponse::success(
            data: $period,
            message: "Periode {$period->period_key} ({$period->month_name}) berhasil dibuka kembali (OPEN)."
        );
    }
}
