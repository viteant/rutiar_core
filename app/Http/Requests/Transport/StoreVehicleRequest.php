<?php

namespace App\Http\Requests\Transport;

use App\Rules\PartnerBelongsToCompany;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
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
            'plate'     => ['required', 'string', 'max:20', 'unique:vehicles,plate'],
            'model'     => ['nullable', 'string', 'max:100'],
            'capacity'  => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        $rules['company_id'] = ['required', 'integer', 'exists:companies,id'];

        $companyId = (int) $this->input('company_id');

        $rules['partner_id'] = [
            'required',
            'integer',
            new PartnerBelongsToCompany($companyId),
        ];

        return $rules;
    }
}
