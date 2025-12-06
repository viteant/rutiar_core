<?php

namespace Database\Factories;

use App\Enums\RunEventSource;
use App\Enums\RunEventType;
use App\Enums\RunIncidentType;
use App\Models\Company;
use App\Models\Passenger;
use App\Models\Route as TransportRoute;
use App\Models\RouteDefinition;
use App\Models\RouteDefinitionPassenger;
use App\Models\Run;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\RunEvent>
 */
class RunEventFactory extends Factory
{
    public function definition(): array
    {
        $company = Company::factory()->create();
        $route = TransportRoute::factory()->for($company)->create();
        $definition = RouteDefinition::factory()
            ->for($company)
            ->for($route, 'route')
            ->create();

        $passenger = Passenger::factory()->for($company)->create();

        $definitionPassenger = RouteDefinitionPassenger::factory()
            ->for($company)
            ->for($definition, 'routeDefinition')
            ->for($passenger, 'passenger')
            ->create();

        $run = Run::factory()
            ->for($company)
            ->for($definition, 'routeDefinition')
            ->create();

        return [
            'company_id' => $company->id,
            'run_id' => $run->id,
            'route_definition_passenger_id' => $definitionPassenger->id,
            'passenger_id' => $passenger->id,
            'event_type' => RunEventType::BOARDING,
            'incident_type' => null,
            'source' => RunEventSource::DRIVER_APP,
            'occurred_at' => $this->faker->dateTime(),
            'lat' => $this->faker->optional()->latitude(-2.2, -2.0),
            'lng' => $this->faker->optional()->longitude(-79.0, -79.9),
            'wait_seconds' => $this->faker->optional()->numberBetween(0, 600),
            'notes' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function incident(RunIncidentType $type = RunIncidentType::OTHER): self
    {
        return $this->state(function (array $attributes) use ($type): array {
            return [
                'event_type' => RunEventType::INCIDENT,
                'incident_type' => $type,
            ];
        });
    }
}
