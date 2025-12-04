<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPermissionPolicy
{
    /**
     * Only COMPANY_ADMIN of the same company can manage role permissions.
     * SUPERADMIN is explicitly excluded (global config only).
     */
    public function manageRolePermissions(User $user, Company $company): bool
    {
        if ($user->isSuperAdmin()) {
            return false;
        }

        if (!$user->company_id || $user->company_id !== $company->id) {
            return false;
        }

        return $user->hasPermission('manage_company_role_permissions');
    }

    /**
     * Only COMPANY_ADMIN of the same company can manage user-specific permissions.
     * SUPERADMIN is explicitly excluded (global config only).
     */
    public function manageUserPermissions(User $user, Company $company): bool
    {
        if ($user->isSuperAdmin()) {
            return false;
        }

        if (!$user->company_id || $user->company_id !== $company->id) {
            return false;
        }

        return $user->hasPermission('manage_company_user_permissions');
    }

    /**
     * View company operational settings.
     */
    public function viewSettings(User $user, Company $company): bool
    {
        if ($user->isSuperAdmin()) {
            return false;
        }

        if (!$user->company_id || $user->company_id !== $company->id) {
            return false;
        }

        return $user->hasPermission('view_company_settings');
    }

    /**
     * Update company operational settings.
     */
    public function updateSettings(User $user, Company $company): bool
    {
        if ($user->isSuperAdmin()) {
            return false;
        }

        if (!$user->company_id || $user->company_id !== $company->id) {
            return false;
        }

        return $user->hasPermission('update_company_settings');
    }
}
