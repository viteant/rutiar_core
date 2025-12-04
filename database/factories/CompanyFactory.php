<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'code' => 'COMP-'.$this->faker->unique()->numerify('####'),
            'country' => 'EC',
            'timezone' => 'America/Guayaquil',
            'is_active' => true,
        ];
    }
}
