<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyPermissionsEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_view_available_permissions(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_ADMIN,
        ]);

        // Darle permiso para gestionar role permissions
        $manageRolePerm = Permission::where('name', 'manage_company_role_permissions')->firstOrFail();

        RolePermission::create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_ADMIN->value,
            'permission_id' => $manageRolePerm->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/company/permissions/available');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                ['id', 'name'],
            ],
        ]);
    }

    public function test_company_user_without_manage_permission_cannot_access_role_permissions(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_USER,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/company/permissions/roles');

        $response->assertStatus(403);
    }

    public function test_company_admin_can_update_role_permissions_for_company_user_role(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_ADMIN,
        ]);

        $manageRolePerm = Permission::where('name', 'manage_company_role_permissions')->firstOrFail();
        $viewPartners = Permission::where('name', 'view_partners')->firstOrFail();

        // Dar permiso de gestión al admin
        RolePermission::create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_ADMIN->value,
            'permission_id' => $manageRolePerm->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/company/permissions/roles/COMPANY_USER', [
            'permissions' => [
                'view_partners',
            ],
        ]);

        $response->assertOk();

        $this->assertTrue(
            RolePermission::query()
                ->where('company_id', $company->id)
                ->where('role', UserRole::COMPANY_USER->value)
                ->where('permission_id', $viewPartners->id)
                ->exists()
        );
    }

    public function test_company_admin_can_view_and_update_user_specific_permissions(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_ADMIN,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_USER,
        ]);

        $manageUserPerm = Permission::where('name', 'manage_company_user_permissions')->firstOrFail();
        $viewBilling = Permission::where('name', 'view_billing')->firstOrFail();

        // Dar permiso de gestión al admin
        RolePermission::create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_ADMIN->value,
            'permission_id' => $manageUserPerm->id,
        ]);

        Sanctum::actingAs($admin);

        // Update overrides
        $updateResponse = $this->putJson("/api/company/permissions/users/{$user->id}", [
            'permissions' => [
                'view_billing',
            ],
        ]);

        $updateResponse->assertOk();

        // Show user permissions
        $showResponse = $this->getJson("/api/company/permissions/users/{$user->id}");

        $showResponse->assertOk();
        $showResponse->assertJsonFragment([
            'user_id' => $user->id,
        ]);

        $showResponse->assertJsonFragment([
            'user_permissions' => ['view_billing'],
        ]);
    }

    public function test_superadmin_cannot_manage_company_permissions(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $superadmin = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::SUPERADMIN,
        ]);

        Sanctum::actingAs($superadmin);

        $response = $this->getJson('/api/company/permissions/roles');

        $response->assertStatus(403);
    }
}
