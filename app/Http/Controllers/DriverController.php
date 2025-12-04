<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DriverController extends Controller
{
    /**
     * Display a listing of the drivers.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->authorize('viewAny', Driver::class);

        $query = Driver::query()
            ->with(['company', 'partner', 'user'])
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

        $drivers = $query->get();

        return response()->json(['data' => $drivers]);
    }

    /**
     * Store a newly created driver in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->authorize('create', Driver::class);

        $isSuperAdmin = $user->isSuperAdmin();

        $baseRules = [
            'full_name'      => ['required', 'string', 'max:150'],
            'phone'          => ['nullable', 'string', 'max:50'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'is_active'      => ['sometimes', 'boolean'],
            'user_id'        => ['nullable', 'integer', 'exists:users,id'],
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

        $partner = Partner::where('company_id', $companyId)
            ->findOrFail($validated['partner_id']);

        $quota = $partner->effectiveDriverQuota();
        if ($quota !== null) {
            $currentDriverCount = $partner->drivers()->count();

            if ($currentDriverCount >= $quota) {
                return response()->json([
                    'message' => 'Driver quota exceeded for this partner.',
                    'errors'  => [
                        'partner_id' => ['Driver quota exceeded for this partner.'],
                    ],
                ], 422);
            }
        }

        $driver = Driver::create($validated);

        return response()->json(['data' => $driver], 201);
    }

    /**
     * Display the specified driver.
     */
    public function show(Request $request, Driver $driver): JsonResponse
    {
        $this->authorize('view', $driver);

        $driver->load(['company', 'partner', 'user']);

        return response()->json(['data' => $driver]);
    }

    /**
     * Update the specified driver in storage.
     */
    public function update(Request $request, Driver $driver): JsonResponse
    {
        $user = $request->user();

        $this->authorize('update', $driver);

        $companyId = $driver->company_id;

        $rules = [
            'full_name'      => ['sometimes', 'required', 'string', 'max:150'],
            'phone'          => ['sometimes', 'nullable', 'string', 'max:50'],
            'license_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_active'      => ['sometimes', 'boolean'],
            'user_id'        => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'partner_id'     => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('partners', 'id')->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                }),
            ],
        ];

        $validated = $request->validate($rules);

        if (array_key_exists('partner_id', $validated) && $validated['partner_id'] !== $driver->partner_id) {
            $partner = Partner::where('company_id', $companyId)
                ->findOrFail($validated['partner_id']);

            $quota = $partner->effectiveDriverQuota();
            if ($quota !== null) {
                $currentDriverCount = $partner->drivers()->count();

                if ($currentDriverCount >= $quota) {
                    return response()->json([
                        'message' => 'Driver quota exceeded for this partner.',
                        'errors'  => [
                            'partner_id' => ['Driver quota exceeded for this partner.'],
                        ],
                    ], 422);
                }
            }
        }

        $driver->fill($validated);
        $driver->save();

        return response()->json(['data' => $driver]);
    }

    /**
     * Deactivate the specified driver (soft delete).
     */
    public function destroy(Request $request, Driver $driver): JsonResponse
    {
        $this->authorize('delete', $driver);

        if ($driver->is_active) {
            $driver->is_active = false;
            $driver->save();
        }

        return response()->json(null, 204);
    }
}
