<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_always_has_any_permission()
    {
        $company = Company::factory()->create();

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::SUPERADMIN,
        ]);

        $permission = Permission::create([
            'name' => 'view_partners',
        ]);

        $this->assertTrue($user->hasPermission('view_partners'));
        $this->assertTrue($user->hasPermission('some_random_permission'));
    }

    public function test_role_permissions_are_applied_when_no_user_override()
    {
        $company = Company::factory()->create();

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_USER,
        ]);

        $permission = Permission::create([
            'name' => 'view_partners',
        ]);

        RolePermission::create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_USER->value,
            'permission_id' => $permission->id,
        ]);

        $this->assertTrue($user->hasPermission('view_partners'));
        $this->assertFalse($user->hasPermission('create_partner'));
    }

    public function test_user_specific_permissions_override_role_permissions()
    {
        $company = Company::factory()->create();

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_USER,
        ]);

        $permission = Permission::create([
            'name' => 'view_invoices',
        ]);

        // No role permission for this permission
        $this->assertFalse($user->hasPermission('view_invoices'));

        // Add user-specific permission
        UserPermission::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'permission_id' => $permission->id,
        ]);

        $this->assertTrue($user->hasPermission('view_invoices'));
    }

    public function test_permission_is_denied_when_not_assigned_anywhere()
    {
        $company = Company::factory()->create();

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_USER,
        ]);

        Permission::create([
            'name' => 'manage_route_definitions',
        ]);

        $this->assertFalse($user->hasPermission('manage_route_definitions'));
    }

    public function test_permissions_are_tenant_scoped()
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $user = User::factory()->create([
            'company_id' => $companyA->id,
            'role' => UserRole::COMPANY_USER,
        ]);

        $permission = Permission::create([
            'name' => 'view_partners',
        ]);

        // Role permission in another tenant
        RolePermission::create([
            'company_id' => $companyB->id,
            'role' => UserRole::COMPANY_USER->value,
            'permission_id' => $permission->id,
        ]);

        $this->assertFalse($user->hasPermission('view_partners'));

        // Now assign permission in the correct tenant
        RolePermission::create([
            'company_id' => $companyA->id,
            'role' => UserRole::COMPANY_USER->value,
            'permission_id' => $permission->id,
        ]);

        $this->assertTrue($user->hasPermission('view_partners'));
    }

}
