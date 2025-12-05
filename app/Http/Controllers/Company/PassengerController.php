<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Company\StorePassengerRequest;
use App\Http\Requests\Company\UpdatePassengerRequest;
use App\Models\Company;
use App\Models\Corporate;
use App\Models\Passenger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PassengerController extends BaseApiController
{
    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Passenger::class);

        $query = Passenger::query()
            ->with('corporate')
            ->active();

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

        if ($request->filled('corporate_id')) {
            $query->where('corporate_id', $request->integer('corporate_id'));
        }

        return response()->json(['data' => $query->get()]);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StorePassengerRequest $request): JsonResponse
    {
        $this->authorize('create', Passenger::class);

        $data = $request->validated();

        $companyId = $data['company_id'];

        // Seguridad extra: corporate pertenece a la misma company
        Corporate::query()
            ->where('company_id', $companyId)
            ->findOrFail($data['corporate_id']);

        $passenger = Passenger::create($data);

        return response()->json([
            'data' => $passenger->load('corporate'),
        ], 201);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Passenger $passenger): JsonResponse
    {
        $this->authorize('view', $passenger);

        return response()->json(['data' => $passenger->load('corporate')]);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdatePassengerRequest $request, Passenger $passenger): JsonResponse
    {
        $this->authorize('update', $passenger);

        $data = $request->validated();

        if (array_key_exists('corporate_id', $data)) {
            $companyId = $passenger->company_id;

            Corporate::query()
                ->where('company_id', $companyId)
                ->findOrFail($data['corporate_id']);
        }

        $passenger->fill($data)->save();

        return response()->json(['data' => $passenger->load('corporate')]);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Passenger $passenger): JsonResponse
    {
        $this->authorize('delete', $passenger);

        $passenger->deactivate();

        return response()->json(null, 204);
    }
}
