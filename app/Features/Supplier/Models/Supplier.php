<?php

namespace App\Features\Supplier\Models;

use App\Features\Auth\Models\User;
use Database\Factories\Features\Supplier\SupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'code',
    'name',
    'contact_person',
    'phone',
    'email',
    'address',
    'tax_number',
    'is_active',
    'created_by',
    'updated_by',
])]
class Supplier extends Model
{
    use HasFactory;

    protected $table = 'suppliers';

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): SupplierFactory
    {
        return SupplierFactory::new();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
