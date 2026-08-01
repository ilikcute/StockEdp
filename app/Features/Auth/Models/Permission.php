<?php

namespace App\Features\Auth\Models;

use App\Features\Auth\Enums\PermissionCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'code',
        'name',
        'group',
    ];

    protected function casts(): array
    {
        return [
            'code' => PermissionCode::class,
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }
}
