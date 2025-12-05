<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\BaseApiController;
use App\Http\Controllers\Transport\Concerns\HasTransportIndexFilters;
use App\Http\Requests\Transport\StoreRouteDefinitionRequest;
use App\Http\Requests\Transport\UpdateRouteDefinitionRequest;
use App\Models\RouteDefinition;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouteDefinitionController extends BaseApiController
{
    use HasTransportIndexFilters;

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RouteDefinition::class);

        /** @var Builder $query */
        $query = RouteDefinition::query()
            ->with(['company', 'route', 'corporate', 'partner', 'driver'])
            ->orderBy('id', 'desc');

        // Applies tenant filter (and optional company_id for SUPERADMIN, partner_id filters, etc.)
        $this->applyCompanyFilter($query, $request);

        if ($request->filled('route_id')) {
            $query->where('route_id', (int) $request->input('route_id'));
        }

        if ($request->filled('corporate_id')) {
            $query->where('corporate_id', (int) $request->input('corporate_id'));
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->input('direction'));
        }

        $definitions = $query->get();

        return response()->json([
            'data' => $definitions,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreRouteDefinitionRequest $request): JsonResponse
    {
        $this->authorize('create', RouteDefinition::class);

        $data = $this->withCompanyId($request->validated());

        // Versioning strategy can be improved later; for now we trust provided version or default to 1.
        if (! array_key_exists('version', $data)) {
            $data['version'] = 1;
        }

        $definition = RouteDefinition::query()->create($data);

        $definition->load(['company', 'route', 'corporate', 'partner', 'driver']);

        return response()->json([
            'data' => $definition,
        ], 201);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(RouteDefinition $routeDefinition): JsonResponse
    {
        $this->authorize('view', $routeDefinition);

        $routeDefinition->load(['company', 'route', 'corporate', 'partner', 'driver', 'passengers']);

        return response()->json([
            'data' => $routeDefinition,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateRouteDefinitionRequest $request, RouteDefinition $routeDefinition): JsonResponse
    {
        $this->authorize('update', $routeDefinition);

        $data = $request->validated();

        // Only SUPERADMIN can change company_id explicitly, and must still pass through withCompanyId().
        if ($this->isSuperAdmin() && array_key_exists('company_id', $data)) {
            $data = $this->withCompanyId($data);
        } else {
            unset($data['company_id']);
        }

        $routeDefinition->fill($data);
        $routeDefinition->save();

        $routeDefinition->refresh()->load(['company', 'route', 'corporate', 'partner', 'driver', 'passengers']);

        return response()->json([
            'data' => $routeDefinition,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(RouteDefinition $routeDefinition): JsonResponse
    {
        $this->authorize('delete', $routeDefinition);

        $routeDefinition->deactivate();

        return $this->noContent();
    }
}
