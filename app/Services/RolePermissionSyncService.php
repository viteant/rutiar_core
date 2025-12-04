<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Support\Collection;

class RolePermissionSyncService
{
    /**
     * Sync default role permissions for a given company.
     *
     * - Reads config('role_permissions.defaults')
     * - Maps permission names to IDs using the permissions table
     * - Creates missing RolePermission records (idempotent)
     */
    public function syncForCompany(Company $company): void
    {
        $defaults = config('role_permissions.defaults', []);

        if (empty($defaults)) {
            return;
        }

        /** @var Collection<string, int> $permissionsByName */
        $permissionsByName = Permission::query()
            ->pluck('id', 'name');

        foreach ($defaults as $role => $permissionNames) {
            if (!is_array($permissionNames)) {
                continue;
            }

            foreach ($permissionNames as $permissionName) {
                $permissionId = $permissionsByName->get($permissionName);

                // Ignore config entries that don't exist in DB
                if (!$permissionId) {
                    continue;
                }

                RolePermission::query()->firstOrCreate([
                    'company_id' => $company->id,
                    'role' => $role,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }
}
