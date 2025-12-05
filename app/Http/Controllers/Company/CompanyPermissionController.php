<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Company\UpdateRolePermissionsRequest;
use App\Http\Requests\Company\UpdateUserPermissionsRequest;
use App\Models\Company;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class CompanyPermissionController extends BaseApiController
{
    /**
     * List all available permissions in the system.
     *
     * Restricted to COMPANY_ADMIN of the current company.
     *
     * @throws AuthorizationException
     */
    public function availablePermissions(): JsonResponse
    {
        $company = $this->resolveCompanyOrAbort('manageRolePermissions');

        // $company no se usa directamente, pero la policy ya validó contexto tenant.
        $permissions = Permission::query()
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return response()->json([
            'data' => $permissions,
        ]);
    }

    /**
     * List role permissions for the current company grouped by role.
     *
     * @throws AuthorizationException
     */
    public function listRolePermissions(): JsonResponse
    {
        $company = $this->resolveCompanyOrAbort('manageRolePermissions');

        $rolePermissions = RolePermission::query()
            ->where('company_id', $company->id)
            ->with('permission:id,name')
            ->get()
            ->groupBy('role')
            ->map(function (Collection $items) {
                return $items
                    ->pluck('permission.name')
                    ->sort()
                    ->values()
                    ->all();
            });

        return response()->json([
            'data' => $rolePermissions,
        ]);
    }

    /**
     * Update role permissions for a specific role in the current company.
     *
     * This replaces the entire permission set for that role in this company.
     *
     * @throws AuthorizationException
     */
    public function updateRolePermissions(string $role, UpdateRolePermissionsRequest $request): JsonResponse
    {
        $company = $this->resolveCompanyOrAbort('manageRolePermissions');

        // No tiene sentido permitir modificar SUPERADMIN aquí.
        $validRoles = [
            'COMPANY_ADMIN',
            'COMPANY_USER',
            'PARTNER_ADMIN',
            'DRIVER',
        ];

        if (!in_array($role, $validRoles, true)) {
            abort(422, 'Role not allowed for company-level permission management.');
        }

        $permissionNames = $request->validated()['permissions'] ?? [];

        $permissions = $this->resolvePermissionsOrFail($permissionNames);

        // Reemplazar set completo para ese rol en esta compañía
        RolePermission::query()
            ->where('company_id', $company->id)
            ->where('role', $role)
            ->delete();

        foreach ($permissions as $name => $id) {
            RolePermission::query()->create([
                'company_id' => $company->id,
                'role' => $role,
                'permission_id' => $id,
            ]);
        }

        return response()->json([
            'message' => 'Role permissions updated.',
        ]);
    }

    /**
     * Show role-based and user-specific permissions for a given user.
     *
     * @throws AuthorizationException
     */
    public function showUserPermissions(int $userId): JsonResponse
    {
        $company = $this->resolveCompanyOrAbort('manageUserPermissions');

        /** @var User $targetUser */
        $targetUser = User::query()->findOrFail($userId);

        // El usuario objetivo debe pertenecer a la misma compañía
        if ($targetUser->company_id !== $company->id) {
            abort(403);
        }

        // Role-based permissions
        $rolePermissionNames = RolePermission::query()
            ->where('company_id', $company->id)
            ->where('role', $targetUser->role->value)
            ->with('permission:id,name')
            ->get()
            ->pluck('permission.name')
            ->sort()
            ->values()
            ->all();

        // User-specific overrides
        $userPermissionNames = $targetUser->userPermissions()
            ->where('company_id', $company->id)
            ->with('permission:id,name')
            ->get()
            ->pluck('permission.name')
            ->sort()
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'user_id' => $targetUser->id,
                'role' => $targetUser->role->value,
                'role_permissions' => $rolePermissionNames,
                'user_permissions' => $userPermissionNames,
            ],
        ]);
    }

    /**
     * Replace user-specific permissions for a given user within the company.
     *
     * These are additive overrides on top of role permissions.
     *
     * @throws AuthorizationException
     */
    public function updateUserPermissions(int $userId, UpdateUserPermissionsRequest $request): JsonResponse
    {
        $company = $this->resolveCompanyOrAbort('manageUserPermissions');

        /** @var User $targetUser */
        $targetUser = User::query()->findOrFail($userId);

        if ($targetUser->company_id !== $company->id) {
            abort(403);
        }

        // Por diseño, no queremos que se cambien permisos de SUPERADMIN aquí
        if ($targetUser->isSuperAdmin()) {
            abort(403, 'Cannot manage permissions for SUPERADMIN users.');
        }

        $permissionNames = $request->validated()['permissions'] ?? [];

        $permissions = $this->resolvePermissionsOrFail($permissionNames);

        // Reemplazar overrides de este usuario en esta compañía
        $targetUser->userPermissions()
            ->where('company_id', $company->id)
            ->delete();

        foreach ($permissions as $name => $id) {
            $targetUser->userPermissions()->create([
                'company_id' => $company->id,
                'permission_id' => $id,
            ]);
        }

        return response()->json([
            'message' => 'User permissions updated.',
        ]);
    }

    /**
     * Resolve current company (tenant) and authorize given ability.
     *
     * @throws AuthorizationException
     */
    protected function resolveCompanyOrAbort(string $ability): Company
    {
        $company = $this->tenant();

        if (!$company instanceof Company) {
            abort(403);
        }

        $this->authorize($ability, $company);

        return $company;
    }

    /**
     * Map permission names to IDs or return 422 if some are unknown.
     *
     * @param array<string> $permissionNames
     */
    protected function resolvePermissionsOrFail(array $permissionNames): Collection
    {
        if ($permissionNames === []) {
            return collect();
        }

        $permissions = Permission::query()
            ->whereIn('name', $permissionNames)
            ->pluck('id', 'name');

        $unknownNames = array_diff($permissionNames, $permissions->keys()->all());

        if (!empty($unknownNames)) {
            return response()->json([
                'message' => 'Unknown permissions: ' . implode(', ', $unknownNames),
            ], 422)->throwResponse();
        }

        return $permissions;
    }
}
