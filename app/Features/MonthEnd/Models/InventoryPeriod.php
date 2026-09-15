<?php

namespace App\Features\MonthEnd\Models;

use App\Features\Auth\Models\User;
use App\Features\MonthEnd\Enums\PeriodStatus;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $period_key
 * @property int $year
 * @property int $month
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property \App\Features\MonthEnd\Enums\PeriodStatus $status
 * @property \Carbon\Carbon|null $closed_at
 * @property int|null $closed_by
 * @property \Carbon\Carbon|null $reopened_at
 * @property int|null $reopened_by
 * @property string|null $reopen_reason
 * @property string|null $notes
 * @property string $month_name
 * 
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class InventoryPeriod extends Model
{
    use HasFactory;

    protected $table = 'inventory_periods';

    protected $fillable = [
        'period_key',
        'year',
        'month',
        'start_date',
        'end_date',
        'status',
        'closed_at',
        'closed_by',
        'reopened_at',
        'reopened_by',
        'reopen_reason',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => PeriodStatus::class,
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    public function snapshots(): HasMany
    {
        return $this->hasMany(InventoryPeriodSnapshot::class, 'inventory_period_id');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopenedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', PeriodStatus::OPEN->value);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', PeriodStatus::CLOSED->value);
    }

    public function scopeForDate(Builder $query, string|DateTimeInterface $date): Builder
    {
        $dateStr = $date instanceof DateTimeInterface ? $date->format('Y-m-d') : Carbon::parse($date)->toDateString();

        return $query->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr);
    }

    public function isOpen(): bool
    {
        return $this->status === PeriodStatus::OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === PeriodStatus::CLOSED;
    }

    public function getMonthNameAttribute(): string
    {
        $carbon = Carbon::createFromDate($this->year, $this->month, 1)->locale('id');

        return $carbon->translatedFormat('F Y');
    }
}
