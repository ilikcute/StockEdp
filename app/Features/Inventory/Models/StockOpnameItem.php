<?php

namespace App\Features\Inventory\Models;

use App\Features\Auth\Models\User;
use App\Features\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpnameItem extends Model
{
    use HasFactory;

    protected $table = 'stock_opname_items';

    protected $fillable = [
        'stock_opname_id',
        'product_id',
        'snapshot_quantity',
        'counted_quantity',
        'variance_quantity',
        'count_version',
        'counted_by',
        'counted_at',
        'item_notes',
        'is_unexpected',
    ];

    protected $casts = [
        'snapshot_quantity' => 'string',
        'counted_quantity' => 'string',
        'variance_quantity' => 'string',
        'count_version' => 'integer',
        'is_unexpected' => 'boolean',
        'counted_at' => 'datetime',
    ];

    public function opname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function countLogs(): HasMany
    {
        return $this->hasMany(StockOpnameCountLog::class, 'stock_opname_item_id');
    }
}
