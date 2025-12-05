<?php

namespace App\Http\Requests\Transport;

use App\Models\Vehicle;
use App\Rules\PartnerBelongsToCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Vehicle $vehicle */
        $vehicle = $this->route('vehicle');
        $companyId = $vehicle->company_id;

        return [
            'plate' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('vehicles', 'plate')->ignore($vehicle->id),
            ],
            'model'     => ['sometimes', 'nullable', 'string', 'max:100'],
            'capacity'  => ['sometimes', 'nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],

            'partner_id' => [
                'sometimes',
                'required',
                'integer',
                new PartnerBelongsToCompany($companyId),
            ],
        ];
    }
}
