<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Company\StoreCorporateRequest;
use App\Http\Requests\Company\UpdateCorporateRequest;
use App\Models\Company;
use App\Models\Corporate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CorporateController extends BaseApiController
{
    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Corporate::class);

        $query = Corporate::query()->active();

        if ($this->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', $request->integer('company_id'));
            }
        } else {
            $company = $this->tenant();

            if (! $company instanceof Company) {
                abort(403);
            }

            $query->forCompany($company);
        }

        $corporates = $query->get();

        return response()->json(['data' => $corporates]);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreCorporateRequest $request): JsonResponse
    {
        $this->authorize('create', Corporate::class);

        $data = $this->withCompanyId($request->validated());

        $corporate = Corporate::create($data);

        return response()->json(['data' => $corporate], 201);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Corporate $corporate): JsonResponse
    {
        $this->authorize('view', $corporate);

        return response()->json(['data' => $corporate]);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateCorporateRequest $request, Corporate $corporate): JsonResponse
    {
        $this->authorize('update', $corporate);

        $corporate->fill($request->validated());
        $corporate->save();

        return response()->json(['data' => $corporate]);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Corporate $corporate): JsonResponse
    {
        $this->authorize('delete', $corporate);

        $corporate->deactivate();

        return response()->json(null, 204);
    }
}
