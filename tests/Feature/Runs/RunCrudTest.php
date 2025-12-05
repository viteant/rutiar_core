<?php

namespace Tests\Feature\Runs;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\Route as TransportRoute;
use App\Models\RouteDefinition;
use App\Models\Run;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RunCrudTest extends TestCase
{
    use RefreshDatabase;

    private const VIEW_PERMISSION = 'view_runs';

    private const CREATE_PERMISSION = 'view_runs'; // create usa view_runs en la policy
    private const UPDATE_PERMISSION = 'view_runs';

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

    protected function createCompanyUserWithoutPermissions(): User
    {
        $company = Company::factory()->create();

        return User::factory()->for($company)->create([
            'role' => UserRole::COMPANY_USER,
        ]);
    }

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

    protected function createRouteDefinitionForCompany(Company $company): RouteDefinition
    {
        $route = TransportRoute::factory()->for($company)->create();
        $partner = Partner::factory()->for($company)->create();

        return RouteDefinition::factory()
            ->for($company)
            ->for($route, 'route')
            ->for($partner, 'partner')
            ->create();
    }

    protected function createRunForCompany(Company $company): Run
    {
        $definition = $this->createRouteDefinitionForCompany($company);
        $partner = $definition->partner;

        return Run::factory()
            ->for($company)
            ->for($definition, 'routeDefinition')
            ->create([
                'partner_id' => $partner->id,
            ]);
    }

    public function test_company_admin_with_permission_can_list_runs(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_PERMISSION,
        ]);

        $company = $admin->company;

        $runA = $this->createRunForCompany($company);

        $otherCompany = Company::factory()->create();
        $this->createRunForCompany($otherCompany);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/runs');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $runA->id]);
        $response->assertJsonMissing(['company_id' => $otherCompany->id]);
    }

    public function test_company_user_without_permission_cannot_list_runs(): void
    {
        $user = $this->createCompanyUserWithoutPermissions();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/runs');

        $response->assertForbidden();
    }

    public function test_company_admin_with_permission_can_create_run_respecting_tenant(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::CREATE_PERMISSION,
        ]);

        $company = $admin->company;
        $definition = $this->createRouteDefinitionForCompany($company);
        $partner = $definition->partner;

        Sanctum::actingAs($admin);

        $payload = [
            'route_definition_id' => $definition->id,
            'service_date'        => '2025-12-05',
            'partner_id'          => $partner->id,
            'fare_amount'         => 25.50,
        ];

        $response = $this->postJson('/api/runs', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('runs', [
            'company_id'          => $company->id,
            'route_definition_id' => $definition->id,
            'service_date'        => '2025-12-05',
            'partner_id'          => $partner->id,
        ]);
    }

    public function test_tenant_mismatch_on_show_is_forbidden_for_non_superadmin(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_PERMISSION,
        ]);

        $otherCompany = Company::factory()->create();
        $foreignRun = $this->createRunForCompany($otherCompany);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/runs/' . $foreignRun->id);

        $response->assertForbidden();
    }

    public function test_company_admin_with_permission_can_update_run(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::UPDATE_PERMISSION,
        ]);

        $company = $admin->company;
        $run = $this->createRunForCompany($company);

        $newPartner = Partner::factory()->for($company)->create();

        Sanctum::actingAs($admin);

        $payload = [
            'partner_id'  => $newPartner->id,
            'fare_amount' => 35.75,
        ];

        $response = $this->putJson('/api/runs/' . $run->id, $payload);

        $response->assertOk();

        $this->assertDatabaseHas('runs', [
            'id'          => $run->id,
            'partner_id'  => $newPartner->id,
            'fare_amount' => 35.75,
        ]);
    }

    public function test_superadmin_can_manage_runs_across_companies(): void
    {
        $superadmin = User::factory()->create([
            'company_id' => null,
            'role'       => UserRole::SUPERADMIN,
        ]);

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $runA = $this->createRunForCompany($companyA);
        $runB = $this->createRunForCompany($companyB);

        Sanctum::actingAs($superadmin);

        $response = $this->getJson('/api/runs');
        $response->assertOk();
        $response->assertJsonFragment(['id' => $runA->id]);
        $response->assertJsonFragment(['id' => $runB->id]);

        $updateResponse = $this->putJson('/api/runs/' . $runB->id, [
            'fare_amount' => 99.99,
        ]);

        $updateResponse->assertOk();

        $this->assertDatabaseHas('runs', [
            'id'          => $runB->id,
            'fare_amount' => 99.99,
        ]);
    }

    public function test_validation_fails_when_creating_run_with_invalid_payload(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::CREATE_PERMISSION,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/runs', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'route_definition_id',
            'service_date',
            'partner_id',
        ]);
    }

    public function test_user_override_permission_allows_access_even_without_role_permission(): void
    {
        $company = Company::factory()->create();

        $user = User::factory()->for($company)->create([
            'role' => UserRole::COMPANY_USER,
        ]);

        // Sin permisos por rol, solo override directo
        $this->giveUserPermission($company, $user, self::VIEW_PERMISSION);

        $run = $this->createRunForCompany($company);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/runs');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $run->id]);
    }
}
