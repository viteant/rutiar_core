<?php

namespace Tests\Feature\Drivers;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_belongs_to_company_and_partner(): void
    {
        $company = Company::factory()->create();
        $partner = Partner::factory()->create([
            'company_id' => $company->id,
        ]);

        $driver = Driver::factory()->create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
        ]);

        $this->assertSame($company->id, $driver->company->id);
        $this->assertSame($partner->id, $driver->partner->id);
    }

    public function test_driver_may_belong_to_user(): void
    {
        $company = Company::factory()->create();
        $partner = Partner::factory()->create([
            'company_id' => $company->id,
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $driver = Driver::factory()->create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'user_id' => $user->id,
        ]);

        $this->assertSame($user->id, $driver->user->id);
    }

    public function test_company_has_many_drivers(): void
    {
        $company = Company::factory()->create();
        $partner = Partner::factory()->create([
            'company_id' => $company->id,
        ]);

        $drivers = Driver::factory()->count(3)->create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
        ]);

        $this->assertCount(3, $company->drivers);
        $this->assertTrue($company->drivers->contains($drivers->first()));
    }

    public function test_partner_has_many_drivers(): void
    {
        $company = Company::factory()->create();
        $partner = Partner::factory()->create([
            'company_id' => $company->id,
        ]);

        $drivers = Driver::factory()->count(2)->create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
        ]);

        $this->assertCount(2, $partner->drivers);
        $this->assertTrue($partner->drivers->contains($drivers->first()));
    }

    public function test_is_active_casts_to_boolean(): void
    {
        $driver = Driver::factory()->create([
            'is_active' => 1,
        ]);

        $this->assertIsBool($driver->is_active);
        $this->assertTrue($driver->is_active);
    }
}
