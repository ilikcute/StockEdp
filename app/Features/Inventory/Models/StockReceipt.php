<?php

namespace App\Features\Inventory\Models;

use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\ReceiptStatus;
use App\Features\Supplier\Models\Supplier;
use Illuminate\Database\Eloquent\Model;

class StockReceipt extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status' => ReceiptStatus::class,
        'date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(StockReceiptItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === ReceiptStatus::DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === ReceiptStatus::POSTED;
    }
}
