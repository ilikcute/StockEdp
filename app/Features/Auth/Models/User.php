<?php

namespace App\Features\Auth\Models;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Location\Models\Location;
use App\Shared\Traits\HasRolesAndPermissions;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'is_active', 'last_login_at', 'last_login_ip'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRolesAndPermissions, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'user_locations');
    }

    public function hasGlobalLocationAccess(): bool
    {
        return $this->hasRole(RoleCode::ADMIN);
    }

    public function getAllowedLocationIds(): array
    {
        if ($this->hasGlobalLocationAccess()) {
            return Location::query()->where('is_active', true)->pluck('id')->toArray();
        }

        return $this->locations()
            ->where('locations.is_active', true)
            ->pluck('locations.id')
            ->toArray();
    }
}
