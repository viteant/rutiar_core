<?php

namespace Tests\Feature\Runs;

use App\Enums\RunStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\Route as TransportRoute;
use App\Models\RouteDefinition;
use App\Models\Run;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RunStatusActionsTest extends TestCase
{
    use RefreshDatabase;

    private const VIEW_PERMISSION        = 'view_runs';
    private const APPROVE_PERMISSION     = 'approve_run';
    private const CANCEL_PERMISSION      = 'cancel_run';
    private const FORCE_CLOSE_PERMISSION = 'force_close_run';

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

    protected function createRunWithStatus(Company $company, RunStatus $status): Run
    {
        $route = TransportRoute::factory()->for($company)->create();
        $partner = Partner::factory()->for($company)->create();

        $definition = RouteDefinition::factory()
            ->for($company)
            ->for($route, 'route')
            ->for($partner, 'partner')
            ->create();

        return Run::factory()
            ->for($company)
            ->for($definition, 'routeDefinition')
            ->create([
                'partner_id' => $partner->id,
                'status'     => $status,
            ]);
    }

    public function test_company_admin_with_approve_permission_can_approve_planned_run(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_PERMISSION,
            self::APPROVE_PERMISSION,
        ]);

        $company = $admin->company;
        $run = $this->createRunWithStatus($company, RunStatus::PLANNED);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/runs/' . $run->id . '/approve');

        $response->assertOk();

        $this->assertDatabaseHas('runs', [
            'id'     => $run->id,
            'status' => RunStatus::APPROVED->value,
        ]);
    }

    public function test_cannot_approve_non_planned_run(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_PERMISSION,
            self::APPROVE_PERMISSION,
        ]);

        $company = $admin->company;
        $run = $this->createRunWithStatus($company, RunStatus::COMPLETED);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/runs/' . $run->id . '/approve');

        $response->assertForbidden();
    }

    public function test_company_admin_with_cancel_permission_can_cancel_run(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_PERMISSION,
            self::CANCEL_PERMISSION,
        ]);

        $company = $admin->company;
        $run = $this->createRunWithStatus($company, RunStatus::APPROVED);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/runs/' . $run->id . '/cancel');

        $response->assertOk();

        $this->assertDatabaseHas('runs', [
            'id'     => $run->id,
            'status' => RunStatus::CANCELED->value,
        ]);
    }

    public function test_cannot_cancel_completed_run(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_PERMISSION,
            self::CANCEL_PERMISSION,
        ]);

        $company = $admin->company;
        $run = $this->createRunWithStatus($company, RunStatus::COMPLETED);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/runs/' . $run->id . '/cancel');

        $response->assertForbidden();
    }

    public function test_company_admin_with_force_close_permission_can_force_close_run(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_PERMISSION,
            self::FORCE_CLOSE_PERMISSION,
        ]);

        $company = $admin->company;
        $run = $this->createRunWithStatus($company, RunStatus::IN_PROGRESS);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/runs/' . $run->id . '/force-close');

        $response->assertOk();

        $this->assertDatabaseHas('runs', [
            'id'     => $run->id,
            'status' => RunStatus::FORCE_CLOSED->value,
        ]);
    }

    public function test_tenant_mismatch_on_status_actions_is_forbidden_for_non_superadmin(): void
    {
        $admin = $this->createCompanyAdminWithPermissions([
            self::VIEW_PERMISSION,
            self::APPROVE_PERMISSION,
            self::CANCEL_PERMISSION,
            self::FORCE_CLOSE_PERMISSION,
        ]);

        $otherCompany = Company::factory()->create();
        $run = $this->createRunWithStatus($otherCompany, RunStatus::PLANNED);

        Sanctum::actingAs($admin);

        $this->postJson('/api/runs/' . $run->id . '/approve')->assertForbidden();
        $this->postJson('/api/runs/' . $run->id . '/cancel')->assertForbidden();
        $this->postJson('/api/runs/' . $run->id . '/force-close')->assertForbidden();
    }

    public function test_superadmin_can_change_status_across_companies(): void
    {
        $superadmin = User::factory()->create([
            'company_id' => null,
            'role'       => UserRole::SUPERADMIN,
        ]);

        $company = Company::factory()->create();
        $run = $this->createRunWithStatus($company, RunStatus::PLANNED);

        Sanctum::actingAs($superadmin);

        $this->postJson('/api/runs/' . $run->id . '/approve')->assertOk();

        $this->assertDatabaseHas('runs', [
            'id'     => $run->id,
            'status' => RunStatus::APPROVED->value,
        ]);
    }
}
