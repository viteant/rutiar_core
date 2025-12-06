<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;

class StoreRunGpsPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        // RunPolicy se encarga del control real en el controlador
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recorded_at' => ['required', 'date'],

            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],

            'speed_kmh' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
