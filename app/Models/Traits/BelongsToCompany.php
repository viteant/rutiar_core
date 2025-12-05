<?php

namespace App\Models\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompany
{
    public function scopeForCompany(Builder $query, Company $company): Builder
    {
        return $query->where('company_id', $company->id);
    }

    public function belongsToCompany(Company $company): bool
    {
        return $this->company_id === $company->id;
    }
}
