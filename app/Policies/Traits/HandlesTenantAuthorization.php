<?php

namespace App\Policies\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait HandlesTenantAuthorization
{
    protected function sameTenant(User $user, Model $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $modelCompanyId = $model->getAttribute('company_id');

        if ($modelCompanyId === null) {
            return false;
        }

        if ($user->company_id === null) {
            return false;
        }

        return (int) $modelCompanyId === (int) $user->company_id;
    }
}
