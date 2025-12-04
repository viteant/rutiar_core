<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Services\RolePermissionSyncService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_creates_role_permissions_for_company_from_config(): void
    {
        // Seed all permissions defined in config/permissions.php
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $company = Company::factory()->create();

        /** @var RolePermissionSyncService $service */
        $service = $this->app->make(RolePermissionSyncService::class);

        $service->syncForCompany($company);

        $defaults = config('role_permissions.defaults', []);

        $this->assertNotEmpty(
            $defaults,
            'role_permissions.defaults config must not be empty'
        );

        foreach ($defaults as $role => $permissionNames) {
            foreach ($permissionNames as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();

                // Si el permiso existe en DB, debe existir el RolePermission correspondiente
                if ($permission) {
                    $this->assertTrue(
                        RolePermission::query()
                            ->where('company_id', $company->id)
                            ->where('role', $role)
                            ->where('permission_id', $permission->id)
                            ->exists(),
                        "Expected role permission for role {$role} and permission {$permissionName}"
                    );
                }
            }
        }
    }

    public function test_sync_is_idempotent_and_does_not_duplicate_records(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $company = Company::factory()->create();

        /** @var RolePermissionSyncService $service */
        $service = $this->app->make(RolePermissionSyncService::class);

        $service->syncForCompany($company);
        $firstCount = RolePermission::query()->count();

        $service->syncForCompany($company);
        $secondCount = RolePermission::query()->count();

        $this->assertSame(
            $firstCount,
            $secondCount,
            'Role permissions sync should be idempotent for the same company.'
        );
    }

    public function test_sync_ignores_permissions_not_present_in_database(): void
    {
        // Forzamos un config temporal con un permiso inexistente
        config()->set('role_permissions.defaults', [
            'COMPANY_USER' => [
                'non_existing_permission',
            ],
        ]);

        $company = Company::factory()->create();

        /** @var RolePermissionSyncService $service */
        $service = $this->app->make(RolePermissionSyncService::class);

        $service->syncForCompany($company);

        $this->assertSame(
            0,
            RolePermission::query()->count(),
            'Role permissions should not be created for non-existing permissions.'
        );
    }
}
