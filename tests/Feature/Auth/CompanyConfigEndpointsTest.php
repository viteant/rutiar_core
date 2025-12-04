<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\CompanyConfig;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyConfigEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_with_permission_can_view_config(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_ADMIN,
        ]);

        $viewSettingsPermission = Permission::where('name', 'view_company_settings')->firstOrFail();

        RolePermission::create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_ADMIN->value,
            'permission_id' => $viewSettingsPermission->id,
        ]);

        CompanyConfig::create([
            'company_id' => $company->id,
            'planning_cutoff_time' => '18:00:00',
            'default_waiting_minutes' => 5,
            'allow_driver_reorder' => true,
            'driver_quota_default' => 30,
            'settings' => [],
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/company/config');

        $response->assertOk();
        $response->assertJsonFragment([
            'planning_cutoff_time' => '18:00:00',
            'default_waiting_minutes' => 5,
            'allow_driver_reorder' => true,
            'driver_quota_default' => 30,
        ]);
    }

    public function test_company_user_without_permission_cannot_view_config(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_USER,
        ]);

        CompanyConfig::factory()->create([
            'company_id' => $company->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/company/config');

        $response->assertStatus(403);
    }

    public function test_company_admin_with_permission_can_update_config(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_ADMIN,
        ]);

        $updateSettingsPermission = Permission::where('name', 'update_company_settings')->firstOrFail();

        RolePermission::create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_ADMIN->value,
            'permission_id' => $updateSettingsPermission->id,
        ]);

        CompanyConfig::create([
            'company_id' => $company->id,
            'planning_cutoff_time' => '18:00:00',
            'default_waiting_minutes' => 5,
            'allow_driver_reorder' => true,
            'driver_quota_default' => 30,
            'settings' => [],
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/company/config', [
            'planning_cutoff_time' => '19:30',
            'default_waiting_minutes' => 10,
            'allow_driver_reorder' => false,
            'driver_quota_default' => 40,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('company_configs', [
            'company_id' => $company->id,
            'planning_cutoff_time' => '19:30:00',
            'default_waiting_minutes' => 10,
            'allow_driver_reorder' => false,
            'driver_quota_default' => 40,
        ]);
    }

    public function test_superadmin_cannot_update_company_config(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $superadmin = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::SUPERADMIN,
        ]);

        CompanyConfig::create([
            'company_id' => $company->id,
        ]);

        Sanctum::actingAs($superadmin);

        $response = $this->putJson('/api/company/config', [
            'planning_cutoff_time' => '19:00',
        ]);

        $response->assertStatus(403);
    }
}
