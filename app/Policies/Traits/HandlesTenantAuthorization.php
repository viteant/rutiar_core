<?php

namespace App\Policies\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait HandlesTenantAuthorization
{
    /**
     * Check if user and model belong to the same tenant.
     * SUPERADMIN bypasses tenant restriction.
     */
    protected function sameTenant(User $user, Model $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!property_exists($model, 'company_id') && !isset($model->company_id)) {
            return false;
        }

        if (!$user->company_id) {
            return false;
        }

        return (int) $model->company_id === (int) $user->company_id;
    }
}
