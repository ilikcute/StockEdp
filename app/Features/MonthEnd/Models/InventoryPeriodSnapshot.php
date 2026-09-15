<?php

namespace App\Features\MonthEnd\Models;

use App\Features\Inventory\Enums\StockCondition;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class InventoryPeriodSnapshot extends Model
{
    use HasFactory;

    protected $table = 'inventory_period_snapshots';

    protected $fillable = [
        'inventory_period_id',
        'product_id',
        'location_id',
        'condition',
        'opening_balance',
        'total_in',
        'total_out',
        'total_adjustment',
        'closing_balance',
        'unit_price',
        'total_value',
    ];

    protected $casts = [
        'condition' => StockCondition::class,
        'opening_balance' => 'decimal:4',
        'total_in' => 'decimal:4',
        'total_out' => 'decimal:4',
        'total_adjustment' => 'decimal:4',
        'closing_balance' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(InventoryPeriod::class, 'inventory_period_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
