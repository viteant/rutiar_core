<?php

namespace Database\Factories;

use App\Enums\RunDirection;
use App\Models\Company;
use App\Models\Corporate;
use App\Models\Partner;
use App\Models\Route as TransportRoute;
use App\Models\RouteDefinition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RouteDefinition>
 */
class RouteDefinitionFactory extends Factory
{
    protected $model = RouteDefinition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $direction = $this->faker->randomElement(RunDirection::cases());

        return [
            'company_id' => Company::factory(),
            'route_id' => TransportRoute::factory(),
            'corporate_id' => Corporate::factory(),
            'partner_id' => Partner::factory(),
            'driver_id' => null,

            'version' => 1,
            'is_active' => true,
            'previous_definition_id' => null,

            'direction' => $direction, // cast enum en el modelo
            'reference_time' => $this->faker->optional()->time('H:i:s'),

            'billing_code' => $this->faker->optional()->bothify('ROUTE-####'),
            'base_fare_amount' => $this->faker->optional()->randomFloat(2, 3, 25),

            'created_by_user_id' => User::factory(),
        ];
    }
}
