<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Partner;
use App\Models\Vehicle;
use App\Policies\CompanyPermissionPolicy;
use App\Policies\DriverPolicy;
use App\Policies\PartnerPolicy;
use App\Policies\VehiclePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Company::class, CompanyPermissionPolicy::class);
        Gate::policy(Partner::class, PartnerPolicy::class);
        Gate::policy(Driver::class, DriverPolicy::class);
        Gate::policy(Vehicle::class, VehiclePolicy::class);
    }
}
