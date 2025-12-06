<?php

namespace Tests\Feature\Runs;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Corporate;
use App\Models\Partner;
use App\Models\Passenger;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\Route as TransportRoute;
use App\Models\RouteDefinition;
use App\Models\RouteDefinitionPassenger;
use App\Models\Run;
use App\Models\RunEvent;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RunEventCrudTest extends TestCase
{
    use RefreshDatabase;

    private const VIEW_RUNS_PERMISSION = 'view_runs';

    protected function giveRolePermission(Company $company, string $role, string $permissionName): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => $permissionName],
            ['description' => $permissionName]
        );

        RolePermission::factory()->create([
            'company_id'    => $company->id,
            'role'          => $role,
            'permission_id' => $permission->id,
        ]);
    }

    protected function giveUserPermission(Company $company, User $user, string $permissionName): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => $permissionName],
            ['description' => $permissionName]
        );

        UserPermission::factory()->create([
            'company_id'    => $company->id,
            'user_id'       => $user->id,
            'permission_id' => $permission->id,
        ]);
    }

    protected function createCompanyAdminWithPermissions(array $permissions): User
    {
        $company = Company::factory()->create();

        $user = User::factory()->for($company)->create([
            'role' => UserRole::COMPANY_ADMIN,
        ]);

        foreach ($permissions as $permissionName) {
            $this->giveRolePermission($company, UserRole::COMPANY_ADMIN->value, $permissionName);
        }

        return $user;
    }

    /**
     * @return array{
     *     company: Company,
     *     run: Run,
     *     passenger: Passenger,
     *     definitionPassenger: RouteDefinitionPassenger
     * }
     */
    protected function createRunWithPassenger(Company $company): array
    {
        $corporate = Corporate::factory()->for($company)->create();
        $route = TransportRoute::factory()->for($company)->create();
        $partner = Partner::factory()->for($company)->create();

        $definition = RouteDefinition::factory()
            ->for($company)
            ->for($route, 'route')
            ->for($partner, 'partner')
            ->create();

        $passenger = Passenger::factory()
            ->for($company)
            ->for($corporate)
            ->create();

        $definitionPassenger = RouteDefinitionPassenger::factory()
            ->for($company)
            ->for($definition, 'routeDefinition')
            ->for($passenger, 'passenger')
            ->create([
                'pickup_order'        => 1,
                'planned_pickup_time' => '08:00',
            ]);

        $run = Run::factory()
            ->for($company)
            ->for($definition, 'routeDefinition')
            ->create();

        return [
            'company'            => $company,
            'run'                => $run,
            'passenger'          => $passenger,
            'definitionPassenger'=> $definitionPassenger,
        ];
    }

    public function test_company_admin_with_permission_can_list_run_events(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_RUNS_PERMISSION,
        ]);

        $company = $admin->company;

        $setup = $this->createRunWithPassenger($company);
        $run = $setup['run'];

        // Eventos del run del tenant
        RunEvent::factory()->count(2)->create([
            'company_id' => $company->id,
            'run_id'     => $run->id,
        ]);

        // Evento de otro tenant
        $otherCompany = Company::factory()->create();
        $otherSetup = $this->createRunWithPassenger($otherCompany);
        RunEvent::factory()->create([
            'company_id' => $otherCompany->id,
            'run_id'     => $otherSetup['run']->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/runs/' . $run->id . '/events');

        $response->assertOk();
        $response->assertJsonCount(2);
    }

    public function test_company_user_without_permission_cannot_list_run_events(): void
    {
        $company = Company::factory()->create();

        $user = User::factory()->for($company)->create([
            'role' => UserRole::COMPANY_USER,
        ]);

        $setup = $this->createRunWithPassenger($company);
        $run = $setup['run'];

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/runs/' . $run->id . '/events');

        $response->assertForbidden();
    }

    public function test_company_admin_with_permission_can_create_run_event_respecting_tenant(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_RUNS_PERMISSION,
        ]);

        $company = $admin->company;

        $setup = $this->createRunWithPassenger($company);
        $run = $setup['run'];
        $passenger = $setup['passenger'];
        $definitionPassenger = $setup['definitionPassenger'];

        Sanctum::actingAs($admin);

        $payload = [
            'route_definition_passenger_id' => $definitionPassenger->id,
            'passenger_id'                  => $passenger->id,
            'event_type'                    => 'boarding',
            'occurred_at'                   => '2025-12-06T10:00:00+00:00',
            'lat'                           => -2.1700000,
            'lng'                           => -79.9000000,
            'wait_seconds'                  => 30,
            'notes'                         => 'Passenger boarded on time',
        ];

        $response = $this->postJson('/api/runs/' . $run->id . '/events', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('run_events', [
            'company_id' => $company->id,
            'run_id'     => $run->id,
            'event_type' => 'boarding',
        ]);
    }

    public function test_tenant_mismatch_on_run_is_forbidden_for_non_superadmin(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_RUNS_PERMISSION,
        ]);

        $otherCompany = Company::factory()->create();
        $setup = $this->createRunWithPassenger($otherCompany);
        $foreignRun = $setup['run'];

        Sanctum::actingAs($admin);

        $this->getJson('/api/runs/' . $foreignRun->id . '/events')->assertForbidden();

        $payload = [
            'event_type'  => 'boarding',
            'occurred_at' => '2025-12-06T10:00:00+00:00',
        ];

        $this->postJson('/api/runs/' . $foreignRun->id . '/events', $payload)
            ->assertForbidden();
    }

    public function test_superadmin_can_manage_run_events_across_companies(): void
    {
        $superadmin = User::factory()->create([
            'company_id' => null,
            'role'       => UserRole::SUPERADMIN,
        ]);

        $company = Company::factory()->create();
        $setup = $this->createRunWithPassenger($company);
        $run = $setup['run'];

        RunEvent::factory()->count(2)->create([
            'company_id' => $company->id,
            'run_id'     => $run->id,
        ]);

        Sanctum::actingAs($superadmin);

        $listResponse = $this->getJson('/api/runs/' . $run->id . '/events');
        $listResponse->assertOk();
        $listResponse->assertJsonCount(2);

        $payload = [
            'event_type'  => 'boarding',
            'occurred_at' => '2025-12-06T11:00:00+00:00',
        ];

        $createResponse = $this->postJson('/api/runs/' . $run->id . '/events', $payload);
        $createResponse->assertCreated();

        $this->assertDatabaseHas('run_events', [
            'run_id'     => $run->id,
            'event_type' => 'boarding',
        ]);
    }

    public function test_validation_fails_with_invalid_payload(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_RUNS_PERMISSION,
        ]);

        $company = $admin->company;

        $setup = $this->createRunWithPassenger($company);
        $run = $setup['run'];

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/runs/' . $run->id . '/events', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'event_type',
            'occurred_at',
        ]);
    }

    public function test_cannot_use_passenger_from_different_company(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_RUNS_PERMISSION,
        ]);

        $company = $admin->company;

        $setup = $this->createRunWithPassenger($company);
        $run = $setup['run'];

        // Passenger y RDP de otro tenant
        $otherCompany = Company::factory()->create();
        $otherSetup = $this->createRunWithPassenger($otherCompany);
        $foreignPassenger = $otherSetup['passenger'];
        $foreignDefinitionPassenger = $otherSetup['definitionPassenger'];

        Sanctum::actingAs($admin);

        $payload = [
            'route_definition_passenger_id' => $foreignDefinitionPassenger->id,
            'passenger_id'                  => $foreignPassenger->id,
            'event_type'                    => 'boarding',
            'occurred_at'                   => '2025-12-06T10:00:00+00:00',
        ];

        $response = $this->postJson('/api/runs/' . $run->id . '/events', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'route_definition_passenger_id',
            'passenger_id',
        ]);
    }

    public function test_user_override_permission_allows_access_even_without_role_permission(): void
    {
        $company = Company::factory()->create();

        $user = User::factory()->for($company)->create([
            'role' => UserRole::COMPANY_USER,
        ]);

        // Sin permisos por rol, solo override directo
        $this->giveUserPermission($company, $user, self::VIEW_RUNS_PERMISSION);

        $setup = $this->createRunWithPassenger($company);
        $run = $setup['run'];

        RunEvent::factory()->count(1)->create([
            'company_id' => $company->id,
            'run_id'     => $run->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/runs/' . $run->id . '/events');

        $response->assertOk();
        $response->assertJsonCount(1);
    }
}
