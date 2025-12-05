<?php

namespace App\Policies;

use App\Enums\RunStatus;
use App\Models\Run;
use App\Models\User;
use App\Policies\Traits\HandlesTenantAuthorization;
use Illuminate\Auth\Access\Response;

class RunPolicy
{
    use HandlesTenantAuthorization;

    private const VIEW_PERMISSION   = 'view_runs';
    private const APPROVE_PERMISSION = 'approve_run';
    private const CANCEL_PERMISSION  = 'cancel_run';
    private const FORCE_CLOSE_PERMISSION = 'force_close_run';

    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission(self::VIEW_PERMISSION);
    }

    public function view(User $user, Run $run): bool
    {
        if (! $this->sameTenant($user, $run)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission(self::VIEW_PERMISSION);
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Crear runs se considera parte del flujo operativo,
        // pero restringido a quienes pueden ver/gestionar runs.
        return $user->hasPermission(self::VIEW_PERMISSION);
    }

    public function update(User $user, Run $run): Response
    {
        if (! $this->sameTenant($user, $run)) {
            return Response::deny();
        }

        if (! $run->canBeEdited()) {
            return Response::deny('Run is in a terminal status and cannot be edited.');
        }

        if ($user->isSuperAdmin()) {
            return Response::allow();
        }

        if (! $user->hasPermission(self::VIEW_PERMISSION)) {
            return Response::deny();
        }

        return Response::allow();
    }

    public function approve(User $user, Run $run): Response
    {
        if (! $this->sameTenant($user, $run)) {
            return Response::deny();
        }

        /** @var RunStatus $status */
        $status = $run->status;

        if (! in_array($status, [RunStatus::PLANNED], true)) {
            return Response::deny('Only planned runs can be approved.');
        }

        if ($user->isSuperAdmin()) {
            return Response::allow();
        }

        if (! $user->hasPermission(self::APPROVE_PERMISSION)) {
            return Response::deny();
        }

        return Response::allow();
    }

    public function cancel(User $user, Run $run): Response
    {
        if (! $this->sameTenant($user, $run)) {
            return Response::deny();
        }

        if ($run->status === RunStatus::COMPLETED) {
            return Response::deny('Completed runs cannot be canceled.');
        }

        if ($user->isSuperAdmin()) {
            return Response::allow();
        }

        if (! $user->hasPermission(self::CANCEL_PERMISSION)) {
            return Response::deny();
        }

        return Response::allow();
    }

    public function forceClose(User $user, Run $run): Response
    {
        if (! $this->sameTenant($user, $run)) {
            return Response::deny();
        }

        if ($user->isSuperAdmin()) {
            return Response::allow();
        }

        if (! $user->hasPermission(self::FORCE_CLOSE_PERMISSION)) {
            return Response::deny();
        }

        return Response::allow();
    }
}
