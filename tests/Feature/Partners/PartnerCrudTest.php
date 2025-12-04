<?php

namespace Tests\Feature\Partners;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PartnerCrudTest extends TestCase
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

    public function test_company_admin_with_permission_can_list_partners(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'view_partners');

        Partner::factory()->count(2)->create([
            'company_id' => $company->id,
        ]);

        // Another tenant to ensure isolation
        Partner::factory()->create([
            'company_id' => Company::factory()->create()->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/partners');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_company_user_without_permission_cannot_list_partners(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_USER,
        ]);

        Partner::factory()->count(2)->create([
            'company_id' => $company->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/partners');

        $response->assertStatus(403);
    }

    public function test_company_admin_with_permission_can_create_partner(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'create_partner');

        Sanctum::actingAs($admin);

        $payload = [
            'name'         => 'Partner Test',
            'code'         => 'PT01',
            'tax_id'       => '1234567890',
            'driver_quota' => 10,
        ];

        $response = $this->postJson('/api/partners', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('partners', [
            'company_id'   => $company->id,
            'name'         => 'Partner Test',
            'code'         => 'PT01',
            'tax_id'       => '1234567890',
            'driver_quota' => 10,
        ]);
    }

    public function test_company_user_without_create_permission_cannot_create_partner(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_USER,
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Unauthorized Partner',
            'code' => 'UP01',
        ];

        $response = $this->postJson('/api/partners', $payload);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('partners', [
            'company_id' => $company->id,
            'name'       => 'Unauthorized Partner',
        ]);
    }

    public function test_company_admin_with_permission_can_show_partner(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'view_partners');

        $partner = Partner::factory()->create([
            'company_id' => $company->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/partners/{$partner->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $partner->id);
    }

    public function test_tenant_mismatch_on_show_is_forbidden_for_non_superadmin(): void
    {
        $this->seed(PermissionSeeder::class);

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->create([
            'company_id' => $companyA->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($companyA, UserRole::COMPANY_ADMIN, 'view_partners');

        $partnerB = Partner::factory()->create([
            'company_id' => $companyB->id,
        ]);

        Sanctum::actingAs($userA);

        $response = $this->getJson("/api/partners/{$partnerB->id}");

        $response->assertStatus(403);
    }

    public function test_company_admin_with_update_permission_can_update_partner(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'update_partner');

        $partner = Partner::factory()->create([
            'company_id' => $company->id,
            'name'       => 'Old Name',
            'code'       => 'OLD',
            'driver_quota' => 5,
        ]);

        Sanctum::actingAs($admin);

        $payload = [
            'name'         => 'New Name',
            'code'         => 'NEW',
            'driver_quota' => 20,
        ];

        $response = $this->putJson("/api/partners/{$partner->id}", $payload);

        $response->assertOk();

        $this->assertDatabaseHas('partners', [
            'id'           => $partner->id,
            'company_id'   => $company->id,
            'name'         => 'New Name',
            'code'         => 'NEW',
            'driver_quota' => 20,
        ]);
    }

    public function test_company_user_without_update_permission_cannot_update_partner(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_USER,
        ]);

        $partner = Partner::factory()->create([
            'company_id' => $company->id,
            'name'       => 'Name Before',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/partners/{$partner->id}", [
            'name' => 'Name After',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('partners', [
            'id'         => $partner->id,
            'name'       => 'Name Before',
        ]);
    }

    public function test_tenant_mismatch_on_update_is_forbidden_for_non_superadmin(): void
    {
        $this->seed(PermissionSeeder::class);

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $adminA = User::factory()->create([
            'company_id' => $companyA->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($companyA, UserRole::COMPANY_ADMIN, 'update_partner');

        $partnerB = Partner::factory()->create([
            'company_id' => $companyB->id,
            'name'       => 'Partner B',
        ]);

        Sanctum::actingAs($adminA);

        $response = $this->putJson("/api/partners/{$partnerB->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('partners', [
            'id'   => $partnerB->id,
            'name' => 'Partner B',
        ]);
    }

    public function test_company_admin_with_delete_permission_deactivates_partner_instead_of_deleting(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($company, UserRole::COMPANY_ADMIN, 'delete_partner');

        $partner = Partner::factory()->create([
            'company_id' => $company->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/partners/{$partner->id}");

        $response->assertStatus(204);

        $this->assertDatabaseHas('partners', [
            'id'        => $partner->id,
            'company_id'=> $company->id,
            'is_active' => false,
        ]);
    }

    public function test_tenant_mismatch_on_delete_is_forbidden_for_non_superadmin(): void
    {
        $this->seed(PermissionSeeder::class);

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $adminA = User::factory()->create([
            'company_id' => $companyA->id,
            'role'       => UserRole::COMPANY_ADMIN,
        ]);

        $this->grantRolePermission($companyA, UserRole::COMPANY_ADMIN, 'delete_partner');

        $partnerB = Partner::factory()->create([
            'company_id' => $companyB->id,
            'is_active'  => true,
        ]);

        Sanctum::actingAs($adminA);

        $response = $this->deleteJson("/api/partners/{$partnerB->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('partners', [
            'id'        => $partnerB->id,
            'company_id'=> $companyB->id,
            'is_active' => true,
        ]);
    }

    public function test_superadmin_can_manage_partners_across_companies(): void
    {
        $this->seed(PermissionSeeder::class);

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $superadmin = User::factory()->create([
            'company_id' => null,
            'role'       => UserRole::SUPERADMIN,
        ]);

        $partnerA = Partner::factory()->create(['company_id' => $companyA->id]);
        $partnerB = Partner::factory()->create(['company_id' => $companyB->id]);

        Sanctum::actingAs($superadmin);

        $indexResponse = $this->getJson('/api/partners');
        $indexResponse->assertOk();
        $indexResponse->assertJsonCount(2, 'data');

        $createResponse = $this->postJson('/api/partners', [
            'company_id' => $companyA->id,
            'name'       => 'Superadmin Partner',
            'code'       => 'SUPER-P01',
        ]);
        $createResponse->assertStatus(201);

        $this->assertDatabaseHas('partners', [
            'name'       => 'Superadmin Partner',
            'company_id' => $companyA->id,
        ]);

        $deleteResponse = $this->deleteJson("/api/partners/{$partnerB->id}");
        $deleteResponse->assertStatus(204);

        $this->assertDatabaseHas('partners', [
            'id'        => $partnerB->id,
            'company_id'=> $companyB->id,
            'is_active' => false,
        ]);
    }

    public function test_user_override_permission_allows_access_even_without_role_permission(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role'       => UserRole::COMPANY_USER,
        ]);

        $viewPartners = Permission::where('name', 'view_partners')->firstOrFail();

        // No role_permission for COMPANY_USER

        // Override at user level
        $user->userPermissions()->create([
            'company_id'    => $company->id,
            'permission_id' => $viewPartners->id,
        ]);

        Partner::factory()->create([
            'company_id' => $company->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/partners');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }
}
