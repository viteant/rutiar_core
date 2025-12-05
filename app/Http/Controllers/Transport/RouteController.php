<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\BaseApiController;
use App\Http\Controllers\Transport\Concerns\HasTransportIndexFilters;
use App\Http\Requests\Transport\StoreRouteRequest;
use App\Http\Requests\Transport\UpdateRouteRequest;
use App\Models\Route;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class RouteController extends BaseApiController
{
    use HasTransportIndexFilters;

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Route::class);

        /** @var Builder $query */
        $query = Route::query()
            ->with('company')
            ->orderBy('name');

        $this->applyCompanyFilter($query, $request);

        $routes = $query->get();

        return response()->json([
            'data' => $routes,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreRouteRequest $request): JsonResponse
    {
        $this->authorize('create', Route::class);

        $data = $this->withCompanyId($request->validated());

        $route = Route::query()->create($data);

        $route->load('company');

        return response()->json([
            'data' => $route,
        ], 201);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Route $route): JsonResponse
    {
        $this->authorize('view', $route);

        $route->load('company');

        return response()->json([
            'data' => $route,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateRouteRequest $request, Route $route): JsonResponse
    {
        $this->authorize('update', $route);

        $data = $request->validated();

        // Only SUPERADMIN can move a route across tenants, and must respect withCompanyId rules.
        if ($this->isSuperAdmin() && array_key_exists('company_id', $data)) {
            $data = $this->withCompanyId($data);
        } else {
            // For normal users, never trust or allow company_id changes.
            unset($data['company_id']);
        }

        $route->fill($data);
        $route->save();

        $route->refresh()->load('company');

        return response()->json([
            'data' => $route,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Route $route): JsonResponse
    {
        $this->authorize('delete', $route);

        $route->deactivate();

        return $this->noContent();
    }
}
