<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\BaseApiController;
use App\Http\Requests\Transport\StoreRunGpsPointRequest;
use App\Models\Run;
use App\Models\RunGpsPoint;
use Illuminate\Http\JsonResponse;

class RunGpsPointController extends BaseApiController
{
    public function index(Run $run): JsonResponse
    {
        $this->authorize('view', $run);

        $points = $run->gpsPoints()
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();

        return response()->json($points);
    }

    public function store(StoreRunGpsPointRequest $request, Run $run): JsonResponse
    {
        $this->authorize('update', $run);

        $data = $request->validated();

        $data['run_id'] = $run->id;

        // company_id siempre coherente con el run, ignorando cualquier cosa del cliente
        $data = $this->withCompanyId($data, $run->company_id);

        $point = RunGpsPoint::create($data);

        return response()->json($point, 201);
    }
}
