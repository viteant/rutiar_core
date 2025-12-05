<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Passenger;
use App\Models\RouteDefinition;
use App\Models\RouteDefinitionPassenger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RouteDefinitionPassenger>
 */
class RouteDefinitionPassengerFactory extends Factory
{
    protected $model = RouteDefinitionPassenger::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'route_definition_id' => RouteDefinition::factory(),
            'passenger_id' => Passenger::factory(),

            'pickup_order' => $this->faker->numberBetween(1, 50),
            'planned_pickup_time' => $this->faker->optional()->time('H:i:s'),
            'pickup_address' => $this->faker->optional()->streetAddress(),

            'pickup_lat' => $this->faker->optional()->latitude(-2.3, -2.1),
            'pickup_lng' => $this->faker->optional()->longitude(-80.1, -79.7),

            'is_active' => true,
        ];
    }
}
