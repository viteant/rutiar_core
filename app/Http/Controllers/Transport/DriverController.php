<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\BaseApiController;
use App\Http\Controllers\Transport\Concerns\HasTransportIndexFilters;
use App\Http\Requests\Transport\StoreDriverRequest;
use App\Http\Requests\Transport\UpdateDriverRequest;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Partner;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends BaseApiController
{
    use HasTransportIndexFilters;

    /**
     * Display a listing of the drivers.
     *
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Driver::class);

        $query = Driver::query()
            ->with(['company', 'partner', 'user'])
            ->active();

        $query = $this->applyCompanyFilter($query, $request);
        $query = $this->applyPartnerFilter($query, $request);

        return response()->json(['data' => $query->get()]);
    }

    /**
     * Store a newly created driver in storage.
     *
     * @throws AuthorizationException
     */
    public function store(StoreDriverRequest $request): JsonResponse
    {
        $this->authorize('create', Driver::class);

        $data = $this->withCompanyId($request->validated());
        $companyId = $data['company_id'];

        /** @var Partner $partner */
        $partner = Partner::query()
            ->where('company_id', $companyId)
            ->findOrFail($data['partner_id']);

        $this->assertPartnerHasDriverQuota($partner);

        $driver = Driver::create($data);

        return response()->json([
            'data' => $driver->load(['company', 'partner', 'user']),
        ], 201);
    }

    /**
     * Display the specified driver.
     *
     * @throws AuthorizationException
     */
    public function show(Driver $driver): JsonResponse
    {
        $this->authorize('view', $driver);

        $driver->load(['company', 'partner', 'user']);

        return response()->json(['data' => $driver]);
    }

    /**
     * Update the specified driver in storage.
     *
     * @throws AuthorizationException
     */
    public function update(UpdateDriverRequest $request, Driver $driver): JsonResponse
    {
        $this->authorize('update', $driver);

        $data = $request->validated();

        if (
            array_key_exists('partner_id', $data)
            && $data['partner_id'] !== $driver->partner_id
        ) {
            /** @var Partner $partner */
            $partner = Partner::query()
                ->where('company_id', $driver->company_id)
                ->findOrFail($data['partner_id']);

            $this->assertPartnerHasDriverQuota($partner);
        }

        $driver->fill($data);
        $driver->save();

        return response()->json([
            'data' => $driver->load(['company', 'partner', 'user']),
        ]);
    }

    /**
     * Deactivate the specified driver (soft delete).
     *
     * @throws AuthorizationException
     */
    public function destroy(Driver $driver): JsonResponse
    {
        $this->authorize('delete', $driver);

        $driver->deactivate();

        return response()->json(null, 204);
    }

    /**
     * Ensure the given partner has available driver quota or throw 422.
     */
    protected function assertPartnerHasDriverQuota(Partner $partner): void
    {
        $quota = $partner->effectiveDriverQuota();

        if ($quota === null) {
            return;
        }

        $currentDriverCount = $partner->drivers()->count();

        if ($currentDriverCount >= $quota) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Driver quota exceeded for this partner.',
                    'errors' => [
                        'partner_id' => ['Driver quota exceeded for this partner.'],
                    ],
                ], 422)
            );
        }
    }
}
