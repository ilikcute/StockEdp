<?php

namespace App\Features\StoreAllocation\Models;

use App\Features\Product\Models\Product;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'store_allocation_id',
    'product_id',
    'quantity',
    'serial_number',
    'pulled_product_id',
    'pulled_quantity',
    'pulled_serial_number',
    'defective_reason',
])]
class StoreAllocationItem extends Model
{
    use HasFactory;

    protected $table = 'store_allocation_items';

    protected $casts = [
        'quantity' => 'string',
        'pulled_quantity' => 'string',
    ];

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(StoreAllocation::class, 'store_allocation_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function pulledProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'pulled_product_id');
    }
}
