<?php

namespace App\Features\Rma\Models;

use App\Features\Auth\Models\User;
use App\Features\Location\Models\Location;
use App\Features\Rma\Enums\RmaStatus;
use App\Features\Supplier\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorRma extends Model
{
    use HasFactory;

    protected $table = 'vendor_rmas';

    protected $fillable = [
        'rma_number',
        'supplier_id',
        'origin_location_id',
        'status',
        'dispatch_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'status' => RmaStatus::class,
        'dispatch_date' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function originLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'origin_location_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VendorRmaItem::class, 'vendor_rma_id');
    }

    public function isDraft(): bool
    {
        return $this->status === RmaStatus::DRAFT;
    }

    public function isDispatched(): bool
    {
        return $this->status === RmaStatus::DISPATCHED;
    }
}
