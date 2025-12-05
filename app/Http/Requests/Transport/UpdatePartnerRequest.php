<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['sometimes', 'string', 'max:150'],
            'code'         => ['sometimes', 'string', 'max:50'],
            'tax_id'       => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_active'    => ['sometimes', 'boolean'],
            'driver_quota' => ['sometimes', 'nullable', 'integer', 'min:0'],
            // company_id no se toca aquí, igual que en tu versión original
        ];
    }
}
