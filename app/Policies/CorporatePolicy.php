<?php

namespace App\Policies;

use App\Models\Corporate;
use App\Models\User;

class CorporatePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id) {
            return false;
        }

        return $user->hasPermission('view_corporates');
    }

    public function view(User $user, Corporate $corporate): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id || $user->company_id !== $corporate->company_id) {
            return false;
        }

        return $user->hasPermission('view_corporates');
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id) {
            return false;
        }

        return $user->hasPermission('create_corporate');
    }

    public function update(User $user, Corporate $corporate): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id || $user->company_id !== $corporate->company_id) {
            return false;
        }

        return $user->hasPermission('update_corporate');
    }

    public function delete(User $user, Corporate $corporate): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id || $user->company_id !== $corporate->company_id) {
            return false;
        }

        return $user->hasPermission('deactivate_corporate');
    }
}
