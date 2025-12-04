<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Corporate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CorporateFactory extends Factory
{
    protected $model = Corporate::class;

    public function definition(): array
    {
        $company = Company::factory()->create();

        return [
            'company_id'    => $company->id,
            'name'          => $this->faker->company(),
            'tax_id'        => $this->faker->numerify('############'),
            'contact_name'  => $this->faker->name(),
            'contact_email' => $this->faker->safeEmail(),
            'is_active'     => true,
        ];
    }
}
