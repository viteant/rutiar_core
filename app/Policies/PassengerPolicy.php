<?php

namespace App\Policies;

use App\Models\Passenger;
use App\Models\User;

class PassengerPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id) {
            return false;
        }

        return $user->hasPermission('view_passengers');
    }

    public function view(User $user, Passenger $passenger): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id || $user->company_id !== $passenger->company_id) {
            return false;
        }

        return $user->hasPermission('view_passengers');
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id) {
            return false;
        }

        return $user->hasPermission('create_passenger');
    }

    public function update(User $user, Passenger $passenger): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id || $user->company_id !== $passenger->company_id) {
            return false;
        }

        return $user->hasPermission('update_passenger');
    }

    public function delete(User $user, Passenger $passenger): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id || $user->company_id !== $passenger->company_id) {
            return false;
        }

        return $user->hasPermission('deactivate_passenger');
    }
}
