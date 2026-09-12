<?php

namespace App\Features\Inventory\Models;

use App\Features\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameCountLog extends Model
{
    public $timestamps = false;

    protected $table = 'stock_opname_count_logs';

    protected $fillable = [
        'stock_opname_item_id',
        'user_id',
        'previous_quantity',
        'new_quantity',
        'count_version',
        'created_at',
    ];

    protected $casts = [
        'previous_quantity' => 'string',
        'new_quantity' => 'string',
        'count_version' => 'integer',
        'created_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(StockOpnameItem::class, 'stock_opname_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
