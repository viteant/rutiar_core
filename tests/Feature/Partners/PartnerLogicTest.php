<?php

namespace Tests\Feature\Partners;

use App\Models\Company;
use App\Models\CompanyConfig;
use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_uses_own_driver_quota_if_set(): void
    {
        $company = Company::factory()->create();

        CompanyConfig::create([
            'company_id' => $company->id,
            'planning_cutoff_time' => '18:00:00',
            'default_waiting_minutes' => 5,
            'max_drivers_per_partner' => 0,
            'allow_driver_reorder' => true,
            'driver_quota_default' => 20,
            'settings' => [],
        ]);

        $partner = Partner::factory()->create([
            'company_id' => $company->id,
            'driver_quota' => 10,
        ]);

        $this->assertSame(10, $partner->effectiveDriverQuota());
    }

    public function test_partner_uses_company_default_quota_if_own_quota_is_null(): void
    {
        $company = Company::factory()->create();

        CompanyConfig::create([
            'company_id' => $company->id,
            'planning_cutoff_time' => '18:00:00',
            'default_waiting_minutes' => 5,
            'max_drivers_per_partner' => 0,
            'allow_driver_reorder' => true,
            'driver_quota_default' => 25,
            'settings' => [],
        ]);

        $partner = Partner::factory()->create([
            'company_id' => $company->id,
            'driver_quota' => null,
        ]);

        $this->assertSame(25, $partner->effectiveDriverQuota());
    }

    public function test_partner_effective_quota_is_null_if_no_company_config(): void
    {
        $company = Company::factory()->create();

        $partner = Partner::factory()->create([
            'company_id' => $company->id,
            'driver_quota' => null,
        ]);

        $this->assertNull($partner->effectiveDriverQuota());
    }
}
