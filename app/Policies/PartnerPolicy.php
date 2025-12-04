<?php

namespace App\Policies;

use App\Models\Partner;
use App\Models\User;

class PartnerPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->company_id) {
            return false;
        }

        return $user->hasPermission('view_partners');
    }

    public function view(User $user, Partner $partner): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$this->inSameTenant($user, $partner)) {
            return false;
        }

        return $user->hasPermission('view_partners');
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->company_id) {
            return false;
        }

        return $user->hasPermission('create_partner');
    }

    public function update(User $user, Partner $partner): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$this->inSameTenant($user, $partner)) {
            return false;
        }

        return $user->hasPermission('update_partner');
    }

    public function delete(User $user, Partner $partner): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$this->inSameTenant($user, $partner)) {
            return false;
        }

        return $user->hasPermission('delete_partner');
    }

    protected function inSameTenant(User $user, Partner $partner): bool
    {
        return $user->company_id !== null
            && $user->company_id === $partner->company_id;
    }
}
