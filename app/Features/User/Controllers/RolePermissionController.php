<?php

namespace App\Features\User\Controllers;

use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use App\Features\User\Requests\UpdateRolePermissionsRequest;
use App\Features\User\Resources\PermissionResource;
use App\Features\User\Resources\RoleResource;
use App\Shared\Exceptions\DomainException;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class RolePermissionController extends Controller
{
    public function roles(): JsonResponse
    {
        $roles = Role::with(['permissions'])->withCount('users')->get();

        return ApiResponse::success(
            data: RoleResource::collection($roles),
            message: 'Daftar peran & hak akses berhasil diambil.'
        );
    }

    public function permissions(): JsonResponse
    {
        $permissions = Permission::all()->groupBy('group');

        $groupedData = [];
        foreach ($permissions as $group => $items) {
            $groupedData[$group] = PermissionResource::collection($items);
        }

        return ApiResponse::success(
            data: $groupedData,
            message: 'Daftar seluruh hak akses sistem berhasil diambil.'
        );
    }

    public function updatePermissions(UpdateRolePermissionsRequest $request, Role $role): JsonResponse
    {
        if ($role->code === RoleCode::ADMIN) {
            throw new DomainException('Hak akses peran Administrator bersifat penuh secara permanen dan tidak dapat dikurangi.', 422);
        }

        $permissionIds = $request->validated('permission_ids');
        $role->permissions()->sync($permissionIds);

        $role->load(['permissions'])->loadCount('users');

        return ApiResponse::success(
            data: new RoleResource($role),
            message: "Hak akses peran \"{$role->name}\" berhasil diperbarui."
        );
    }
}
