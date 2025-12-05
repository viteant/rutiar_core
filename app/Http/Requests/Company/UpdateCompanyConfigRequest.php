<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled in the controller via policies
        return true;
    }

    public function rules(): array
    {
        return [
            'planning_cutoff_time' => ['nullable', 'date_format:H:i'],
            'default_waiting_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'allow_driver_reorder' => ['nullable', 'boolean'],
            'driver_quota_default' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
