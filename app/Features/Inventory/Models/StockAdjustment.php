<?php

namespace App\Features\Inventory\Models;

use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\AdjustmentReason;
use App\Features\Inventory\Enums\AdjustmentStatus;
use App\Features\Location\Models\Location;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'adjustment_number',
        'location_id',
        'adjustment_date',
        'direction',
        'reason_code',
        'notes',
        'status',
        'created_by',
        'updated_by',
        'posted_by',
        'canceled_by',
        'posted_at',
        'canceled_at',
    ];

    protected $casts = [
        'status' => AdjustmentStatus::class,
        'reason_code' => AdjustmentReason::class,
        'adjustment_date' => 'date',
        'posted_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function canceler()
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }

    public function isDraft(): bool
    {
        return $this->status === AdjustmentStatus::DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === AdjustmentStatus::POSTED;
    }

    public function isCanceled(): bool
    {
        return $this->status === AdjustmentStatus::CANCELED;
    }
}
