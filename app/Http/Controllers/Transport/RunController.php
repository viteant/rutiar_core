<?php

namespace App\Http\Controllers\Transport;

use App\Enums\RunStatus;
use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Transport\StoreRunRequest;
use App\Http\Requests\Transport\UpdateRunRequest;
use App\Models\Run;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RunController extends BaseApiController
{
    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Run::class);

        $query = Run::query();

        if (! $this->isSuperAdmin()) {
            $tenant = $this->tenantOrFail();
            $query->where('company_id', $tenant->id);
        } else {
            $companyId = $request->query('company_id');

            if ($companyId !== null) {
                $query->where('company_id', (int) $companyId);
            }
        }

        if ($request->filled('route_definition_id')) {
            $query->where('route_definition_id', (int) $request->query('route_definition_id'));
        }

        if ($request->filled('service_date')) {
            $query->whereDate('service_date', $request->query('service_date'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $runs = $query
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->get();

        return response()->json($runs);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Run $run): JsonResponse
    {
        $this->authorize('view', $run);

        return response()->json($run);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreRunRequest $request): JsonResponse
    {
        $this->authorize('create', Run::class);

        $data = $this->withCompanyId($request->validated());

        if (! isset($data['created_by_user_id'])) {
            $data['created_by_user_id'] = $this->user()->id;
        }

        $run = Run::create($data);

        return response()->json($run, 201);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateRunRequest $request, Run $run): JsonResponse
    {
        $this->authorize('update', $run);

        $data = $this->withCompanyId($request->validated(), $run->company_id);

        $run->fill($data);
        $run->save();

        return response()->json($run);
    }


    /**
     * @throws AuthorizationException
     */
    public function approve(Run $run): JsonResponse
    {
        $this->authorize('approve', $run);

        $run->status = RunStatus::APPROVED;
        $run->save();

        return response()->json($run);
    }

    /**
     * @throws AuthorizationException
     */
    public function cancel(Run $run): JsonResponse
    {
        $this->authorize('cancel', $run);

        $run->status = RunStatus::CANCELED;
        $run->save();

        return response()->json($run);
    }

    /**
     * @throws AuthorizationException
     */
    public function forceClose(Run $run): JsonResponse
    {
        $this->authorize('forceClose', $run);

        $run->status = RunStatus::FORCE_CLOSED;
        $run->save();

        return response()->json($run);
    }
}
