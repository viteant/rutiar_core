<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\BaseApiController;
use App\Http\Controllers\Transport\Concerns\HasTransportIndexFilters;
use App\Http\Requests\Transport\StoreVehicleRequest;
use App\Http\Requests\Transport\UpdateVehicleRequest;
use App\Models\Company;
use App\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends BaseApiController
{
    use HasTransportIndexFilters;

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Vehicle::class);

        $query = Vehicle::query()
            ->with(['company', 'partner'])
            ->active();

        $query = $this->applyCompanyFilter($query, $request);
        $query = $this->applyPartnerFilter($query, $request);

        return response()->json(['data' => $query->get()]);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $this->authorize('create', Vehicle::class);

        $data = $this->withCompanyId($request->validated());

        $vehicle = Vehicle::create($data);

        return response()->json([
            'data' => $vehicle->load(['company', 'partner']),
        ], 201);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('view', $vehicle);

        $vehicle->load(['company', 'partner']);

        return response()->json(['data' => $vehicle]);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('update', $vehicle);

        $vehicle->fill($request->validated());
        $vehicle->save();

        return response()->json([
            'data' => $vehicle->load(['company', 'partner']),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('delete', $vehicle);

        $vehicle->deactivate();

        return response()->json(null, 204);
    }
}
