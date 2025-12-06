<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Transport\StoreRunEventRequest;
use App\Models\Run;
use App\Models\RunEvent;
use Illuminate\Http\JsonResponse;

class RunEventController extends BaseApiController
{
    public function index(Run $run): JsonResponse
    {
        $this->authorize('view', $run);

        // Tenant ya se asegura por policy (sameTenant)
        $events = $run->events()
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        return response()->json($events);
    }

    public function store(StoreRunEventRequest $request, Run $run): JsonResponse
    {
        // Crear eventos implica modificar la ejecución -> usamos update
        $this->authorize('update', $run);

        $data = $request->validated();

        $data['run_id'] = $run->id;

        // Forzamos company_id coherente con el run
        $data = $this->withCompanyId($data, $run->company_id);

        if (! isset($data['source'])) {
            // Por defecto asumimos app del conductor
            $data['source'] = 'driver_app';
        }

        $event = RunEvent::create($data);

        return response()->json($event, 201);
    }
}
