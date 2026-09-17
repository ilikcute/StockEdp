<?php

namespace App\Features\ProductSerial\Models;

use App\Features\Inventory\Enums\StockCondition;
use App\Features\Location\Models\Location;
use App\Features\Product\Models\Product;
use App\Features\ProductSerial\Enums\SerialStatus;
use App\Features\Store\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSerial extends Model
{
    use HasFactory;

    protected $table = 'product_serials';

    protected $fillable = [
        'serial_number',
        'product_id',
        'current_location_id',
        'current_store_id',
        'current_condition',
        'status',
        'notes',
    ];

    protected $casts = [
        'current_condition' => StockCondition::class,
        'status' => SerialStatus::class,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    public function currentStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'current_store_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ProductSerialMovement::class, 'product_serial_id')->orderByDesc('occurred_at')->orderByDesc('id');
    }

    public function isInstalled(): bool
    {
        return $this->status === SerialStatus::INSTALLED;
    }

    public function isInStock(): bool
    {
        return $this->status === SerialStatus::IN_STOCK;
    }

    public function isDefective(): bool
    {
        return $this->status === SerialStatus::DEFECTIVE || $this->current_condition === StockCondition::DEFECTIVE;
    }

    public function isAtStore(?int $storeId = null): bool
    {
        if (! $this->isInstalled()) {
            return false;
        }

        return $storeId ? (int) $this->current_store_id === (int) $storeId : ! empty($this->current_store_id);
    }
}
