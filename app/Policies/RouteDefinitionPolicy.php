<?php

namespace App\Policies;

use App\Models\RouteDefinition;
use App\Models\User;
use App\Policies\Traits\HandlesTenantAuthorization;

class RouteDefinitionPolicy
{
    use HandlesTenantAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_route_definitions');
    }

    public function view(User $user, RouteDefinition $routeDefinition): bool
    {
        if (! $this->sameTenant($user, $routeDefinition)) {
            return false;
        }

        return $user->hasPermission('view_route_definitions');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create_route_definition');
    }

    public function update(User $user, RouteDefinition $routeDefinition): bool
    {
        if (! $this->sameTenant($user, $routeDefinition)) {
            return false;
        }

        return $user->hasPermission('update_route_definition');
    }

    public function delete(User $user, RouteDefinition $routeDefinition): bool
    {
        if (! $this->sameTenant($user, $routeDefinition)) {
            return false;
        }

        return $user->hasPermission('deactivate_route_definition');
    }
}
