<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreCorporateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $base = [
            'name'          => ['required', 'string', 'max:150'],
            'tax_id'        => ['nullable', 'string', 'max:50'],
            'contact_name'  => ['nullable', 'string', 'max:150'],
            'contact_email' => ['nullable', 'string', 'max:150', 'email'],
            'is_active'     => ['sometimes', 'boolean'],
        ];

        if ($this->user()?->isSuperAdmin()) {
            $base['company_id'] = ['required', 'integer', 'exists:companies,id'];
        }

        return $base;
    }
}
