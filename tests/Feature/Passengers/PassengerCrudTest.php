<?php

namespace Tests\Feature\Passengers;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\CompanyConfig;
use App\Models\Corporate;
use App\Models\Passenger;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PassengerCrudTest extends TestCase
{
    use RefreshDatabase;

    private function createCompanyWithConfig(): Company
    {
        $company = Company::factory()->create();

        CompanyConfig::factory()->create([
            'company_id'           => $company->id,
            'driver_quota_default' => 5,
        ]);

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

    public function test_company_admin_with_permission_can_list_passengers(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $corporate = Corporate::factory()->create([
            'company_id' => $company->id,
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'view_passengers');

        Passenger::factory()->count(2)->create([
            'company_id'   => $company->id,
            'corporate_id' => $corporate->id,
            'is_active'    => true,
        ]);

        // otro tenant
        $otherCompany = $this->createCompanyWithConfig();
        $otherCorporate = Corporate::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        Passenger::factory()->create([
            'company_id'   => $otherCompany->id,
            'corporate_id' => $otherCorporate->id,
            'is_active'    => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/passengers');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_company_user_without_permission_cannot_list_passengers(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $corporate = Corporate::factory()->create([
            'company_id' => $company->id,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_USER,
        ]);

        Passenger::factory()->count(2)->create([
            'company_id'   => $company->id,
            'corporate_id' => $corporate->id,
            'is_active'    => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/passengers');

        $response->assertStatus(403);
    }

    public function test_company_admin_with_permission_can_create_passenger_respecting_tenant(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $corporate = Corporate::factory()->create([
            'company_id' => $company->id,
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'create_passenger');

        Sanctum::actingAs($admin);

        $payload = [
            'full_name'     => 'Jane Passenger',
            'employee_code' => 'EMP-0001',
            'document_id'   => '1234567890',
            'home_address'  => 'Calle falsa 123',
            'home_lat'      => -2.1700,
            'home_lng'      => -79.9200,
            'shift_code'    => 'SHIFT-01',
            'corporate_id'  => $corporate->id,
        ];

        $response = $this->postJson('/api/passengers', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('passengers', [
            'company_id'   => $company->id,
            'corporate_id' => $corporate->id,
            'full_name'    => 'Jane Passenger',
            'employee_code'=> 'EMP-0001',
        ]);
    }

    public function test_tenant_mismatch_on_show_is_forbidden_for_non_superadmin(): void
    {
        $this->seed(PermissionSeeder::class);

        $companyA = $this->createCompanyWithConfig();
        $companyB = $this->createCompanyWithConfig();

        $corporateB = Corporate::factory()->create([
            'company_id' => $companyB->id,
        ]);

        $adminA = User::factory()->create([
            'company_id' => $companyA->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($companyA, UserRole::COMPANY_ADMIN, 'view_passengers');

        $passengerB = Passenger::factory()->create([
            'company_id'   => $companyB->id,
            'corporate_id' => $corporateB->id,
            'is_active'    => true,
        ]);

        Sanctum::actingAs($adminA);

        $response = $this->getJson("/api/passengers/{$passengerB->id}");

        $response->assertStatus(403);
    }

    public function test_company_admin_with_permission_can_update_passenger(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $corporate1 = Corporate::factory()->create([
            'company_id' => $company->id,
        ]);
        $corporate2 = Corporate::factory()->create([
            'company_id' => $company->id,
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'update_passenger');

        $passenger = Passenger::factory()->create([
            'company_id'   => $company->id,
            'corporate_id' => $corporate1->id,
            'full_name'    => 'Old Name',
        ]);

        Sanctum::actingAs($admin);

        $payload = [
            'full_name'    => 'New Name',
            'shift_code'   => 'SHIFT-02',
            'corporate_id' => $corporate2->id,
        ];

        $response = $this->putJson("/api/passengers/{$passenger->id}", $payload);

        $response->assertOk();

        $this->assertDatabaseHas('passengers', [
            'id'           => $passenger->id,
            'company_id'   => $company->id,
            'corporate_id' => $corporate2->id,
            'full_name'    => 'New Name',
            'shift_code'   => 'SHIFT-02',
        ]);
    }

    public function test_company_admin_with_deactivate_permission_deactivates_passenger_instead_of_deleting(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $corporate = Corporate::factory()->create([
            'company_id' => $company->id,
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'deactivate_passenger');

        $passenger = Passenger::factory()->create([
            'company_id'   => $company->id,
            'corporate_id' => $corporate->id,
            'is_active'    => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/passengers/{$passenger->id}");

        $response->assertStatus(204);

        $this->assertDatabaseHas('passengers', [
            'id'         => $passenger->id,
            'company_id' => $company->id,
            'is_active'  => false,
        ]);
    }

    public function test_superadmin_can_manage_passengers_across_companies(): void
    {
        $this->seed(PermissionSeeder::class);

        $companyA = $this->createCompanyWithConfig();
        $companyB = $this->createCompanyWithConfig();

        $corporateA = Corporate::factory()->create([
            'company_id' => $companyA->id,
        ]);
        $corporateB = Corporate::factory()->create([
            'company_id' => $companyB->id,
        ]);

        $superadmin = User::factory()->create([
            'company_id' => null,
            'role'       => UserRole::SUPERADMIN,
        ]);

        Passenger::factory()->create([
            'company_id'   => $companyA->id,
            'corporate_id' => $corporateA->id,
            'is_active'    => true,
        ]);

        $passengerB = Passenger::factory()->create([
            'company_id'   => $companyB->id,
            'corporate_id' => $corporateB->id,
            'is_active'    => true,
        ]);

        Sanctum::actingAs($superadmin);

        $indexResponse = $this->getJson('/api/passengers');
        $indexResponse->assertOk();
        $indexResponse->assertJsonCount(2, 'data');

        $createResponse = $this->postJson('/api/passengers', [
            'company_id'   => $companyA->id,
            'corporate_id' => $corporateA->id,
            'full_name'    => 'Super Passenger',
            'employee_code'=> 'EMP-SUP',
        ]);
        $createResponse->assertStatus(201);

        $this->assertDatabaseHas('passengers', [
            'full_name'    => 'Super Passenger',
            'company_id'   => $companyA->id,
            'corporate_id' => $corporateA->id,
        ]);

        $deleteResponse = $this->deleteJson("/api/passengers/{$passengerB->id}");
        $deleteResponse->assertStatus(204);

        $this->assertDatabaseHas('passengers', [
            'id'         => $passengerB->id,
            'company_id' => $companyB->id,
            'is_active'  => false,
        ]);
    }

    public function test_user_override_permission_allows_access_even_without_role_permission(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $corporate = Corporate::factory()->create([
            'company_id' => $company->id,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_USER,
        ]);

        $viewPassengers = Permission::where('name', 'view_passengers')->firstOrFail();

        $user->userPermissions()->create([
            'company_id'    => $company->id,
            'permission_id' => $viewPassengers->id,
        ]);

        Passenger::factory()->create([
            'company_id'   => $company->id,
            'corporate_id' => $corporate->id,
            'is_active'    => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/passengers');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }
}
