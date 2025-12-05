<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Access control is handled by RunPolicy in the controller
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();
        $isSuperAdmin = $user !== null && $user->isSuperAdmin();

        /** @var \App\Models\Company|null $tenant */
        $tenant = $this->attributes->get('tenant');

        $companyScoped = static function (string $table) use ($isSuperAdmin, $tenant) {
            $rule = Rule::exists($table, 'id');

            if (! $isSuperAdmin && $tenant !== null) {
                $rule->where('company_id', $tenant->id);
            }

            return $rule;
        };

        return [
            'company_id' => ['sometimes', 'integer', 'exists:companies,id'],

            'route_definition_id' => ['sometimes', 'integer', $companyScoped('route_definitions')],
            'service_date' => ['sometimes', 'date'],

            'partner_id' => ['sometimes', 'integer', $companyScoped('partners')],
            'driver_id' => ['sometimes', 'nullable', 'integer', $companyScoped('drivers')],
            'vehicle_id' => ['sometimes', 'nullable', 'integer', $companyScoped('vehicles')],

            'fare_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],

            'route_billing_code_snap' => ['sometimes', 'nullable', 'string', 'max:50'],

            'manifest_snapshot' => ['sometimes', 'array'],

            'created_by_user_id' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }
}
