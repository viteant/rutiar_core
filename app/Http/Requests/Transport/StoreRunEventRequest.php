<?php

namespace App\Http\Requests\Transport;

use App\Models\Run;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRunEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El control fino lo hace la RunPolicy en el controlador
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var \App\Models\Run|null $run */
        $run = $this->route('run');
        $user = $this->user();

        $isSuperAdmin = $user !== null && $user->isSuperAdmin();
        $companyId = $run?->company_id;

        $companyScoped = static function (string $table) use ($isSuperAdmin, $companyId) {
            $rule = Rule::exists($table, 'id');

            if (! $isSuperAdmin && $companyId !== null) {
                $rule->where('company_id', $companyId);
            }

            return $rule;
        };

        return [
            // El run viene por la ruta, no desde el payload
            'route_definition_passenger_id' => [
                'nullable',
                'integer',
                $companyScoped('route_definition_passengers'),
            ],
            'passenger_id' => [
                'nullable',
                'integer',
                $companyScoped('passengers'),
            ],

            'event_type' => [
                'required',
                'string',
                Rule::in([
                    'boarding',
                    'drop_off',
                    'no_show',
                    'added_on_route',
                    'removed_from_route',
                    'incident',
                ]),
            ],

            'incident_type' => [
                'nullable',
                'string',
                Rule::in([
                    'security',
                    'health',
                    'vehicle_breakdown',
                    'traffic',
                    'other',
                ]),
            ],

            'source' => [
                'nullable',
                'string',
                Rule::in([
                    'driver_app',
                    'backoffice',
                    'system',
                ]),
            ],

            'occurred_at' => ['required', 'date'],

            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],

            'wait_seconds' => ['nullable', 'integer', 'min:0'],

            'notes' => ['nullable', 'string'],
        ];
    }
}
