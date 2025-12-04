<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->company(),
            'code' => strtoupper($this->faker->bothify('P####')),
            'tax_id' => $this->faker->numerify('############'),
            'driver_quota' => $this->faker->numberBetween(0, 50),
            'is_active' => true,
        ];
    }
}
