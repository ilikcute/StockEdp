<?php

namespace App\Features\StoreAllocation\Models;

use App\Features\Auth\Models\User;
use App\Features\Location\Models\Location;
use App\Features\Store\Models\Store;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'allocation_number',
    'technician_user_id',
    'technician_location_id',
    'store_id',
    'allocated_at',
    'notes',
    'created_by',
    'updated_by',
])]
class StoreAllocation extends Model
{
    use HasFactory;

    protected $table = 'store_allocations';

    protected $casts = [
        'allocated_at' => 'date',
    ];

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_user_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'technician_location_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StoreAllocationItem::class, 'store_allocation_id');
    }
}
