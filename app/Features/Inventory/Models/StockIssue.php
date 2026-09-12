<?php

namespace App\Features\Inventory\Models;

use App\Features\Auth\Models\User;
use App\Features\Inventory\Enums\IssueStatus;
use Illuminate\Database\Eloquent\Model;

class StockIssue extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status' => IssueStatus::class,
        'date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(StockIssueItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === IssueStatus::DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === IssueStatus::POSTED;
    }
}
