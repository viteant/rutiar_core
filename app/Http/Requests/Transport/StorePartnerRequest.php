<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;

class StorePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Policies are handled in the controller
        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if ($user && ! $user->isSuperAdmin()) {
            $this->merge([
                'company_id' => $user->company_id,
            ]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'name'         => ['required', 'string', 'max:150'],
            'code'         => ['required', 'string', 'max:50'],
            'tax_id'       => ['nullable', 'string', 'max:50'],
            'is_active'    => ['sometimes', 'boolean'],
            'driver_quota' => ['nullable', 'integer', 'min:0'],
        ];

        // After prepareForValidation, non-superadmin always has company_id set.
        $rules['company_id'] = ['required', 'integer', 'exists:companies,id'];

        return $rules;
    }
}
