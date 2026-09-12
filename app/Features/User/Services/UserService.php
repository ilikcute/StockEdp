<?php

namespace App\Features\User\Services;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Role;
use App\Features\Auth\Models\User;
use App\Features\Location\Models\Location;
use App\Shared\Exceptions\DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function listUsers(array $filters): LengthAwarePaginator
    {
        $query = User::query()->with(['roles', 'locations']);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['role_id'])) {
            $query->whereHas('roles', function (Builder $q) use ($filters) {
                $q->where('roles.id', (int) $filters['role_id']);
            });
        }

        if (! empty($filters['location_id'])) {
            $query->whereHas('locations', function (Builder $q) use ($filters) {
                $q->where('locations.id', (int) $filters['location_id']);
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $isActive = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['name', 'username', 'email', 'created_at', 'is_active'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min($perPage, 100));

        return $query->paginate($perPage);
    }

    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => $data['is_active'] ?? true,
            ]);

            if (! empty($data['role_ids'])) {
                $user->roles()->sync($data['role_ids']);
            }

            if (isset($data['location_ids'])) {
                $user->locations()->sync($data['location_ids']);
            }

            return $user->load(['roles', 'locations']);
        });
    }

    public function updateUser(User $user, array $data, User $currentUser): User
    {
        return DB::transaction(function () use ($user, $data, $currentUser) {
            $willBeInactive = isset($data['is_active']) && ! (bool) $data['is_active'];

            // Guard 1: Prevent self-deactivation
            if ($willBeInactive && $user->id === $currentUser->id) {
                throw new DomainException('Anda tidak dapat menonaktifkan akun sendiri yang sedang digunakan.', 422);
            }

            // Guard 2: Prevent removing Admin role or deactivating the last active Admin
            if ($user->hasRole(RoleCode::ADMIN)) {
                $adminRoleId = Role::where('code', RoleCode::ADMIN->value)->value('id');
                $isRemovingAdminRole = isset($data['role_ids']) && ! in_array((int) $adminRoleId, array_map('intval', (array) $data['role_ids']), true);

                if ($willBeInactive || $isRemovingAdminRole) {
                    $this->ensureAtLeastOneOtherActiveAdminExists($user->id);
                }
            }

            $updateData = [
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
            ];

            if (isset($data['is_active'])) {
                $updateData['is_active'] = (bool) $data['is_active'];
            }

            if (! empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            $user->update($updateData);

            if (isset($data['role_ids'])) {
                $user->roles()->sync($data['role_ids']);
            }

            if (isset($data['location_ids'])) {
                $user->locations()->sync($data['location_ids']);
            }

            return $user->load(['roles', 'locations']);
        });
    }

    public function updateUserStatus(User $user, bool $isActive, User $currentUser): User
    {
        if (! $isActive && $user->id === $currentUser->id) {
            throw new DomainException('Anda tidak dapat menonaktifkan akun sendiri yang sedang digunakan.', 422);
        }

        if (! $isActive && $user->hasRole(RoleCode::ADMIN)) {
            $this->ensureAtLeastOneOtherActiveAdminExists($user->id);
        }

        $user->update(['is_active' => $isActive]);

        return $user->load(['roles', 'locations']);
    }

    public function getFormOptions(): array
    {
        $roles = Role::select('id', 'code', 'name', 'description')->get()->map(fn (Role $r) => [
            'id' => $r->id,
            'code' => $r->code->value,
            'name' => $r->name,
            'description' => $r->description,
        ]);

        $locations = Location::where('is_active', true)
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        return [
            'roles' => $roles,
            'locations' => $locations,
        ];
    }

    private function ensureAtLeastOneOtherActiveAdminExists(int $excludedUserId): void
    {
        $otherActiveAdminCount = User::where('id', '!=', $excludedUserId)
            ->where('is_active', true)
            ->whereHas('roles', function (Builder $q) {
                $q->where('code', RoleCode::ADMIN->value);
            })
            ->count();

        if ($otherActiveAdminCount === 0) {
            throw new DomainException('Tidak dapat menonaktifkan atau mencabut hak akses dari Administrator aktif terakhir di sistem.', 422);
        }
    }
}
