<?php

namespace App\Features\Audit\Services;

use App\Features\Audit\Models\ActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AuditLogService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = ActivityLog::query()->with('user:id,name,username,email');

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['module'])) {
            $query->where('module', (string) $filters['module']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', (string) $filters['action']);
        }

        if (isset($filters['user_id']) && $filters['user_id'] !== '' && $filters['user_id'] !== null) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', (string) $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', (string) $filters['date_to']);
        }

        $perPage = (int) ($filters['per_page'] ?? 25);
        $perPage = max(1, min($perPage, 100));

        return $query->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function getModules(): array
    {
        return ActivityLog::query()
            ->select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->map(fn (string $module) => [
                'value' => $module,
                'label' => $this->moduleLabel($module),
            ])
            ->values()
            ->all();
    }

    public function moduleLabel(string $module): string
    {
        return match ($module) {
            'auth' => 'Autentikasi',
            'month_end' => 'Tutup Buku Bulanan',
            'store_allocations' => 'Alokasi Toko',
            'users' => 'Pengelolaan Pengguna',
            'products' => 'Produk',
            'categories' => 'Kategori',
            'units' => 'Satuan',
            'suppliers' => 'Supplier',
            'locations' => 'Lokasi',
            'stores' => 'Toko',
            'departments' => 'Departemen',
            default => ucfirst(str_replace('_', ' ', $module)),
        };
    }
}