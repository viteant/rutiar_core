<?php

namespace App\Policies;

use App\Models\Driver;
use App\Models\User;

class DriverPolicy
{
    /**
     * Determine whether the user can view any drivers.
     */
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id) {
            return false;
        }

        return $user->hasPermission('view_drivers');
    }

    /**
     * Determine whether the user can view the driver.
     */
    public function view(User $user, Driver $driver): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id || $user->company_id !== $driver->company_id) {
            return false;
        }

        return $user->hasPermission('view_drivers');
    }

    /**
     * Determine whether the user can create drivers.
     */
    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id) {
            return false;
        }

        return $user->hasPermission('create_driver');
    }

    /**
     * Determine whether the user can update the driver.
     */
    public function update(User $user, Driver $driver): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id || $user->company_id !== $driver->company_id) {
            return false;
        }

        return $user->hasPermission('update_driver');
    }

    /**
     * Determine whether the user can deactivate (delete) the driver.
     */
    public function delete(User $user, Driver $driver): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id || $user->company_id !== $driver->company_id) {
            return false;
        }

        // DELETE endpoint maps to "deactivate_driver" permission in this domain
        return $user->hasPermission('deactivate_driver');
    }
}
