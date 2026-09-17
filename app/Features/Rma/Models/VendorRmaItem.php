<?php

namespace App\Features\Rma\Models;

use App\Features\Product\Models\Product;
use App\Features\ProductSerial\Models\ProductSerial;
use App\Features\Rma\Enums\RmaItemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorRmaItem extends Model
{
    use HasFactory;

    protected $table = 'vendor_rma_items';

    protected $fillable = [
        'vendor_rma_id',
        'product_id',
        'product_serial_id',
        'serial_number',
        'quantity',
        'fault_description',
        'status',
    ];

    protected $casts = [
        'status' => RmaItemStatus::class,
        'quantity' => 'integer',
    ];

    public function vendorRma(): BelongsTo
    {
        return $this->belongsTo(VendorRma::class, 'vendor_rma_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productSerial(): BelongsTo
    {
        return $this->belongsTo(ProductSerial::class, 'product_serial_id');
    }
}
