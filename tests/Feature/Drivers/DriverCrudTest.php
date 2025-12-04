<?php

namespace Tests\Feature\Drivers;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\CompanyConfig;
use App\Models\Driver;
use App\Models\Partner;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverCrudTest extends TestCase
{
    use RefreshDatabase;

    private function grantRolePermission(Company $company, UserRole $role, string $permissionName): void
    {
        $permission = Permission::where('name', $permissionName)->firstOrFail();

        RolePermission::create([
            'company_id'    => $company->id,
            'role'          => $role->value,
            'permission_id' => $permission->id,
        ]);
    }

    private function createCompanyWithConfig(array $overrides = []): Company
    {
        $company = Company::factory()->create();

        CompanyConfig::factory()->create(array_merge([
            'company_id'            => $company->id,
            'driver_quota_default'  => 5,
        ], $overrides));

        return $company;
    }

    public function test_company_admin_with_permission_can_list_drivers(): void
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

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'view_drivers');

        Driver::factory()->count(2)->create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'is_active'  => true,
        ]);

        // Another tenant for isolation
        $otherCompany = $this->createCompanyWithConfig();
        $otherPartner = Partner::factory()->create([
            'company_id' => $otherCompany->id,
        ]);
        Driver::factory()->create([
            'company_id' => $otherCompany->id,
            'partner_id' => $otherPartner->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/drivers');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_company_user_without_permission_cannot_list_drivers(): void
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

        Driver::factory()->count(2)->create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/drivers');

        $response->assertStatus(403);
    }

    public function test_company_admin_with_permission_can_create_driver_respecting_tenant(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $partner = Partner::factory()->create([
            'company_id'   => $company->id,
            // Evitamos el 0 por defecto del factory:
            // null => usa driver_quota_default (5)
            'driver_quota' => null,
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'create_driver');

        Sanctum::actingAs($admin);

        $payload = [
            'full_name'      => 'John Driver',
            'phone'          => '0999999999',
            'license_number' => 'ABC123',
            'partner_id'     => $partner->id,
        ];

        $response = $this->postJson('/api/drivers', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('drivers', [
            'full_name'  => 'John Driver',
            'company_id' => $company->id,
            'partner_id' => $partner->id,
        ]);
    }

    public function test_create_driver_fails_when_quota_is_exceeded(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig([
            'driver_quota_default' => 2,
        ]);

        $partner = Partner::factory()->create([
            'company_id'   => $company->id,
            'driver_quota' => null,
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'create_driver');

        Driver::factory()->count(2)->create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($admin);

        $payload = [
            'full_name'      => 'Extra Driver',
            'phone'          => '0123456789',
            'license_number' => 'LIC-EXTRA',
            'partner_id'     => $partner->id,
        ];

        $response = $this->postJson('/api/drivers', $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.partner_id.0', 'Driver quota exceeded for this partner.');

        $this->assertDatabaseMissing('drivers', [
            'full_name' => 'Extra Driver',
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

        $this->grantRolePermission($companyA, UserRole::COMPANY_ADMIN, 'view_drivers');

        $partnerB = Partner::factory()->create([
            'company_id' => $companyB->id,
        ]);

        $driverB = Driver::factory()->create([
            'company_id' => $companyB->id,
            'partner_id' => $partnerB->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($adminA);

        $response = $this->getJson("/api/drivers/{$driverB->id}");

        $response->assertStatus(403);
    }

    public function test_company_admin_with_permission_can_update_driver(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $partner1 = Partner::factory()->create([
            'company_id'   => $company->id,
            'driver_quota' => 10,
        ]);

        $partner2 = Partner::factory()->create([
            'company_id'   => $company->id,
            'driver_quota' => 10,
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'update_driver');

        $driver = Driver::factory()->create([
            'company_id' => $company->id,
            'partner_id' => $partner1->id,
            'full_name'  => 'Old Name',
        ]);

        Sanctum::actingAs($admin);

        $payload = [
            'full_name'  => 'New Name',
            'partner_id' => $partner2->id,
        ];

        $response = $this->putJson("/api/drivers/{$driver->id}", $payload);

        $response->assertOk();

        $this->assertDatabaseHas('drivers', [
            'id'         => $driver->id,
            'company_id' => $company->id,
            'partner_id' => $partner2->id,
            'full_name'  => 'New Name',
        ]);
    }

    public function test_company_admin_with_deactivate_permission_deactivates_driver_instead_of_deleting(): void
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

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'deactivate_driver');

        $driver = Driver::factory()->create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/drivers/{$driver->id}");

        $response->assertStatus(204);

        $this->assertDatabaseHas('drivers', [
            'id'         => $driver->id,
            'company_id' => $company->id,
            'is_active'  => false,
        ]);
    }

    public function test_superadmin_can_manage_drivers_across_companies(): void
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

        Driver::factory()->create([
            'company_id' => $companyA->id,
            'partner_id' => $partnerA->id,
            'is_active'  => true,
        ]);
        $driverB = Driver::factory()->create([
            'company_id' => $companyB->id,
            'partner_id' => $partnerB->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($superadmin);

        $indexResponse = $this->getJson('/api/drivers');
        $indexResponse->assertOk();
        $indexResponse->assertJsonCount(2, 'data');

        $createResponse = $this->postJson('/api/drivers', [
            'company_id'     => $companyA->id,
            'partner_id'     => $partnerA->id,
            'full_name'      => 'Superadmin Driver',
            'phone'          => '0000000000',
            'license_number' => 'SUPER-LIC',
        ]);

        $createResponse->assertStatus(201);

        $this->assertDatabaseHas('drivers', [
            'full_name'  => 'Superadmin Driver',
            'company_id' => $companyA->id,
            'partner_id' => $partnerA->id,
        ]);

        $deleteResponse = $this->deleteJson("/api/drivers/{$driverB->id}");
        $deleteResponse->assertStatus(204);

        $this->assertDatabaseHas('drivers', [
            'id'         => $driverB->id,
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

        $viewDrivers = Permission::where('name', 'view_drivers')->firstOrFail();

        $user->userPermissions()->create([
            'company_id'    => $company->id,
            'permission_id' => $viewDrivers->id,
        ]);

        Driver::factory()->create([
            'company_id' => $company->id,
            'partner_id' => $partner->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/drivers');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }
}
