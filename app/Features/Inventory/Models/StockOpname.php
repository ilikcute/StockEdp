<?php

namespace App\Features\Inventory\Models;

use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\OpnameStatus;
use App\Features\Location\Models\Location;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    use HasFactory;

    protected $table = 'stock_opnames';

    protected $fillable = [
        'opname_number',
        'location_id',
        'opname_date',
        'status',
        'notes',
        'cancel_reason',
        'started_by',
        'started_at',
        'completed_by',
        'completed_at',
        'posted_by',
        'posted_at',
        'canceled_by',
        'canceled_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => OpnameStatus::class,
        'opname_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'posted_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class, 'stock_opname_id');
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function canceler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function reopenLogs(): HasMany
    {
        return $this->hasMany(StockOpnameReopenLog::class, 'stock_opname_id');
    }

    public function isDraft(): bool
    {
        return $this->status === OpnameStatus::DRAFT;
    }

    public function isInProgress(): bool
    {
        return $this->status === OpnameStatus::IN_PROGRESS;
    }

    public function isCounted(): bool
    {
        return $this->status === OpnameStatus::COUNTED;
    }

    public function isPosted(): bool
    {
        return $this->status === OpnameStatus::POSTED;
    }

    public function isCanceled(): bool
    {
        return $this->status === OpnameStatus::CANCELED;
    }
}
