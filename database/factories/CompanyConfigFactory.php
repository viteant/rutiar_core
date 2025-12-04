<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyConfig>
 */
class CompanyConfigFactory extends Factory
{
    protected $model = CompanyConfig::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'planning_cutoff_time' => '18:00:00',
            'default_waiting_minutes' => 5,
            'max_drivers_per_partner' => 0,
            'allow_driver_reorder' => true,
            'settings' => [],
        ];
    }
}
