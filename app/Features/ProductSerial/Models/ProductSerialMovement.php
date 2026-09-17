<?php

namespace App\Features\ProductSerial\Models;

use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\StockCondition;
use App\Features\Location\Models\Location;
use App\Features\ProductSerial\Enums\SerialMovementType;
use App\Features\ProductSerial\Enums\SerialStatus;
use App\Features\Store\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSerialMovement extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'product_serial_movements';

    protected $fillable = [
        'product_serial_id',
        'movement_type',
        'from_location_id',
        'to_location_id',
        'from_store_id',
        'to_store_id',
        'from_condition',
        'to_condition',
        'from_status',
        'to_status',
        'reference_type',
        'reference_id',
        'reference_number',
        'user_id',
        'notes',
        'occurred_at',
        'created_at',
    ];

    protected $casts = [
        'movement_type' => SerialMovementType::class,
        'from_condition' => StockCondition::class,
        'to_condition' => StockCondition::class,
        'from_status' => SerialStatus::class,
        'to_status' => SerialStatus::class,
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function productSerial(): BelongsTo
    {
        return $this->belongsTo(ProductSerial::class, 'product_serial_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function fromStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'from_store_id');
    }

    public function toStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
