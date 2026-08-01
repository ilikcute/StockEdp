<?php

namespace App\Shared\Traits;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRolesAndPermissions
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasRole(RoleCode|string $role): bool
    {
        $this->loadMissing('roles');
        $code = $role instanceof RoleCode ? $role->value : $role;

        return $this->roles->contains(fn (Role $r) => $r->code->value === $code);
    }

    public function hasPermissionTo(PermissionCode|string $permission): bool
    {
        $this->loadMissing('roles.permissions');

        if ($this->hasRole(RoleCode::ADMIN)) {
            return true;
        }

        $code = $permission instanceof PermissionCode ? $permission->value : $permission;

        return $this->roles->flatMap(fn (Role $role) => $role->permissions)
            ->contains(fn (Permission $p) => $p->code->value === $code);
    }

    public function assignRole(RoleCode|string ...$roles): void
    {
        $codes = array_map(fn ($r) => $r instanceof RoleCode ? $r->value : $r, $roles);
        $roleIds = Role::whereIn('code', $codes)->pluck('id');

        $this->roles()->syncWithoutDetaching($roleIds);
        $this->load('roles');
    }
}
