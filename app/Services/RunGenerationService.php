<?php

namespace App\Services;

use App\Enums\RunStatus;
use App\Models\Driver;
use App\Models\Partner;
use App\Models\RouteDefinition;
use App\Models\RouteDefinitionPassenger;
use App\Models\Run;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RunGenerationService
{
    public function generateFromDefinition(
        RouteDefinition $definition,
        string|\DateTimeInterface $serviceDate,
        ?Driver $driver,
        ?Vehicle $vehicle,
        ?float $fareAmount,
        User $createdBy
    ): Run
    {
        $serviceDate = $serviceDate instanceof \DateTimeInterface
            ? $serviceDate
            : Carbon::parse($serviceDate);

        return DB::transaction(function () use (
            $definition,
            $serviceDate,
            $driver,
            $vehicle,
            $fareAmount,
            $createdBy
        ): Run {
            $partner = $definition->partner;

            if (! $partner instanceof Partner) {
                throw new \RuntimeException('RouteDefinition must have a partner associated.');
            }

            $manifestSnapshot = $this->buildManifestSnapshot($definition, $serviceDate);

            $run = new Run();

            $run->company_id = $definition->company_id;
            $run->route_definition_id = $definition->id;
            $run->service_date = $serviceDate->toDateString();
            $run->status = RunStatus::PLANNED;

            $run->partner_id = $partner->id;
            $run->driver_id = $driver?->id ?? $definition->driver_id;
            $run->vehicle_id = $vehicle?->id;

            $run->fare_amount = $fareAmount ?? $definition->base_fare_amount;
            $run->route_billing_code_snap = $definition->billing_code;

            $run->manifest_snapshot = $manifestSnapshot;

            $run->created_by_user_id = $createdBy->id;

            $run->save();

            return $run;
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildManifestSnapshot(RouteDefinition $definition, \DateTimeInterface $serviceDate): array
    {
        $items = RouteDefinitionPassenger::query()
            ->where('route_definition_id', $definition->id)
            ->active()
            ->with('passenger')
            ->orderBy('pickup_order')
            ->get();

        $snapshotItems = $items->map(function (RouteDefinitionPassenger $item): array {
            $passenger = $item->passenger;

            $plannedPickupTime = $item->planned_pickup_time;
            $plannedPickupTimeFormatted = null;

            if ($plannedPickupTime instanceof \DateTimeInterface) {
                $plannedPickupTimeFormatted = $plannedPickupTime->format('H:i');
            } elseif (is_string($plannedPickupTime) && $plannedPickupTime !== '') {
                $plannedPickupTimeFormatted = Carbon::parse($plannedPickupTime)->format('H:i');
            }

            return [
                'route_definition_passenger_id' => $item->id,
                'passenger_id'                  => $item->passenger_id,
                'corporate_id'                  => $passenger?->corporate_id,
                'full_name'                     => $passenger?->full_name,
                'employee_code'                 => $passenger?->employee_code,
                'pickup_order'                  => $item->pickup_order,
                'planned_pickup_time'           => $plannedPickupTimeFormatted,
                'pickup_address'                => $item->pickup_address ?? $passenger?->home_address,
                'pickup_lat'                    => $item->pickup_lat ?? $passenger?->home_lat,
                'pickup_lng'                    => $item->pickup_lng ?? $passenger?->home_lng,
                'home_address'                  => $passenger?->home_address,
                'home_lat'                      => $passenger?->home_lat,
                'home_lng'                      => $passenger?->home_lng,
                'shift_code'                    => $passenger?->shift_code,
            ];
        })->all();

        return [
            'route_definition_id' => $definition->id,
            'service_date'        => $serviceDate->format('Y-m-d'),
            'items'               => $snapshotItems,
        ];
    }
}
