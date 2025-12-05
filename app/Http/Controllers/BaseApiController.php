<?php

namespace App\Http\Controllers;

// No te estoy diciendo que copies literal, solo la idea.
use App\Models\Company;
use App\Models\User;

abstract class BaseApiController extends Controller
{
    protected function user(): User
    {
        return request()->user();
    }

    protected function tenant(): ?Company
    {
        /** @var Company|null */
        return request()->attributes->get('tenant'); // lo setea ResolveTenantFromUser
    }

    protected function isSuperAdmin(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    protected function withCompanyId(array $data): array
    {
        if ($this->isSuperAdmin() && isset($data['company_id'])) {
            return $data;
        }

        $tenant = $this->tenant();

        if ($tenant) {
            $data['company_id'] = $tenant->id;
        }

        return $data;
    }
}

