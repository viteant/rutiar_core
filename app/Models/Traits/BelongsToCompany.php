<?php

namespace App\Models\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany(Builder $query, Company $company): Builder
    {
        return $query->where('company_id', $company->id);
    }

    public function belongsToCompany(Company $company): bool
    {
        if ($this->company_id === null) {
            return false;
        }

        return (int) $this->company_id === (int) $company->id;
    }
}
