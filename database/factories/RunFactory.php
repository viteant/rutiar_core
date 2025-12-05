<?php

namespace Database\Factories;

use App\Enums\RunStatus;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Route as TransportRoute;
use App\Models\RouteDefinition;
use App\Models\Run;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Run>
 */
class RunFactory extends Factory
{
    protected $model = Run::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::factory()->create();

        $route = TransportRoute::factory()->for($company)->create();
        $partner = Partner::factory()->for($company)->create();
        $driver = null;
        $vehicle = null;

        $definition = RouteDefinition::factory()
            ->for($company)
            ->for($route, 'route')
            ->for($partner, 'partner')
            ->create();

        return [
            'company_id' => $company->id,
            'route_definition_id' => $definition->id,
            'service_date' => $this->faker->date(),

            'status' => RunStatus::PLANNED,

            'partner_id' => $partner->id,
            'driver_id' => $driver?->id,
            'vehicle_id' => $vehicle?->id,

            'fare_amount' => $this->faker->optional()->randomFloat(2, 3, 50),
            'route_billing_code_snap' => $this->faker->optional()->bothify('RUN-####'),
            'manifest_snapshot' => null,

            'created_by_user_id' => User::factory()->for($company)->create()->id,
        ];
    }
}
