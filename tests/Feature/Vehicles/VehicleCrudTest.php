<?php

namespace Tests\Feature\Vehicles;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\CompanyConfig;
use App\Models\Partner;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VehicleCrudTest extends TestCase
{
    use RefreshDatabase;

    private function createCompanyWithConfig(array $overrides = []): Company
    {
        $company = Company::factory()->create();

        CompanyConfig::factory()->create(array_merge([
            'company_id'           => $company->id,
            'driver_quota_default' => 5,
        ], $overrides));

        return $company;
    }

    private function grantRolePermission(Company $company, UserRole $role, string $permissionName): void
    {
        $permission = Permission::where('name', $permissionName)->firstOrFail();

        RolePermission::create([
            'company_id'    => $company->id,
            'role'          => $role->value,
            'permission_id' => $permission->id,
        ]);
    }

    public function test_company_admin_with_permission_can_list_vehicles(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $partner = Partner::factory()->create([
            'company_id' => $company->id,
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'view_vehicles');

        Vehicle::factory()->count(2)->create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'is_active'  => true,
        ]);

        // Otro tenant para asegurar aislamiento
        $otherCompany = $this->createCompanyWithConfig();
        $otherPartner = Partner::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        Vehicle::factory()->create([
            'company_id' => $otherCompany->id,
            'partner_id' => $otherPartner->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/vehicles');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_company_user_without_permission_cannot_list_vehicles(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $partner = Partner::factory()->create([
            'company_id' => $company->id,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_USER,
        ]);

        Vehicle::factory()->count(2)->create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/vehicles');

        $response->assertStatus(403);
    }

    public function test_company_admin_with_permission_can_create_vehicle_respecting_tenant(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $partner = Partner::factory()->create([
            'company_id'   => $company->id,
            'driver_quota' => null,
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'create_vehicle');

        Sanctum::actingAs($admin);

        $payload = [
            'plate'    => 'GYE-1234',
            'model'    => 'Sprinter',
            'capacity' => 20,
            'partner_id' => $partner->id,
        ];

        $response = $this->postJson('/api/vehicles', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('vehicles', [
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'plate'      => 'GYE-1234',
            'model'      => 'Sprinter',
            'capacity'   => 20,
        ]);
    }

    public function test_tenant_mismatch_on_show_is_forbidden_for_non_superadmin(): void
    {
        $this->seed(PermissionSeeder::class);

        $companyA = $this->createCompanyWithConfig();
        $companyB = $this->createCompanyWithConfig();

        $adminA = User::factory()->create([
            'company_id' => $companyA->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($companyA, UserRole::COMPANY_ADMIN, 'view_vehicles');

        $partnerB = Partner::factory()->create([
            'company_id' => $companyB->id,
        ]);

        $vehicleB = Vehicle::factory()->create([
            'company_id' => $companyB->id,
            'partner_id' => $partnerB->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($adminA);

        $response = $this->getJson("/api/vehicles/{$vehicleB->id}");

        $response->assertStatus(403);
    }

    public function test_company_admin_with_permission_can_update_vehicle(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $partner1 = Partner::factory()->create([
            'company_id' => $company->id,
        ]);

        $partner2 = Partner::factory()->create([
            'company_id' => $company->id,
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'update_vehicle');

        $vehicle = Vehicle::factory()->create([
            'company_id' => $company->id,
            'partner_id' => $partner1->id,
            'plate'      => 'OLD-0001',
            'model'      => 'Old Model',
            'capacity'   => 10,
        ]);

        Sanctum::actingAs($admin);

        $payload = [
            'plate'      => 'NEW-0001',
            'model'      => 'New Model',
            'capacity'   => 25,
            'partner_id' => $partner2->id,
        ];

        $response = $this->putJson("/api/vehicles/{$vehicle->id}", $payload);

        $response->assertOk();

        $this->assertDatabaseHas('vehicles', [
            'id'         => $vehicle->id,
            'company_id' => $company->id,
            'partner_id' => $partner2->id,
            'plate'      => 'NEW-0001',
            'model'      => 'New Model',
            'capacity'   => 25,
        ]);
    }

    public function test_company_admin_with_deactivate_permission_deactivates_vehicle_instead_of_deleting(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $partner = Partner::factory()->create([
            'company_id' => $company->id,
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'deactivate_vehicle');

        $vehicle = Vehicle::factory()->create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/vehicles/{$vehicle->id}");

        $response->assertStatus(204);

        $this->assertDatabaseHas('vehicles', [
            'id'         => $vehicle->id,
            'company_id' => $company->id,
            'is_active'  => false,
        ]);
    }

    public function test_superadmin_can_manage_vehicles_across_companies(): void
    {
        $this->seed(PermissionSeeder::class);

        $companyA = $this->createCompanyWithConfig();
        $companyB = $this->createCompanyWithConfig();

        $partnerA = Partner::factory()->create([
            'company_id' => $companyA->id,
        ]);

        $partnerB = Partner::factory()->create([
            'company_id' => $companyB->id,
        ]);

        $superadmin = User::factory()->create([
            'company_id' => null,
            'role'       => UserRole::SUPERADMIN,
        ]);

        Vehicle::factory()->create([
            'company_id' => $companyA->id,
            'partner_id' => $partnerA->id,
            'is_active'  => true,
        ]);

        $vehicleB = Vehicle::factory()->create([
            'company_id' => $companyB->id,
            'partner_id' => $partnerB->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($superadmin);

        $indexResponse = $this->getJson('/api/vehicles');
        $indexResponse->assertOk();
        $indexResponse->assertJsonCount(2, 'data');

        $createResponse = $this->postJson('/api/vehicles', [
            'company_id' => $companyA->id,
            'partner_id' => $partnerA->id,
            'plate'      => 'SUP-0001',
            'model'      => 'Superadmin Van',
            'capacity'   => 30,
        ]);
        $createResponse->assertStatus(201);

        $this->assertDatabaseHas('vehicles', [
            'plate'      => 'SUP-0001',
            'company_id' => $companyA->id,
            'partner_id' => $partnerA->id,
        ]);

        $deleteResponse = $this->deleteJson("/api/vehicles/{$vehicleB->id}");
        $deleteResponse->assertStatus(204);

        $this->assertDatabaseHas('vehicles', [
            'id'         => $vehicleB->id,
            'company_id' => $companyB->id,
            'is_active'  => false,
        ]);
    }

    public function test_user_override_permission_allows_access_even_without_role_permission(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $partner = Partner::factory()->create([
            'company_id' => $company->id,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_USER,
        ]);

        $viewVehicles = Permission::where('name', 'view_vehicles')->firstOrFail();

        // Override a nivel usuario sin role_permission
        $user->userPermissions()->create([
            'company_id'    => $company->id,
            'permission_id' => $viewVehicles->id,
        ]);

        Vehicle::factory()->create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/vehicles');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }
}
