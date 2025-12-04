<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Corporate;
use App\Models\Passenger;
use Illuminate\Database\Eloquent\Factories\Factory;

class PassengerFactory extends Factory
{
    protected $model = Passenger::class;

    public function definition(): array
    {
        $company = Company::factory()->create();
        $corporate = Corporate::factory()->create([
            'company_id' => $company->id,
        ]);

        return [
            'company_id'    => $company->id,
            'corporate_id'  => $corporate->id,
            'full_name'     => $this->faker->name(),
            'employee_code' => $this->faker->bothify('EMP-####'),
            'document_id'   => $this->faker->numerify('##########'),
            'home_address'  => $this->faker->address(),
            'home_lat'      => $this->faker->latitude(-4, 2),
            'home_lng'      => $this->faker->longitude(-82, -75),
            'shift_code'    => $this->faker->bothify('SHIFT-##'),
            'is_active'     => true,
        ];
    }
}
