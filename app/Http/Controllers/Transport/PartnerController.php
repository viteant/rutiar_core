<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Transport\StorePartnerRequest;
use App\Http\Requests\Transport\UpdatePartnerRequest;
use App\Models\Company;
use App\Models\Partner;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerController extends BaseApiController
{
    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Partner::class);

        $query = Partner::query()
            ->orderBy('name');

        if ($this->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->integer('company_id'));
            }
        } else {
            $company = $this->tenant();

            if (! $company instanceof Company) {
                abort(403);
            }

            $query->where('company_id', $company->id);
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

    /**
     * @throws AuthorizationException
     */
    public function store(StorePartnerRequest $request): JsonResponse
    {
        $this->authorize('create', Partner::class);

        $data = $this->withCompanyId($request->validated());

        $partner = Partner::create($data);

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

    /**
     * @throws AuthorizationException
     */
    public function show(Partner $partner): JsonResponse
    {
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

    /**
     * @throws AuthorizationException
     */
    public function update(Partner $partner, UpdatePartnerRequest $request): JsonResponse
    {
        $this->authorize('update', $partner);

        $partner->fill($request->validated());
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

    /**
     * @throws AuthorizationException
     */
    public function destroy(Partner $partner): JsonResponse
    {
        $this->authorize('delete', $partner);

        // Si usas trait Activatable en Partner:
        // $partner->deactivate();
        if ($partner->is_active) {
            $partner->is_active = false;
            $partner->save();
        }

        return response()->json(null, 204);
    }
}
