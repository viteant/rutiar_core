<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition(): array
    {
        $company = Company::factory()->create();

        $partner = Partner::factory()->create([
            'company_id' => $company->id,
        ]);

        return [
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'user_id' => null,
            'full_name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'license_number' => strtoupper($this->faker->bothify('LIC#######')),
            'is_active' => true,
        ];
    }
}
