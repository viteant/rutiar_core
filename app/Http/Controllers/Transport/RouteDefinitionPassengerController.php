<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\BaseApiController;
use App\Http\Controllers\Transport\Concerns\HasTransportIndexFilters;
use App\Http\Requests\Transport\StoreRouteDefinitionPassengerRequest;
use App\Http\Requests\Transport\UpdateRouteDefinitionPassengerRequest;
use App\Models\Passenger;
use App\Models\RouteDefinition;
use App\Models\RouteDefinitionPassenger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouteDefinitionPassengerController extends BaseApiController
{
    use HasTransportIndexFilters;

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RouteDefinitionPassenger::class);

        /** @var Builder $query */
        $query = RouteDefinitionPassenger::query()
            ->with(['routeDefinition', 'passenger'])
            ->orderBy('pickup_order');

        $this->applyCompanyFilter($query, $request);

        if ($request->filled('route_definition_id')) {
            $query->where('route_definition_id', (int) $request->input('route_definition_id'));
        }

        if ($request->filled('passenger_id')) {
            $query->where('passenger_id', (int) $request->input('passenger_id'));
        }

        $items = $query->get();

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreRouteDefinitionPassengerRequest $request): JsonResponse
    {
        $this->authorize('create', RouteDefinitionPassenger::class);

        $data = $this->withCompanyId($request->validated());

        $this->assertPassengerMatchesRouteDefinition($data);

        $item = RouteDefinitionPassenger::query()->create($data);

        $item->load(['routeDefinition', 'passenger']);

        return response()->json([
            'data' => $item,
        ], 201);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(RouteDefinitionPassenger $routeDefinitionPassenger): JsonResponse
    {
        $this->authorize('view', $routeDefinitionPassenger);

        $routeDefinitionPassenger->load(['routeDefinition', 'passenger']);

        return response()->json([
            'data' => $routeDefinitionPassenger,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(
        UpdateRouteDefinitionPassengerRequest $request,
        RouteDefinitionPassenger $routeDefinitionPassenger
    ): JsonResponse {
        $this->authorize('update', $routeDefinitionPassenger);

        $data = $request->validated();

        if ($this->isSuperAdmin() && array_key_exists('company_id', $data)) {
            $data = $this->withCompanyId($data);
        } else {
            unset($data['company_id']);
        }

        // If route_definition_id or passenger_id are changing, re-check consistency
        if (array_key_exists('route_definition_id', $data) || array_key_exists('passenger_id', $data)) {
            $merged = array_merge($routeDefinitionPassenger->toArray(), $data);
            $this->assertPassengerMatchesRouteDefinition($merged);
        }

        $routeDefinitionPassenger->fill($data);
        $routeDefinitionPassenger->save();

        $routeDefinitionPassenger->refresh()->load(['routeDefinition', 'passenger']);

        return response()->json([
            'data' => $routeDefinitionPassenger,
        ]);
    }

    public function destroy(RouteDefinitionPassenger $routeDefinitionPassenger): JsonResponse
    {
        $this->authorize('delete', $routeDefinitionPassenger);

        $routeDefinitionPassenger->deactivate();

        return $this->noContent();
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function assertPassengerMatchesRouteDefinition(array $data): void
    {
        if (! isset($data['route_definition_id'], $data['passenger_id'])) {
            return;
        }

        /** @var RouteDefinition $definition */
        $definition = RouteDefinition::query()
            ->select(['id', 'company_id', 'corporate_id'])
            ->findOrFail((int) $data['route_definition_id']);

        /** @var Passenger $passenger */
        $passenger = Passenger::query()
            ->select(['id', 'company_id', 'corporate_id'])
            ->findOrFail((int) $data['passenger_id']);

        if ((int) $definition->company_id !== (int) $passenger->company_id) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Passenger does not belong to the same company as the route definition.',
                    'errors' => [
                        'passenger_id' => ['Passenger company mismatch with route definition.'],
                    ],
                ], 422)
            );
        }

        if ((int) $definition->corporate_id !== (int) $passenger->corporate_id) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Passenger does not belong to the same corporate as the route definition.',
                    'errors' => [
                        'passenger_id' => ['Passenger corporate mismatch with route definition.'],
                    ],
                ], 422)
            );
        }
    }
}
