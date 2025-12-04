<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id) {
            return false;
        }

        return $user->hasPermission('view_vehicles');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id || $user->company_id !== $vehicle->company_id) {
            return false;
        }

        return $user->hasPermission('view_vehicles');
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id) {
            return false;
        }

        return $user->hasPermission('create_vehicle');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id || $user->company_id !== $vehicle->company_id) {
            return false;
        }

        return $user->hasPermission('update_vehicle');
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id || $user->company_id !== $vehicle->company_id) {
            return false;
        }

        // HTTP DELETE = desactivar vehículo
        return $user->hasPermission('deactivate_vehicle');
    }
}
