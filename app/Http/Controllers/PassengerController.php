<?php

namespace App\Http\Controllers;

use App\Models\Corporate;
use App\Models\Passenger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PassengerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->authorize('viewAny', Passenger::class);

        $query = Passenger::query()
            ->with(['corporate'])
            ->where('is_active', true);

        if (! $user->isSuperAdmin()) {
            $query->where('company_id', $user->company_id);
        } else {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->integer('company_id'));
            }
        }

        if ($request->filled('corporate_id')) {
            $query->where('corporate_id', $request->integer('corporate_id'));
        }

        $passengers = $query->get();

        return response()->json(['data' => $passengers]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->authorize('create', Passenger::class);

        $isSuperAdmin = $user->isSuperAdmin();

        $baseRules = [
            'full_name'     => ['required', 'string', 'max:150'],
            'employee_code' => ['nullable', 'string', 'max:80'],
            'document_id'   => ['nullable', 'string', 'max:80'],
            'home_address'  => ['nullable', 'string'],
            'home_lat'      => ['nullable', 'numeric', 'between:-90,90'],
            'home_lng'      => ['nullable', 'numeric', 'between:-180,180'],
            'shift_code'    => ['nullable', 'string', 'max:50'],
            'is_active'     => ['sometimes', 'boolean'],
        ];

        if ($isSuperAdmin) {
            $companyId = $request->integer('company_id');

            $rules = array_merge($baseRules, [
                'company_id' => ['required', 'integer', 'exists:companies,id'],
                'corporate_id' => [
                    'required',
                    'integer',
                    Rule::exists('corporates', 'id')->where(function ($query) use ($companyId) {
                        $query->where('company_id', $companyId);
                    }),
                ],
            ]);
        } else {
            $companyId = $user->company_id;

            $rules = array_merge($baseRules, [
                'corporate_id' => [
                    'required',
                    'integer',
                    Rule::exists('corporates', 'id')->where(function ($query) use ($companyId) {
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

        // Seguridad extra: corporate pertenece al mismo tenant
        $corporate = Corporate::where('company_id', $companyId)
            ->findOrFail($validated['corporate_id']);

        $passenger = Passenger::create($validated);

        return response()->json(['data' => $passenger], 201);
    }

    public function show(Request $request, Passenger $passenger): JsonResponse
    {
        $this->authorize('view', $passenger);

        $passenger->load(['corporate']);

        return response()->json(['data' => $passenger]);
    }

    public function update(Request $request, Passenger $passenger): JsonResponse
    {
        $this->authorize('update', $passenger);

        $companyId = $passenger->company_id;

        $rules = [
            'full_name'     => ['sometimes', 'required', 'string', 'max:150'],
            'employee_code' => ['sometimes', 'nullable', 'string', 'max:80'],
            'document_id'   => ['sometimes', 'nullable', 'string', 'max:80'],
            'home_address'  => ['sometimes', 'nullable', 'string'],
            'home_lat'      => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'home_lng'      => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'shift_code'    => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_active'     => ['sometimes', 'boolean'],
            'corporate_id'  => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('corporates', 'id')->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                }),
            ],
        ];

        $validated = $request->validate($rules);

        if (array_key_exists('corporate_id', $validated)) {
            $corporate = Corporate::where('company_id', $companyId)
                ->findOrFail($validated['corporate_id']);
        }

        $passenger->fill($validated);
        $passenger->save();

        return response()->json(['data' => $passenger]);
    }

    public function destroy(Request $request, Passenger $passenger): JsonResponse
    {
        $this->authorize('delete', $passenger);

        if ($passenger->is_active) {
            $passenger->is_active = false;
            $passenger->save();
        }

        return response()->json(null, 204);
    }
}
