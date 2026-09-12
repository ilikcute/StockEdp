<?php

namespace App\Features\Inventory\Services;

use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class InventoryFreezeService
{
    /**
     * Ensure lock rows exist for given locations in a concurrency-safe manner.
     *
     * @param  array<int>  $locationIds
     */
    public function ensureLockRowsExist(array $locationIds): void
    {
        if (empty($locationIds)) {
            return;
        }

        $uniqueIds = array_values(array_unique(array_map('intval', $locationIds)));
        sort($uniqueIds);

        $now = now();
        $records = [];
        foreach ($uniqueIds as $locId) {
            $records[] = [
                'location_id' => $locId,
                'is_frozen' => false,
                'frozen_by_opname_id' => null,
                'frozen_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('inventory_location_locks')->insertOrIgnore($records);
    }

    /**
     * Lock location operation rows in strict ASCENDING order and validate freeze state.
     *
     * @param  array<int>  $locationIds
     * @param  int|null  $allowedOpnameId  Opname ID if the caller is the freeze owner
     *
     * @throws DomainException
     */
    public function lockAndValidateLocations(array $locationIds, ?int $allowedOpnameId = null): void
    {
        if (empty($locationIds)) {
            return;
        }

        $uniqueIds = array_values(array_unique(array_map('intval', $locationIds)));
        sort($uniqueIds);

        $this->ensureLockRowsExist($uniqueIds);

        $executeLock = function () use ($uniqueIds, $allowedOpnameId) {
            $locks = DB::table('inventory_location_locks')
                ->whereIn('location_id', $uniqueIds)
                ->orderBy('location_id', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($locks as $lock) {
                if ($lock->is_frozen) {
                    if ($allowedOpnameId === null || (int) $lock->frozen_by_opname_id !== $allowedOpnameId) {
                        throw new DomainException('Location is frozen for stock opname.', 409, [
                            'code' => 'LOCATION_FROZEN',
                            'location_id' => $lock->location_id,
                        ]);
                    }
                }
            }
        };

        if (DB::transactionLevel() > 0) {
            $executeLock();
        } else {
            DB::transaction($executeLock);
        }
    }

    /**
     * Activate freeze on a location for a specific stock opname owner.
     *
     * @throws DomainException
     */
    public function freezeLocation(int $locationId, int $opnameId): void
    {
        $this->ensureLockRowsExist([$locationId]);

        $lock = DB::table('inventory_location_locks')
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->first();

        if ($lock && (bool) $lock->is_frozen && (int) $lock->frozen_by_opname_id !== $opnameId) {
            throw new DomainException(
                'Lokasi persediaan sudah dibekukan oleh sesi Stock Opname lain.',
                409,
                ['code' => 'LOCATION_FROZEN']
            );
        }

        DB::table('inventory_location_locks')
            ->where('location_id', $locationId)
            ->update([
                'is_frozen' => true,
                'frozen_by_opname_id' => $opnameId,
                'frozen_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Release freeze on a location only if owned by the specified stock opname ID.
     *
     * @throws DomainException
     */
    public function unfreezeLocation(int $locationId, int $opnameId): void
    {
        $this->ensureLockRowsExist([$locationId]);

        $lock = DB::table('inventory_location_locks')
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->first();

        if (! $lock || ! (bool) $lock->is_frozen) {
            return;
        }

        if ((int) $lock->frozen_by_opname_id !== $opnameId) {
            throw new DomainException(
                'Tidak dapat melepaskan pembekuan lokasi milik sesi Stock Opname lain.',
                409,
                ['code' => 'LOCATION_FROZEN_OWNER_MISMATCH']
            );
        }

        DB::table('inventory_location_locks')
            ->where('location_id', $locationId)
            ->update([
                'is_frozen' => false,
                'frozen_by_opname_id' => null,
                'frozen_at' => null,
                'updated_at' => now(),
            ]);
    }
}
