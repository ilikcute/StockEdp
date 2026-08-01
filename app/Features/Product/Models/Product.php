<?php

namespace App\Features\Product\Models;

use App\Features\Auth\Models\User;
use App\Features\Category\Models\Category;
use App\Features\Unit\Models\Unit;
use Database\Factories\Features\Product\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'description',
        'category_id',
        'unit_id',
        'minimum_stock',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'minimum_stock' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
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
