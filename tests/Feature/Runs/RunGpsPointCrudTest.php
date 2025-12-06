<?php

namespace Tests\Feature\Runs;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Corporate;
use App\Models\Partner;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\Route as TransportRoute;
use App\Models\RouteDefinition;
use App\Models\Run;
use App\Models\RunGpsPoint;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RunGpsPointCrudTest extends TestCase
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
     * @return array{company: Company, run: Run}
     */
    protected function createRunForCompany(Company $company): array
    {
        $corporate = Corporate::factory()->for($company)->create();
        $route = TransportRoute::factory()->for($company)->create();
        $partner = Partner::factory()->for($company)->create();

        $definition = RouteDefinition::factory()
            ->for($company)
            ->for($route, 'route')
            ->for($partner, 'partner')
            ->create();

        $run = Run::factory()
            ->for($company)
            ->for($definition, 'routeDefinition')
            ->create();

        return [
            'company' => $company,
            'run'     => $run,
        ];
    }

    public function test_company_admin_with_permission_can_list_run_gps_points(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_RUNS_PERMISSION,
        ]);

        $company = $admin->company;

        $setup = $this->createRunForCompany($company);
        $run = $setup['run'];

        // puntos del tenant
        RunGpsPoint::factory()->count(2)->create([
            'company_id' => $company->id,
            'run_id'     => $run->id,
        ]);

        // punto de otro tenant
        $otherCompany = Company::factory()->create();
        $otherSetup = $this->createRunForCompany($otherCompany);
        RunGpsPoint::factory()->create([
            'company_id' => $otherCompany->id,
            'run_id'     => $otherSetup['run']->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/runs/' . $run->id . '/gps-points');

        $response->assertOk();
        $response->assertJsonCount(2);
    }

    public function test_company_user_without_permission_cannot_list_run_gps_points(): void
    {
        $company = Company::factory()->create();

        $user = User::factory()->for($company)->create([
            'role' => UserRole::COMPANY_USER,
        ]);

        $setup = $this->createRunForCompany($company);
        $run = $setup['run'];

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/runs/' . $run->id . '/gps-points');

        $response->assertForbidden();
    }

    public function test_company_admin_with_permission_can_create_run_gps_point_respecting_tenant(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_RUNS_PERMISSION,
        ]);

        $company = $admin->company;

        $setup = $this->createRunForCompany($company);
        $run = $setup['run'];

        Sanctum::actingAs($admin);

        $payload = [
            'recorded_at' => '2025-12-06T10:00:00+00:00',
            'lat'         => -2.1700000,
            'lng'         => -79.9000000,
            'speed_kmh'   => 45.5,
        ];

        $response = $this->postJson('/api/runs/' . $run->id . '/gps-points', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('run_gps_points', [
            'company_id' => $company->id,
            'run_id'     => $run->id,
            'lat'        => -2.1700000,
            'lng'        => -79.9000000,
        ]);
    }

    public function test_tenant_mismatch_on_run_is_forbidden_for_non_superadmin(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_RUNS_PERMISSION,
        ]);

        $otherCompany = Company::factory()->create();
        $setup = $this->createRunForCompany($otherCompany);
        $foreignRun = $setup['run'];

        Sanctum::actingAs($admin);

        $this->getJson('/api/runs/' . $foreignRun->id . '/gps-points')
            ->assertForbidden();

        $payload = [
            'recorded_at' => '2025-12-06T10:00:00+00:00',
            'lat'         => -2.17,
            'lng'         => -79.9,
        ];

        $this->postJson('/api/runs/' . $foreignRun->id . '/gps-points', $payload)
            ->assertForbidden();
    }

    public function test_superadmin_can_manage_run_gps_points_across_companies(): void
    {
        $superadmin = User::factory()->create([
            'company_id' => null,
            'role'       => UserRole::SUPERADMIN,
        ]);

        $company = Company::factory()->create();
        $setup = $this->createRunForCompany($company);
        $run = $setup['run'];

        RunGpsPoint::factory()->count(2)->create([
            'company_id' => $company->id,
            'run_id'     => $run->id,
        ]);

        Sanctum::actingAs($superadmin);

        $listResponse = $this->getJson('/api/runs/' . $run->id . '/gps-points');
        $listResponse->assertOk();
        $listResponse->assertJsonCount(2);

        $payload = [
            'recorded_at' => '2025-12-06T11:00:00+00:00',
            'lat'         => -2.18,
            'lng'         => -79.91,
            'speed_kmh'   => 30.0,
        ];

        $createResponse = $this->postJson('/api/runs/' . $run->id . '/gps-points', $payload);
        $createResponse->assertCreated();

        $this->assertDatabaseHas('run_gps_points', [
            'run_id' => $run->id,
            'lat'    => -2.18,
            'lng'    => -79.91,
        ]);
    }

    public function test_validation_fails_with_invalid_payload(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_RUNS_PERMISSION,
        ]);

        $setup = $this->createRunForCompany($admin->company);
        $run = $setup['run'];

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/runs/' . $run->id . '/gps-points', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'recorded_at',
            'lat',
            'lng',
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

        $setup = $this->createRunForCompany($company);
        $run = $setup['run'];

        RunGpsPoint::factory()->count(1)->create([
            'company_id' => $company->id,
            'run_id'     => $run->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/runs/' . $run->id . '/gps-points');

        $response->assertOk();
        $response->assertJsonCount(1);
    }
}
