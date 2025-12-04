<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->authorize('viewAny', Vehicle::class);

        $query = Vehicle::query()
            ->with(['company', 'partner'])
            ->where('is_active', true);

        if (! $user->isSuperAdmin()) {
            $query->where('company_id', $user->company_id);
        } else {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->integer('company_id'));
            }
        }

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->integer('partner_id'));
        }

        $vehicles = $query->get();

        return response()->json(['data' => $vehicles]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->authorize('create', Vehicle::class);

        $isSuperAdmin = $user->isSuperAdmin();

        $baseRules = [
            'plate'    => ['required', 'string', 'max:20', 'unique:vehicles,plate'],
            'model'    => ['nullable', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_active'=> ['sometimes', 'boolean'],
        ];

        if ($isSuperAdmin) {
            $companyId = $request->integer('company_id');

            $rules = array_merge($baseRules, [
                'company_id' => ['required', 'integer', 'exists:companies,id'],
                'partner_id' => [
                    'required',
                    'integer',
                    Rule::exists('partners', 'id')->where(function ($query) use ($companyId) {
                        $query->where('company_id', $companyId);
                    }),
                ],
            ]);
        } else {
            $companyId = $user->company_id;

            $rules = array_merge($baseRules, [
                'partner_id' => [
                    'required',
                    'integer',
                    Rule::exists('partners', 'id')->where(function ($query) use ($companyId) {
                        $query->where('company_id', $companyId);
                    }),
                ],
            ]);
        }

        $validated = $request->validate($rules);

        if (! $isSuperAdmin) {
            $validated['company_id'] = $companyId;
        } else {
            $companyId = $validated['company_id'];
        }

        // Segundo check de seguridad por si acaso
        $partner = Partner::where('company_id', $companyId)
            ->findOrFail($validated['partner_id']);

        $vehicle = Vehicle::create($validated);

        return response()->json(['data' => $vehicle], 201);
    }

    public function show(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('view', $vehicle);

        $vehicle->load(['company', 'partner']);

        return response()->json(['data' => $vehicle]);
    }

    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $user = $request->user();

        $this->authorize('update', $vehicle);

        $companyId = $vehicle->company_id;

        $rules = [
            'plate'    => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('vehicles', 'plate')->ignore($vehicle->id),
            ],
            'model'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'is_active'=> ['sometimes', 'boolean'],
            'partner_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('partners', 'id')->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                }),
            ],
        ];

        $validated = $request->validate($rules);

        if (array_key_exists('partner_id', $validated)) {
            $partner = Partner::where('company_id', $companyId)
                ->findOrFail($validated['partner_id']);
        }

        $vehicle->fill($validated);
        $vehicle->save();

        return response()->json(['data' => $vehicle]);
    }

    public function destroy(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('delete', $vehicle);

        if ($vehicle->is_active) {
            $vehicle->is_active = false;
            $vehicle->save();
        }

        return response()->json(null, 204);
    }
}
