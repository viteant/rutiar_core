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

    /**
     * Injects the correct company_id into the payload based on the current user / tenant.
     *
     * For superadmin:
     *  - If company_id is present in $data, it is used as-is (already validated).
     *  - If company_id is missing and $fallbackCompanyId is provided, it will be used.
     *  - If both are missing, a 422 error is thrown.
     *
     * For normal users:
     *  - company_id is always forced from the resolved tenant.
     *
     * @param array<string, mixed> $data
     */
    protected function withCompanyId(array $data, ?int $fallbackCompanyId = null): array
    {
        if ($this->isSuperAdmin()) {
            if (! isset($data['company_id'])) {
                if ($fallbackCompanyId !== null) {
                    $data['company_id'] = $fallbackCompanyId;
                } else {
                    abort(422, 'company_id is required for superadmin operations.');
                }
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
