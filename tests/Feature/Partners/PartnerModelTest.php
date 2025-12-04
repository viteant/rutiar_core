<?php

namespace Partners;

use App\Models\Company;
use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_belongs_to_company(): void
    {
        $company = Company::factory()->create();

        $partner = Partner::factory()->create([
            'company_id' => $company->id,
        ]);

        $this->assertSame($company->id, $partner->company->id);
    }

    public function test_company_has_many_partners(): void
    {
        $company = Company::factory()->create();

        $partners = Partner::factory()->count(3)->create([
            'company_id' => $company->id,
        ]);

        $this->assertCount(3, $company->partners);
        $this->assertTrue(
            $company->partners->contains($partners->first())
        );
    }

    public function test_partner_casts_is_active_and_driver_quota(): void
    {
        $partner = Partner::factory()->create([
            'is_active' => 1,
            'driver_quota' => 10,
        ]);

        $this->assertIsBool($partner->is_active);
        $this->assertTrue($partner->is_active);

        $this->assertIsInt($partner->driver_quota);
        $this->assertSame(10, $partner->driver_quota);
    }

    public function test_partner_factory_creates_valid_partner(): void
    {
        $partner = Partner::factory()->create();

        $this->assertNotNull($partner->id);
        $this->assertNotNull($partner->company_id);
        $this->assertNotEmpty($partner->name);

        $this->assertTrue(
            Company::where('id', $partner->company_id)->exists()
        );
    }
}
