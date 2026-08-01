<?php

namespace App\Features\Inventory\Models;

use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\TransferStatus;
use App\Features\Location\Models\Location;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = [
        'transfer_number',
        'origin_location_id',
        'destination_location_id',
        'status',
        'transfer_date',
        'notes',
        'created_by',
        'updated_by',
        'sent_by',
        'received_by',
        'canceled_by',
        'sent_at',
        'received_at',
        'canceled_at',
    ];

    protected $casts = [
        'status' => TransferStatus::class,
        'transfer_date' => 'date',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function originLocation()
    {
        return $this->belongsTo(Location::class, 'origin_location_id');
    }

    public function destinationLocation()
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === TransferStatus::DRAFT;
    }

    public function isSent(): bool
    {
        return $this->status === TransferStatus::SENT;
    }
}
