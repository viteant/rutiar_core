<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;

abstract class BaseApiController extends Controller
{
    protected function user(): User
    {
        /** @var User|null $user */
        $user = request()->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }

    /**
     * Backwards compatible helper.
     * If you are calling this, you are assuming tenant MUST exist.
     */
    protected function tenant(): Company
    {
        return $this->tenantOrFail();
    }

    /**
     * Explicit strict version.
     */
    protected function tenantOrFail(): Company
    {
        /** @var Company|null $tenant */
        $tenant = request()->attributes->get('tenant');

        if (! $tenant) {
            abort(403, 'Tenant not resolved.');
        }

        return $tenant;
    }

    protected function isSuperAdmin(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    protected function withCompanyId(array $data): array
    {
        if ($this->isSuperAdmin()) {
            if (! isset($data['company_id'])) {
                abort(422, 'company_id is required for superadmin operations.');
            }

            return $data;
        }

        $tenant = $this->tenantOrFail();
        $data['company_id'] = $tenant->id;

        return $data;
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
