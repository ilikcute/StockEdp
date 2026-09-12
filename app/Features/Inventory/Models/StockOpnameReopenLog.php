<?php

namespace App\Features\Inventory\Models;

use App\Features\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameReopenLog extends Model
{
    public $timestamps = false;

    protected $table = 'stock_opname_reopen_logs';

    protected $fillable = [
        'stock_opname_id',
        'reopened_by',
        'reason',
        'reopened_at',
    ];

    protected $casts = [
        'reopened_at' => 'datetime',
    ];

    public function opname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function reopener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }
}
