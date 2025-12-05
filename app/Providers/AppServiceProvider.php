<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Corporate;
use App\Models\Driver;
use App\Models\Partner;
use App\Models\Passenger;
use App\Models\Route;
use App\Models\RouteDefinition;
use App\Models\RouteDefinitionPassenger;
use App\Models\Run;
use App\Models\Vehicle;
use App\Policies\CompanyPermissionPolicy;
use App\Policies\CorporatePolicy;
use App\Policies\DriverPolicy;
use App\Policies\PartnerPolicy;
use App\Policies\PassengerPolicy;
use App\Policies\RouteDefinitionPassengerPolicy;
use App\Policies\RouteDefinitionPolicy;
use App\Policies\RoutePolicy;
use App\Policies\RunPolicy;
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
        Gate::policy(Corporate::class, CorporatePolicy::class);
        Gate::policy(Passenger::class, PassengerPolicy::class);
        Gate::policy(Route::class, RoutePolicy::class);
        Gate::policy(RouteDefinition::class, RouteDefinitionPolicy::class);
        Gate::policy(RouteDefinitionPassenger::class, RouteDefinitionPassengerPolicy::class);
        Gate::policy(Run::class, RunPolicy::class);
    }
}
