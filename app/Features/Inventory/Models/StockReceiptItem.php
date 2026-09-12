<?php

namespace App\Features\Inventory\Models;

use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use Illuminate\Database\Eloquent\Model;

class StockReceiptItem extends Model
{
    protected $guarded = ['id'];

    public function receipt()
    {
        return $this->belongsTo(StockReceipt::class, 'stock_receipt_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
