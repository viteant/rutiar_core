<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Partner;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        $company = Company::factory()->create();
        $partner = Partner::factory()->create([
            'company_id' => $company->id,
        ]);

        return [
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'plate'      => strtoupper($this->faker->bothify('ABC-####')),
            'model'      => $this->faker->words(2, true),
            'capacity'   => $this->faker->numberBetween(4, 40),
            'is_active'  => true,
        ];
    }
}
