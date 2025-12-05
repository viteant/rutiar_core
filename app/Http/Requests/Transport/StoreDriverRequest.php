<?php

namespace App\Http\Requests\Transport;

use App\Rules\PartnerBelongsToCompany;
use Illuminate\Foundation\Http\FormRequest;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Policies handled in controller
        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if ($user && !$user->isSuperAdmin()) {
            $this->merge([
                'company_id' => $user->company_id,
            ]);
        }
    }

    public function rules(): array
    {
        $baseRules = [
            'full_name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];

        $rules = $baseRules;

        $rules['company_id'] = ['required', 'integer', 'exists:companies,id'];

        $companyId = (int)$this->input('company_id');

        $rules['partner_id'] = [
            'required',
            'integer',
            new PartnerBelongsToCompany($companyId),
        ];

        return $rules;
    }
}
