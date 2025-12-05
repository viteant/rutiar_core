<?php

namespace App\Http\Requests\Transport;

use App\Models\Driver;
use App\Rules\PartnerBelongsToCompany;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Driver $driver */
        $driver = $this->route('driver');

        $companyId = $driver->company_id;

        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:150'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'license_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],

            'partner_id' => [
                'sometimes',
                'required',
                'integer',
                new PartnerBelongsToCompany($companyId),
            ],
        ];
    }
}
