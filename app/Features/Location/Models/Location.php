<?php

namespace App\Features\Location\Models;

use App\Features\Auth\Models\User;
use App\Features\Inventory\Models\InventoryLocationLock;
use App\Features\Location\Observers\LocationObserver;
use Database\Factories\Features\Location\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy([LocationObserver::class])]
#[Fillable(['code', 'name', 'description', 'address', 'phone', 'is_active', 'created_by', 'updated_by'])]
class Location extends Model
{
    use HasFactory;

    protected $table = 'locations';

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): LocationFactory
    {
        return LocationFactory::new();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_locations');
    }

    public function operationLock()
    {
        return $this->hasOne(InventoryLocationLock::class, 'location_id');
    }
}
