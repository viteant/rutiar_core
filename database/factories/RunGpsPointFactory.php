<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Route as TransportRoute;
use App\Models\RouteDefinition;
use App\Models\Run;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\RunGpsPoint>
 */
class RunGpsPointFactory extends Factory
{
    public function definition(): array
    {
        $company = Company::factory()->create();
        $route = TransportRoute::factory()->for($company)->create();
        $definition = RouteDefinition::factory()
            ->for($company)
            ->for($route, 'route')
            ->create();

        $run = Run::factory()
            ->for($company)
            ->for($definition, 'routeDefinition')
            ->create();

        return [
            'company_id'  => $company->id,
            'run_id'      => $run->id,
            'recorded_at' => $this->faker->dateTime(),
            'lat'         => $this->faker->latitude(-2.2, -2.0),
            'lng'         => $this->faker->longitude(-79.0, -79.9),
            'speed_kmh'   => $this->faker->optional()->randomFloat(2, 0, 120),
            'is_active'   => true,
        ];
    }
}
