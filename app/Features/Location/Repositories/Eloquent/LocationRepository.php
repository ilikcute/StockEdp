<?php

namespace App\Features\Location\Repositories\Eloquent;

use App\Features\Location\Models\Location;
use App\Features\Location\Repositories\Contracts\LocationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LocationRepository implements LocationRepositoryInterface
{
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Location::with(['createdBy', 'updatedBy']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'code', 'name', 'is_active', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    public function getAllActive(): Collection
    {
        return Location::query()
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();
    }

    public function findById(int $id): ?Location
    {
        return Location::with(['createdBy', 'updatedBy'])->find($id);
    }

    public function create(array $data): Location
    {
        $location = Location::create($data);

        DB::table('inventory_location_locks')->insertOrIgnore([
            'location_id' => $location->id,
            'is_frozen' => false,
            'frozen_by_opname_id' => null,
            'frozen_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $location;
    }

    public function update(Location $location, array $data): Location
    {
        $location->update($data);

        return $location->fresh(['createdBy', 'updatedBy']);
    }
}
