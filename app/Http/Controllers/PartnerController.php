<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorize('viewAny', Partner::class);

        $query = Partner::query()
            ->orderBy('name');

        if (!$user->isSuperAdmin()) {
            $query->where('company_id', $user->company_id);
        } elseif ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        $partners = $query->get([
            'id',
            'company_id',
            'name',
            'code',
            'tax_id',
            'is_active',
            'driver_quota',
            'created_at',
            'updated_at',
        ]);

        return response()->json([
            'data' => $partners,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorize('create', Partner::class);

        // Reglas base para todos
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
            'driver_quota' => ['nullable', 'integer', 'min:0'],
        ];

        // Solo SUPERADMIN puede indicar company_id manualmente
        if ($user->isSuperAdmin()) {
            $rules['company_id'] = ['required', 'integer', 'exists:companies,id'];
        }

        $validated = $request->validate($rules);

        // Resolver company_id según el tipo de usuario
        if ($user->isSuperAdmin()) {
            $companyId = $validated['company_id'];
        } else {
            if (!$user->company_id) {
                abort(403);
            }

            $companyId = $user->company_id;
        }

        // En este punto company_id NO puede ser null
        /** @var Company|null $company */
        $company = Company::find($companyId);

        if (!$company) {
            abort(422, 'Invalid company_id.');
        }

        $partner = Partner::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'code' => $validated['code'],
            'tax_id' => $validated['tax_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'driver_quota' => $validated['driver_quota'] ?? null,
        ]);

        return response()->json([
            'data' => $partner->only([
                'id',
                'company_id',
                'name',
                'code',
                'tax_id',
                'is_active',
                'driver_quota',
                'created_at',
                'updated_at',
            ]),
        ], 201);
    }

    public function show(Partner $partner, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorize('view', $partner);

        return response()->json([
            'data' => $partner->only([
                'id',
                'company_id',
                'name',
                'code',
                'tax_id',
                'is_active',
                'driver_quota',
                'created_at',
                'updated_at',
            ]),
        ]);
    }

    public function update(Partner $partner, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorize('update', $partner);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'code' => ['sometimes', 'string', 'max:50'],
            'tax_id' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
            'driver_quota' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);

        $partner->fill($validated);
        $partner->save();

        return response()->json([
            'data' => $partner->only([
                'id',
                'company_id',
                'name',
                'code',
                'tax_id',
                'is_active',
                'driver_quota',
                'created_at',
                'updated_at',
            ]),
        ]);
    }

    public function destroy(Partner $partner, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorize('delete', $partner);

        $partner->delete();

        return response()->json([], 204);
    }
}
