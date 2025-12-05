<?php

namespace App\Http\Requests\Transport;

use App\Enums\RunDirection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StoreRouteDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
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
            'company_id' => ['sometimes', 'integer', 'exists:companies,id'],

            'route_id' => ['required', 'integer', $companyScoped('routes')],
            'corporate_id' => ['required', 'integer', $companyScoped('corporates')],
            'partner_id' => ['required', 'integer', $companyScoped('partners')],
            'driver_id' => ['nullable', 'integer', $companyScoped('drivers')],

            // AQUÍ: nada de Rule::enum, sólo valores permitidos
            'direction' => ['required', Rule::in(RunDirection::values())],
            'reference_time' => ['nullable', 'date_format:H:i:s'],

            'billing_code' => ['nullable', 'string', 'max:50'],
            'base_fare_amount' => ['nullable', 'numeric', 'min:0'],

            'version' => ['sometimes', 'integer', 'min:1'],
            'previous_definition_id' => ['nullable', 'integer', $companyScoped('route_definitions')],

            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
