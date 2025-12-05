<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StoreRouteDefinitionPassengerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Policy + permissions handle authorization
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

        $companyScoped = static function (string $table) use ($isSuperAdmin, $tenant): Exists {
            $rule = Rule::exists($table, 'id');

            if (! $isSuperAdmin && $tenant !== null) {
                $rule->where('company_id', $tenant->id);
            }

            return $rule;
        };

        return [
            // For SUPERADMIN, BaseApiController::withCompanyId() will enforce presence and validity.
            'company_id' => ['sometimes', 'integer', 'exists:companies,id'],

            'route_definition_id' => ['required', 'integer', $companyScoped('route_definitions')],
            'passenger_id' => ['required', 'integer', $companyScoped('passengers')],

            'pickup_order' => ['required', 'integer', 'min:1'],

            'planned_pickup_time' => ['nullable', 'date_format:H:i:s'],
            'pickup_address' => ['nullable', 'string', 'max:255'],

            'pickup_lat' => ['nullable', 'numeric'],
            'pickup_lng' => ['nullable', 'numeric'],

            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
