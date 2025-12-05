<?php

namespace Tests\Feature\Routes;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\Route as TransportRoute;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RouteCrudTest extends TestCase
{
    use RefreshDatabase;

    private const VIEW_PERMISSION   = 'view_routes';
    private const CREATE_PERMISSION = 'create_route';
    private const UPDATE_PERMISSION = 'update_route';
    private const DELETE_PERMISSION = 'deactivate_route';

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

    protected function createRouteForCompany(Company $company): TransportRoute
    {
        return TransportRoute::factory()->for($company)->create();
    }

    public function test_company_admin_with_permission_can_list_routes(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_PERMISSION,
        ]);

        $company = $admin->company;
        $this->createRouteForCompany($company);

        $otherCompany = Company::factory()->create();
        $this->createRouteForCompany($otherCompany);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/routes');

        $response->assertOk();
        $response->assertJsonFragment(['company_id' => $company->id]);
        $response->assertJsonMissing(['company_id' => $otherCompany->id]);
    }

    public function test_company_user_without_permission_cannot_list_routes(): void
    {
        $user = $this->createCompanyUserWithoutPermissions();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/routes');

        $response->assertForbidden();
    }

    public function test_company_admin_with_permission_can_create_route_respecting_tenant(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::CREATE_PERMISSION,
        ]);

        $company = $admin->company;

        Sanctum::actingAs($admin);

        $payload = [
            'name' => 'Guasmo',
            'description' => 'Ruta base Guasmo',
            'zone_label' => 'SOUTH',
        ];

        $response = $this->postJson('/api/routes', $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('routes', [
            'company_id' => $company->id,
            'name'       => 'Guasmo',
            'is_active'  => true,
        ]);
    }

    public function test_tenant_mismatch_on_show_is_forbidden_for_non_superadmin(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_PERMISSION,
        ]);

        $otherCompany = Company::factory()->create();
        $foreignRoute = $this->createRouteForCompany($otherCompany);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/routes/' . $foreignRoute->id);

        $response->assertForbidden();
    }

    public function test_company_admin_with_permission_can_update_route(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::UPDATE_PERMISSION,
        ]);

        $route = $this->createRouteForCompany($admin->company);

        Sanctum::actingAs($admin);

        $payload = [
            'description' => 'Updated description',
            'zone_label'  => 'UPDATED-ZONE',
        ];

        $response = $this->putJson('/api/routes/' . $route->id, $payload);

        $response->assertOk();
        $this->assertDatabaseHas('routes', [
            'id'          => $route->id,
            'description' => 'Updated description',
            'zone_label'  => 'UPDATED-ZONE',
        ]);
    }

    public function test_company_admin_with_delete_permission_deactivates_route_instead_of_deleting(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::DELETE_PERMISSION,
        ]);

        $route = $this->createRouteForCompany($admin->company);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson('/api/routes/' . $route->id);

        $response->assertNoContent();
        $this->assertDatabaseHas('routes', [
            'id'        => $route->id,
            'is_active' => false,
        ]);
    }

    public function test_superadmin_can_manage_routes_across_companies(): void
    {
        $superadmin = User::factory()->create([
            'company_id' => null,
            'role'       => UserRole::SUPERADMIN,
        ]);

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $routeA = $this->createRouteForCompany($companyA);
        $routeB = $this->createRouteForCompany($companyB);

        Sanctum::actingAs($superadmin);

        $response = $this->getJson('/api/routes');
        $response->assertOk();
        $response->assertJsonFragment(['id' => $routeA->id]);
        $response->assertJsonFragment(['id' => $routeB->id]);

        $deleteResponse = $this->deleteJson('/api/routes/' . $routeB->id);
        $deleteResponse->assertNoContent();

        $this->assertDatabaseHas('routes', [
            'id'        => $routeB->id,
            'is_active' => false,
        ]);
    }

    public function test_validation_fails_when_creating_route_with_invalid_payload(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::CREATE_PERMISSION,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/routes', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name',
        ]);
    }

    public function test_user_override_permission_allows_access_even_without_role_permission(): void
    {
        $company = Company::factory()->create();

        $user = User::factory()->for($company)->create([
            'role' => UserRole::COMPANY_USER,
        ]);

        // No role permission, solo user override
        $this->giveUserPermission($company, $user, self::VIEW_PERMISSION);

        $this->createRouteForCompany($company);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/routes');

        $response->assertOk();
    }
}
