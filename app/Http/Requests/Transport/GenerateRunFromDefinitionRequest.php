<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateRunFromDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Policy de Run se aplica en el controlador
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
            'route_definition_id' => ['required', 'integer', $companyScoped('route_definitions')],
            'service_date'        => ['required', 'date'],

            'driver_id'           => ['sometimes', 'nullable', 'integer', $companyScoped('drivers')],
            'vehicle_id'          => ['sometimes', 'nullable', 'integer', $companyScoped('vehicles')],
            'fare_amount'         => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
