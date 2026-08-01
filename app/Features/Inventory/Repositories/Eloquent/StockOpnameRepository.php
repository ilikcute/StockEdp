<?php

namespace App\Features\Inventory\Repositories\Eloquent;

use App\Features\Inventory\Models\StockOpname;
use App\Features\Inventory\Repositories\Contracts\StockOpnameRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StockOpnameRepository implements StockOpnameRepositoryInterface
{
    public function getPaginatedOpnames(
        array $allowedLocationIds,
        array $filters = [],
        string $sortField = 'id',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = StockOpname::with(['location', 'creator', 'starter', 'completer', 'poster', 'canceler'])
            ->whereIn('location_id', $allowedLocationIds);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('opname_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('opname_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('opname_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $allowlistSorts = ['id', 'opname_number', 'opname_date', 'status', 'created_at'];
        if (! in_array($sortField, $allowlistSorts, true)) {
            $sortField = 'id';
        }
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $sortDirection)->paginate($perPage);
    }

    public function findById(int $id): ?StockOpname
    {
        return StockOpname::with([
            'location',
            'creator',
            'updater',
            'starter',
            'completer',
            'poster',
            'canceler',
            'items.product.unit',
            'reopenLogs.reopener',
        ])->find($id);
    }

    public function generateOpnameNumber(): string
    {
        $prefix = 'SOP-'.now()->format('Ym').'-';

        $lastOpname = DB::table('stock_opnames')
            ->where('opname_number', 'like', "{$prefix}%")
            ->orderBy('opname_number', 'desc')
            ->lockForUpdate()
            ->first();

        if (! $lastOpname) {
            $number = 1;
        } else {
            $lastSeq = (int) substr($lastOpname->opname_number, -4);
            $number = $lastSeq + 1;
        }

        if ($number > 9999) {
            throw new \RuntimeException('Batas pembuatan nomor stock opname bulan ini telah tercapai.');
        }

        return $prefix.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
