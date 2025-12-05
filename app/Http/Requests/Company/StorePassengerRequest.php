<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePassengerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Policy se evalúa en el controller
        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();

        // Non-superadmin: company_id siempre viene del user
        if ($user && ! $user->isSuperAdmin()) {
            $this->merge([
                'company_id' => $user->company_id,
            ]);
        }
    }

    public function rules(): array
    {
        $user = $this->user();

        $rules = [
            'full_name'     => ['required', 'string', 'max:150'],
            'employee_code' => ['nullable', 'string', 'max:80'],
            'document_id'   => ['nullable', 'string', 'max:80'],
            'home_address'  => ['nullable', 'string'],
            'home_lat'      => ['nullable', 'numeric', 'between:-90,90'],
            'home_lng'      => ['nullable', 'numeric', 'between:-180,180'],
            'shift_code'    => ['nullable', 'string', 'max:50'],
            'is_active'     => ['sometimes', 'boolean'],
        ];

        // Después de prepareForValidation:
        // - Superadmin: company_id viene del body
        // - Company user: company_id viene del usuario
        $rules['company_id'] = ['required', 'integer', 'exists:companies,id'];

        $companyId = (int) $this->input('company_id');

        $rules['corporate_id'] = [
            'required',
            'integer',
            Rule::exists('corporates', 'id')->where(
                fn ($q) => $q->where('company_id', $companyId)
            ),
        ];

        return $rules;
    }
}
