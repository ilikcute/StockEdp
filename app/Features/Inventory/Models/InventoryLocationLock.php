<?php

namespace App\Features\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLocationLock extends Model
{
    protected $table = 'inventory_location_locks';

    protected $primaryKey = 'location_id';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'is_frozen' => 'boolean',
        'frozen_at' => 'datetime',
    ];
}
