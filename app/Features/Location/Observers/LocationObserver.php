<?php

namespace App\Features\Location\Observers;

use App\Features\Location\Models\Location;
use Illuminate\Support\Facades\DB;

class LocationObserver
{
    /**
     * Handle the Location "created" event.
     */
    public function created(Location $location): void
    {
        DB::table('inventory_location_locks')->insertOrIgnore([
            'location_id' => $location->id,
            'is_frozen' => false,
            'frozen_by_opname_id' => null,
            'frozen_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
