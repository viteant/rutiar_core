<?php

namespace App\Policies;

use App\Models\Route;
use App\Models\User;
use App\Policies\Traits\HandlesTenantAuthorization;

class RoutePolicy
{
    use HandlesTenantAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_routes');
    }

    public function view(User $user, Route $route): bool
    {
        if (!$this->sameTenant($user, $route)) {
            return false;
        }

        return $user->hasPermission('view_routes');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create_route');
    }

    public function update(User $user, Route $route): bool
    {
        if (!$this->sameTenant($user, $route)) {
            return false;
        }

        return $user->hasPermission('update_route');
    }

    public function delete(User $user, Route $route): bool
    {
        if (!$this->sameTenant($user, $route)) {
            return false;
        }

        return $user->hasPermission('deactivate_route');
    }
}
