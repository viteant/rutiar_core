<?php

namespace Tests\Feature\Routes;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\Company;
use App\Models\User;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\UserPermission;
use App\Models\Partner;
use App\Models\Corporate;
use App\Models\Route as TransportRoute;
use App\Models\RouteDefinition;
use App\Enums\UserRole;

class RouteDefinitionCrudTest extends TestCase
{
    use RefreshDatabase;

    private const VIEW_PERMISSION   = 'view_route_definitions';
    private const CREATE_PERMISSION = 'create_route_definition';
    private const UPDATE_PERMISSION = 'update_route_definition';
    private const DELETE_PERMISSION = 'deactivate_route_definition';

    // Helpers básicos
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
        $partner = Partner::factory()->for($company)->create();
        $corporate = Corporate::factory()->for($company)->create();
        $route = TransportRoute::factory()->for($company)->create();

        return RouteDefinition::factory()->for($company)
            ->for($route, 'route')
            ->for($corporate, 'corporate')
            ->for($partner, 'partner')
            ->create();
    }

    public function test_company_admin_with_permission_can_list_route_definitions(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_PERMISSION,
        ]);

        $company = $admin->company;
        $this->createRouteDefinitionForCompany($company);
        // Otra compañía para validar tenant
        $otherCompany = Company::factory()->create();
        $this->createRouteDefinitionForCompany($otherCompany);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/route-definitions');

        $response->assertOk();
        $response->assertJsonFragment(['company_id' => $company->id]);
        $response->assertJsonMissing(['company_id' => $otherCompany->id]);
    }

    public function test_company_user_without_permission_cannot_list_route_definitions(): void
    {
        $user = $this->createCompanyUserWithoutPermissions();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/route-definitions');

        $response->assertForbidden();
    }

    public function test_company_admin_with_permission_can_create_route_definition_respecting_tenant(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::CREATE_PERMISSION,
        ]);

        $company = $admin->company;
        $partner = Partner::factory()->for($company)->create();
        $corporate = Corporate::factory()->for($company)->create();
        $route = TransportRoute::factory()->for($company)->create();

        Sanctum::actingAs($admin);

        $payload = [
            // company_id debe ser ignorado / inyectado por withCompanyId, así que no se envía
            'route_id'         => $route->id,
            'corporate_id'     => $corporate->id,
            'partner_id'       => $partner->id,
            'version'          => 1,
            'direction'        => 'OUTBOUND', // valor válido de run_direction
            'reference_time'   => '08:00:00',
            'billing_code'     => 'GYE-GUASMO-01',
            'base_fare_amount' => '10.50',
        ];

        $response = $this->postJson('/api/route-definitions', $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('route_definitions', [
            'company_id'  => $company->id,
            'route_id'    => $route->id,
            'corporate_id'=> $corporate->id,
            'partner_id'  => $partner->id,
            'version'     => 1,
            'is_active'   => true,
        ]);
    }

    public function test_tenant_mismatch_on_show_is_forbidden_for_non_superadmin(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_PERMISSION,
        ]);

        $otherCompany = Company::factory()->create();
        $foreignDefinition = $this->createRouteDefinitionForCompany($otherCompany);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/route-definitions/' . $foreignDefinition->id);

        $response->assertForbidden();
    }

    public function test_company_admin_with_permission_can_update_route_definition(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::UPDATE_PERMISSION,
        ]);

        $definition = $this->createRouteDefinitionForCompany($admin->company);

        Sanctum::actingAs($admin);

        $payload = [
            'billing_code'     => 'UPDATED-CODE',
            'base_fare_amount' => '15.75',
        ];

        $response = $this->putJson('/api/route-definitions/' . $definition->id, $payload);

        $response->assertOk();
        $this->assertDatabaseHas('route_definitions', [
            'id'               => $definition->id,
            'billing_code'     => 'UPDATED-CODE',
            'base_fare_amount' => 15.75,
        ]);
    }

    public function test_company_admin_with_delete_permission_deactivates_route_definition_instead_of_deleting(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::DELETE_PERMISSION,
        ]);

        $definition = $this->createRouteDefinitionForCompany($admin->company);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson('/api/route-definitions/' . $definition->id);

        $response->assertNoContent();
        $this->assertDatabaseHas('route_definitions', [
            'id'        => $definition->id,
            'is_active' => false,
        ]);
    }

    public function test_superadmin_can_manage_route_definitions_across_companies(): void
    {
        $superadmin = User::factory()->create([
            'company_id' => null,
            'role'       => UserRole::SUPERADMIN,
        ]);

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $definitionA = $this->createRouteDefinitionForCompany($companyA);
        $definitionB = $this->createRouteDefinitionForCompany($companyB);

        Sanctum::actingAs($superadmin);

        // listar cross-tenant
        $response = $this->getJson('/api/route-definitions');
        $response->assertOk();
        $response->assertJsonFragment(['id' => $definitionA->id]);
        $response->assertJsonFragment(['id' => $definitionB->id]);

        // desactivar uno en B usando company_id explícito
        $payload = ['company_id' => $companyB->id];
        $deleteResponse = $this->deleteJson('/api/route-definitions/' . $definitionB->id, $payload);

        $deleteResponse->assertNoContent();
        $this->assertDatabaseHas('route_definitions', [
            'id'        => $definitionB->id,
            'is_active' => false,
        ]);
    }

    public function test_validation_fails_with_invalid_payload(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::CREATE_PERMISSION,
        ]);

        Sanctum::actingAs($admin);

        // Payload vacío, debería fallar validación
        $response = $this->postJson('/api/route-definitions', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'route_id',
            'corporate_id',
            'partner_id',
            'direction',
        ]);
    }

    public function test_user_override_permission_allows_access_even_without_role_permission(): void
    {
        $company = Company::factory()->create();

        $user = User::factory()->for($company)->create([
            'role' => UserRole::COMPANY_USER,
        ]);

        // No damos permiso por rol, solo override directo
        $this->giveUserPermission($company, $user, self::VIEW_PERMISSION);

        $this->createRouteDefinitionForCompany($company);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/route-definitions');

        $response->assertOk();
    }
}
