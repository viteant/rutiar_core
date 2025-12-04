<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

class CompanyPermissionController extends Controller
{
    use AuthorizesRequests;
    use ValidatesRequests;

    /**
     * List all available permissions in the system.
     *
     * Restricted to COMPANY_ADMIN of the current company.
     * @throws AuthorizationException
     */
    public function availablePermissions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Company|null $company */
        $company = $user->company;

        if (!$company) {
            abort(403);
        }

        $this->authorize('manageRolePermissions', $company);

        $permissions = Permission::query()
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return response()->json([
            'data' => $permissions,
        ]);
    }

    /**
     * List role permissions for the current company grouped by role.
     * @throws AuthorizationException
     */
    public function listRolePermissions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Company|null $company */
        $company = $user->company;

        if (!$company) {
            abort(403);
        }

        $this->authorize('manageRolePermissions', $company);

        $rolePermissions = RolePermission::query()
            ->where('company_id', $company->id)
            ->with('permission:id,name')
            ->get()
            ->groupBy('role')
            ->map(function ($items) {
                /** @var \Illuminate\Support\Collection $items */
                return $items->pluck('permission.name')->sort()->values()->all();
            });

        return response()->json([
            'data' => $rolePermissions,
        ]);
    }

    /**
     * Update role permissions for a specific role in the current company.
     *
     * This replaces the entire permission set for that role in this company.
     * @throws AuthorizationException
     */
    public function updateRolePermissions(string $role, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Company|null $company */
        $company = $user->company;

        if (!$company) {
            abort(403);
        }

        $this->authorize('manageRolePermissions', $company);

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

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'distinct'],
        ]);

        /** @var array<string> $permissionNames */
        $permissionNames = $validated['permissions'] ?? [];

        $permissions = Permission::query()
            ->whereIn('name', $permissionNames)
            ->pluck('id', 'name');

        // Validar que todos los nombres enviados existan
        $unknownNames = array_diff($permissionNames, $permissions->keys()->all());

        if (!empty($unknownNames)) {
            return response()->json([
                'message' => 'Unknown permissions: ' . implode(', ', $unknownNames),
            ], 422);
        }

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
     * @throws AuthorizationException
     */
    public function showUserPermissions(int $userId, Request $request): JsonResponse
    {
        /** @var User $authUser */
        $authUser = $request->user();

        /** @var Company|null $company */
        $company = $authUser->company;

        if (!$company) {
            abort(403);
        }

        $this->authorize('manageUserPermissions', $company);

        /** @var User|null $targetUser */
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
     * @throws AuthorizationException
     */
    public function updateUserPermissions(int $userId, Request $request): JsonResponse
    {
        /** @var User $authUser */
        $authUser = $request->user();

        /** @var Company|null $company */
        $company = $authUser->company;

        if (!$company) {
            abort(403);
        }

        $this->authorize('manageUserPermissions', $company);

        /** @var User $targetUser */
        $targetUser = User::query()->findOrFail($userId);

        if ($targetUser->company_id !== $company->id) {
            abort(403);
        }

        // Por diseño, no queremos que se cambien permisos de SUPERADMIN aquí
        if ($targetUser->isSuperAdmin()) {
            abort(403, 'Cannot manage permissions for SUPERADMIN users.');
        }

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'distinct'],
        ]);

        /** @var array<string> $permissionNames */
        $permissionNames = $validated['permissions'] ?? [];

        $permissions = Permission::query()
            ->whereIn('name', $permissionNames)
            ->pluck('id', 'name');

        $unknownNames = array_diff($permissionNames, $permissions->keys()->all());

        if (!empty($unknownNames)) {
            return response()->json([
                'message' => 'Unknown permissions: ' . implode(', ', $unknownNames),
            ], 422);
        }

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
}
