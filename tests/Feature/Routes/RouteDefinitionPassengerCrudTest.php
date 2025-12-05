<?php

namespace Tests\Feature\Routes;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Corporate;
use App\Models\Passenger;
use App\Models\Partner;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\Route as TransportRoute;
use App\Models\RouteDefinition;
use App\Models\RouteDefinitionPassenger;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RouteDefinitionPassengerCrudTest extends TestCase
{
    use RefreshDatabase;

    private const VIEW_DEFINITIONS_PERMISSION   = 'view_route_definitions';
    private const UPDATE_DEFINITIONS_PERMISSION = 'update_route_definition';

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

    protected function createRouteDefinitionWithPassenger(Company $company): array
    {
        $corporate = Corporate::factory()->for($company)->create();
        $partner   = Partner::factory()->for($company)->create();
        $route     = TransportRoute::factory()->for($company)->create();

        $definition = RouteDefinition::factory()
            ->for($company)
            ->for($route, 'route')
            ->for($corporate, 'corporate')
            ->for($partner, 'partner')
            ->create();

        $passenger = Passenger::factory()
            ->for($company)
            ->for($corporate, 'corporate')
            ->create();

        $item = RouteDefinitionPassenger::factory()
            ->for($company)
            ->for($definition, 'routeDefinition')
            ->for($passenger, 'passenger')
            ->create();

        return [$definition, $passenger, $item];
    }

    public function test_company_admin_with_permission_can_list_manifest_items(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_DEFINITIONS_PERMISSION,
            self::UPDATE_DEFINITIONS_PERMISSION,
        ]);

        $company = $admin->company;

        [$definitionA, $passengerA, $itemA] = $this->createRouteDefinitionWithPassenger($company);

        $otherCompany = Company::factory()->create();
        $this->createRouteDefinitionWithPassenger($otherCompany);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/route-definition-passengers');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $itemA->id]);
        $response->assertJsonMissing(['company_id' => $otherCompany->id]);
    }

    public function test_company_user_without_permission_cannot_list_manifest_items(): void
    {
        $user = $this->createCompanyUserWithoutPermissions();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/route-definition-passengers');

        $response->assertForbidden();
    }

    public function test_company_admin_with_permission_can_create_manifest_item_respecting_tenant(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::UPDATE_DEFINITIONS_PERMISSION,
        ]);

        $company   = $admin->company;
        $corporate = Corporate::factory()->for($company)->create();
        $partner   = Partner::factory()->for($company)->create();
        $route     = TransportRoute::factory()->for($company)->create();

        $definition = RouteDefinition::factory()
            ->for($company)
            ->for($route, 'route')
            ->for($corporate, 'corporate')
            ->for($partner, 'partner')
            ->create();

        $passenger = Passenger::factory()
            ->for($company)
            ->for($corporate, 'corporate')
            ->create();

        Sanctum::actingAs($admin);

        $payload = [
            'route_definition_id' => $definition->id,
            'passenger_id'        => $passenger->id,
            'pickup_order'        => 1,
            'planned_pickup_time' => '08:00:00',
            'pickup_address'      => 'Calle Falsa 123',
        ];

        $response = $this->postJson('/api/route-definition-passengers', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('route_definition_passengers', [
            'company_id'          => $company->id,
            'route_definition_id' => $definition->id,
            'passenger_id'        => $passenger->id,
            'pickup_order'        => 1,
            'is_active'           => true,
        ]);
    }

    public function test_tenant_mismatch_on_manifest_show_is_forbidden_for_non_superadmin(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_DEFINITIONS_PERMISSION,
        ]);

        $otherCompany = Company::factory()->create();
        [, , $foreignItem] = $this->createRouteDefinitionWithPassenger($otherCompany);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/route-definition-passengers/' . $foreignItem->id);

        $response->assertForbidden();
    }

    public function test_company_admin_with_permission_can_update_manifest_item(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::UPDATE_DEFINITIONS_PERMISSION,
        ]);

        $company = $admin->company;
        [, , $item] = $this->createRouteDefinitionWithPassenger($company);

        Sanctum::actingAs($admin);

        $payload = [
            'pickup_order'        => 5,
            'pickup_address'      => 'Nueva dirección',
            'planned_pickup_time' => '09:30:00',
        ];

        $response = $this->putJson('/api/route-definition-passengers/' . $item->id, $payload);

        $response->assertOk();

        $this->assertDatabaseHas('route_definition_passengers', [
            'id'                  => $item->id,
            'pickup_order'        => 5,
            'pickup_address'      => 'Nueva dirección',
            'planned_pickup_time' => '09:30:00',
        ]);
    }

    public function test_company_admin_can_deactivate_manifest_item_instead_of_deleting(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::UPDATE_DEFINITIONS_PERMISSION,
        ]);

        $company = $admin->company;
        [, , $item] = $this->createRouteDefinitionWithPassenger($company);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson('/api/route-definition-passengers/' . $item->id);

        $response->assertNoContent();

        $this->assertDatabaseHas('route_definition_passengers', [
            'id'        => $item->id,
            'is_active' => false,
        ]);
    }

    public function test_cannot_attach_passenger_from_different_corporate(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::UPDATE_DEFINITIONS_PERMISSION,
        ]);

        $company = $admin->company;

        $corporateA = Corporate::factory()->for($company)->create();
        $corporateB = Corporate::factory()->for($company)->create();

        $partner = Partner::factory()->for($company)->create();
        $route   = TransportRoute::factory()->for($company)->create();

        $definition = RouteDefinition::factory()
            ->for($company)
            ->for($route, 'route')
            ->for($corporateA, 'corporate')
            ->for($partner, 'partner')
            ->create();

        $passengerOtherCorporate = Passenger::factory()
            ->for($company)
            ->for($corporateB, 'corporate')
            ->create();

        Sanctum::actingAs($admin);

        $payload = [
            'route_definition_id' => $definition->id,
            'passenger_id'        => $passengerOtherCorporate->id,
            'pickup_order'        => 1,
        ];

        $response = $this->postJson('/api/route-definition-passengers', $payload);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Passenger does not belong to the same corporate as the route definition.',
        ]);
        $response->assertJsonFragment([
            'Passenger corporate mismatch with route definition.',
        ]);
    }


    public function test_superadmin_can_manage_manifest_across_companies(): void
    {
        $superadmin = User::factory()->create([
            'company_id' => null,
            'role'       => UserRole::SUPERADMIN,
        ]);

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        [, , $itemA] = $this->createRouteDefinitionWithPassenger($companyA);
        [, , $itemB] = $this->createRouteDefinitionWithPassenger($companyB);

        Sanctum::actingAs($superadmin);

        $response = $this->getJson('/api/route-definition-passengers');
        $response->assertOk();
        $response->assertJsonFragment(['id' => $itemA->id]);
        $response->assertJsonFragment(['id' => $itemB->id]);

        $deleteResponse = $this->deleteJson('/api/route-definition-passengers/' . $itemB->id);
        $deleteResponse->assertNoContent();

        $this->assertDatabaseHas('route_definition_passengers', [
            'id'        => $itemB->id,
            'is_active' => false,
        ]);
    }

    public function test_user_override_permission_allows_manifest_access_even_without_role_permission(): void
    {
        $company = Company::factory()->create();

        $user = User::factory()->for($company)->create([
            'role' => UserRole::COMPANY_USER,
        ]);

        // No damos permisos por rol, solo override
        $this->giveUserPermission($company, $user, self::VIEW_DEFINITIONS_PERMISSION);

        [, , $item] = $this->createRouteDefinitionWithPassenger($company);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/route-definition-passengers');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $item->id]);
    }
}
