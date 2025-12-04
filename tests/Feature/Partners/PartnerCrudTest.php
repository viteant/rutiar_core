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

    public function test_company_admin_with_permission_can_list_partners(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_ADMIN,
        ]);

        $viewPartners = Permission::where('name', 'view_partners')->firstOrFail();

        RolePermission::create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_ADMIN->value,
            'permission_id' => $viewPartners->id,
        ]);

        Partner::factory()->count(2)->create([
            'company_id' => $company->id,
        ]);

        // Otro tenant para asegurar aislamiento
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
            'role' => UserRole::COMPANY_USER,
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
            'role' => UserRole::COMPANY_ADMIN,
        ]);

        $createPartner = Permission::where('name', 'create_partner')->firstOrFail();

        RolePermission::create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_ADMIN->value,
            'permission_id' => $createPartner->id,
        ]);

        Sanctum::actingAs($admin);

        $payload = [
            'name' => 'Partner Test',
            'code' => 'PT01',
            'tax_id' => '1234567890',
            'driver_quota' => 10,
        ];

        $response = $this->postJson('/api/partners', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('partners', [
            'company_id' => $company->id,
            'name' => 'Partner Test',
            'code' => 'PT01',
            'tax_id' => '1234567890',
            'driver_quota' => 10,
        ]);
    }

    public function test_tenant_mismatch_on_show_is_forbidden_for_non_superadmin(): void
    {
        $this->seed(PermissionSeeder::class);

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->create([
            'company_id' => $companyA->id,
            'role' => UserRole::COMPANY_ADMIN,
        ]);

        $viewPartners = Permission::where('name', 'view_partners')->firstOrFail();

        RolePermission::create([
            'company_id' => $companyA->id,
            'role' => UserRole::COMPANY_ADMIN->value,
            'permission_id' => $viewPartners->id,
        ]);

        $partnerB = Partner::factory()->create([
            'company_id' => $companyB->id,
        ]);

        Sanctum::actingAs($userA);

        $response = $this->getJson("/api/partners/{$partnerB->id}");

        $response->assertStatus(403);
    }

    public function test_superadmin_can_manage_partners_across_companies(): void
    {
        $this->seed(PermissionSeeder::class);

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $superadmin = User::factory()->create([
            'company_id' => null,
            'role' => UserRole::SUPERADMIN,
        ]);

        Partner::factory()->create(['company_id' => $companyA->id]);
        Partner::factory()->create(['company_id' => $companyB->id]);

        Sanctum::actingAs($superadmin);

        $indexResponse = $this->getJson('/api/partners');

        $indexResponse->assertOk();
        $indexResponse->assertJsonCount(2, 'data');

        $createResponse = $this->postJson('/api/partners', [
            'company_id' => $companyA->id,
            'name' => 'Superadmin Partner',
            'code' => 'SUPER-P01',
        ]);

        $createResponse->assertStatus(201);

        $this->assertDatabaseHas('partners', [
            'name' => 'Superadmin Partner',
            'company_id' => $companyA->id,
        ]);
    }

    public function test_user_override_permission_allows_access_even_without_role_permission(): void
    {
        $this->seed(PermissionSeeder::class);

        $company = Company::factory()->create();

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::COMPANY_USER,
        ]);

        $viewPartners = Permission::where('name', 'view_partners')->firstOrFail();

        // No role_permission para COMPANY_USER

        // Override a nivel de usuario
        $user->userPermissions()->create([
            'company_id' => $company->id,
            'permission_id' => $viewPartners->id,
        ]);

        Partner::factory()->count(1)->create([
            'company_id' => $company->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/partners');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }
}
