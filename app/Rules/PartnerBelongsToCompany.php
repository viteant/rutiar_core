<?php

namespace App\Rules;

use App\Models\Partner;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PartnerBelongsToCompany implements ValidationRule
{
    public function __construct(
        protected int $companyId
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Partner::query()
            ->where('company_id', $this->companyId)
            ->where('id', $value)
            ->exists();

        if (! $exists) {
            $fail('The selected :attribute is invalid for the current company.');
        }
    }
}
