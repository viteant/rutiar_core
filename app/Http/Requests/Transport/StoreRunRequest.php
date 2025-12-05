<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRunRequest extends FormRequest
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
            // SUPERADMIN can pass company_id explicitly; for normal users it will be injected
            'company_id' => ['sometimes', 'integer', 'exists:companies,id'],

            'route_definition_id' => ['required', 'integer', $companyScoped('route_definitions')],
            'service_date' => ['required', 'date'],

            'partner_id' => ['required', 'integer', $companyScoped('partners')],
            'driver_id' => ['nullable', 'integer', $companyScoped('drivers')],
            'vehicle_id' => ['nullable', 'integer', $companyScoped('vehicles')],

            'fare_amount' => ['nullable', 'numeric', 'min:0'],

            'route_billing_code_snap' => ['nullable', 'string', 'max:50'],

            // manifest_snapshot will usually be filled by a service, not from the client
            'manifest_snapshot' => ['sometimes', 'array'],

            'created_by_user_id' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }
}
