<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePassengerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->route('passenger')->company_id;

        return [
            'full_name'     => ['sometimes', 'required', 'string', 'max:150'],
            'employee_code' => ['sometimes', 'nullable', 'string', 'max:80'],
            'document_id'   => ['sometimes', 'nullable', 'string', 'max:80'],
            'home_address'  => ['sometimes', 'nullable', 'string'],
            'home_lat'      => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'home_lng'      => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'shift_code'    => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_active'     => ['sometimes', 'boolean'],

            'corporate_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('corporates', 'id')->where(fn($q) => $q->where('company_id', $companyId)),
            ],
        ];
    }
}
