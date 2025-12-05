<?php

namespace App\Http\Controllers\Transport\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasTransportIndexFilters
{
    protected function applyCompanyFilter(Builder $query, Request $request): Builder
    {
        if ($this->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->integer('company_id'));
            }

            return $query;
        }

        $company = $this->tenant();

        if (! $company instanceof Company) {
            abort(403);
        }

        return $query->where('company_id', $company->id);
    }

    protected function applyPartnerFilter(Builder $query, Request $request): Builder
    {
        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->integer('partner_id'));
        }

        return $query;
    }
}
