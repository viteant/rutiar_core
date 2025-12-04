<?php

namespace Tests\Feature\Corporates;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\CompanyConfig;
use App\Models\Corporate;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CorporateCrudTest extends TestCase
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

    public function test_company_admin_with_permission_can_list_corporates(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'view_corporates');

        Corporate::factory()->count(2)->create([
            'company_id' => $company->id,
            'is_active'  => true,
        ]);

        // otro tenant para aislamiento
        $otherCompany = $this->createCompanyWithConfig();
        Corporate::factory()->create([
            'company_id' => $otherCompany->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/corporates');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_company_user_without_permission_cannot_list_corporates(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_USER,
        ]);

        Corporate::factory()->count(2)->create([
            'company_id' => $company->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/corporates');

        $response->assertStatus(403);
    }

    public function test_company_admin_with_permission_can_create_corporate_respecting_tenant(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'create_corporate');

        Sanctum::actingAs($admin);

        $payload = [
            'name'          => 'Acme Corp',
            'tax_id'        => '1234567890',
            'contact_name'  => 'John Manager',
            'contact_email' => 'john@example.com',
        ];

        $response = $this->postJson('/api/corporates', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('corporates', [
            'company_id'    => $company->id,
            'name'          => 'Acme Corp',
            'tax_id'        => '1234567890',
            'contact_name'  => 'John Manager',
            'contact_email' => 'john@example.com',
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

        $this->grantRolePermission($companyA, UserRole::COMPANY_ADMIN, 'view_corporates');

        $corporateB = Corporate::factory()->create([
            'company_id' => $companyB->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($adminA);

        $response = $this->getJson("/api/corporates/{$corporateB->id}");

        $response->assertStatus(403);
    }

    public function test_company_admin_with_permission_can_update_corporate(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'update_corporate');

        $corporate = Corporate::factory()->create([
            'company_id' => $company->id,
            'name'       => 'Old Name',
        ]);

        Sanctum::actingAs($admin);

        $payload = [
            'name'          => 'New Name',
            'contact_name'  => 'Jane Updated',
            'contact_email' => 'jane@example.com',
        ];

        $response = $this->putJson("/api/corporates/{$corporate->id}", $payload);

        $response->assertOk();

        $this->assertDatabaseHas('corporates', [
            'id'            => $corporate->id,
            'company_id'    => $company->id,
            'name'          => 'New Name',
            'contact_name'  => 'Jane Updated',
            'contact_email' => 'jane@example.com',
        ]);
    }

    public function test_company_admin_with_deactivate_permission_deactivates_corporate_instead_of_deleting(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'deactivate_corporate');

        $corporate = Corporate::factory()->create([
            'company_id' => $company->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/corporates/{$corporate->id}");

        $response->assertStatus(204);

        $this->assertDatabaseHas('corporates', [
            'id'         => $corporate->id,
            'company_id' => $company->id,
            'is_active'  => false,
        ]);
    }

    public function test_superadmin_can_manage_corporates_across_companies(): void
    {
        $this->seed(PermissionSeeder::class);

        $companyA = $this->createCompanyWithConfig();
        $companyB = $this->createCompanyWithConfig();

        $superadmin = User::factory()->create([
            'company_id' => null,
            'role'       => UserRole::SUPERADMIN,
        ]);

        Corporate::factory()->create([
            'company_id' => $companyA->id,
            'is_active'  => true,
        ]);
        $corporateB = Corporate::factory()->create([
            'company_id' => $companyB->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($superadmin);

        $indexResponse = $this->getJson('/api/corporates');
        $indexResponse->assertOk();
        $indexResponse->assertJsonCount(2, 'data');

        $createResponse = $this->postJson('/api/corporates', [
            'company_id'    => $companyA->id,
            'name'          => 'Superadmin Corporate',
            'tax_id'        => '9999999999',
            'contact_name'  => 'Boss',
            'contact_email' => 'boss@example.com',
        ]);
        $createResponse->assertStatus(201);

        $this->assertDatabaseHas('corporates', [
            'name'       => 'Superadmin Corporate',
            'company_id' => $companyA->id,
        ]);

        $deleteResponse = $this->deleteJson("/api/corporates/{$corporateB->id}");
        $deleteResponse->assertStatus(204);

        $this->assertDatabaseHas('corporates', [
            'id'         => $corporateB->id,
            'company_id' => $companyB->id,
            'is_active'  => false,
        ]);
    }

    public function test_user_override_permission_allows_access_even_without_role_permission(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = $this->createCompanyWithConfig();

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_USER,
        ]);

        $viewCorporates = Permission::where('name', 'view_corporates')->firstOrFail();

        $user->userPermissions()->create([
            'company_id'    => $company->id,
            'permission_id' => $viewCorporates->id,
        ]);

        Corporate::factory()->create([
            'company_id' => $company->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/corporates');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }
}
