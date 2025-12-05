<?php

namespace App\Policies;

use App\Models\RouteDefinitionPassenger;
use App\Models\User;
use App\Policies\Traits\HandlesTenantAuthorization;

class RouteDefinitionPassengerPolicy
{
    use HandlesTenantAuthorization;

    public function viewAny(User $user): bool
    {
        // Viewing manifest is tied to viewing definitions
        return $user->hasPermission('view_route_definitions');
    }

    public function view(User $user, RouteDefinitionPassenger $item): bool
    {
        if (! $this->sameTenant($user, $item)) {
            return false;
        }

        return $user->hasPermission('view_route_definitions');
    }

    public function create(User $user): bool
    {
        // Editing manifest is part of updating a definition
        return $user->hasPermission('update_route_definition');
    }

    public function update(User $user, RouteDefinitionPassenger $item): bool
    {
        if (! $this->sameTenant($user, $item)) {
            return false;
        }

        return $user->hasPermission('update_route_definition');
    }

    public function delete(User $user, RouteDefinitionPassenger $item): bool
    {
        if (! $this->sameTenant($user, $item)) {
            return false;
        }

        return $user->hasPermission('update_route_definition');
    }
}
