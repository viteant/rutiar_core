<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_login_without_company_code(): void
    {
        $user = User::factory()
            ->superAdmin()
            ->create([
                'email' => 'superadmin@test.com',
                'password' => bcrypt('password'),
            ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'superadmin@test.com',
            'password' => 'password',
            'device_name' => 'phpunit',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
                'user' => [
                    'id',
                    'email',
                    'role',
                    'company',
                ],
            ])
            // En JSON viene el value del enum, no el enum en sí
            ->assertJsonPath('user.role', UserRole::SUPERADMIN->value)
            ->assertJsonPath('user.company', null);
    }

    public function test_company_user_can_login_with_valid_company_code(): void
    {
        $company = Company::factory()->create([
            'code' => 'ACME-001',
        ]);

        $user = User::factory()
            ->forCompany($company)
            ->create([
                'email' => 'admin@acme.test',
                'password' => bcrypt('password'),
                'role' => UserRole::COMPANY_ADMIN,
            ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@acme.test',
            'password' => 'password',
            'device_name' => 'phpunit',
            'company_code' => 'ACME-001',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.company.code', 'ACME-001')
            ->assertJsonStructure([
                'token',
                'token_type',
                'user' => [
                    'id',
                    'email',
                    'role',
                    'company' => ['id', 'name', 'code'],
                ],
            ]);
    }

    public function test_non_superadmin_requires_company_code(): void
    {
        $company = Company::factory()->create();

        $user = User::factory()
            ->forCompany($company)
            ->create([
                'email' => 'user@acme.test',
                'password' => bcrypt('password'),
                'role' => UserRole::COMPANY_USER,
            ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@acme.test',
            'password' => 'password',
            'device_name' => 'phpunit',
            // sin company_code
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'company_code is required.',
            ])
            ->assertJsonValidationErrors(['company_code']);
    }

    public function test_login_fails_with_invalid_company_code(): void
    {
        $company = Company::factory()->create([
            'code' => 'ACME-001',
        ]);

        $user = User::factory()
            ->forCompany($company)
            ->create([
                'email' => 'user@acme.test',
                'password' => bcrypt('password'),
                'role' => UserRole::COMPANY_USER,
            ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@acme.test',
            'password' => 'password',
            'device_name' => 'phpunit',
            'company_code' => 'WRONG-999',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['company_code']);
    }

    public function test_tenant_is_resolved_on_protected_route_for_company_user(): void
    {
        $company = Company::factory()->create([
            'code' => 'ACME-001',
        ]);

        $user = User::factory()
            ->forCompany($company)
            ->create([
                'role' => UserRole::COMPANY_USER,
            ]);

        $token = $user->createToken('phpunit')->plainTextToken;

        $response = $this->getJson('/api/tenant-example', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonPath('tenant.id', $company->id)
            ->assertJsonPath('tenant.code', $company->code);
    }

    public function test_superadmin_has_null_tenant_on_protected_route(): void
    {
        $user = User::factory()
            ->superAdmin()
            ->create();

        $token = $user->createToken('phpunit')->plainTextToken;

        $response = $this->getJson('/api/tenant-example', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonPath('tenant', null);
    }

    public function test_logout_revokes_current_token(): void
    {
        $company = Company::factory()->create();

        $user = User::factory()
            ->forCompany($company)
            ->create();

        $token = $user->createToken('phpunit')->plainTextToken;

        $logoutResponse = $this->postJson('/api/auth/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $logoutResponse
            ->assertOk()
            ->assertJsonFragment([
                'message' => 'Logged out.',
            ]);

        // Confirma que ya no hay tokens para este usuario
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // En tests hay que limpiar el guard de Sanctum para que deje de cachear el user
        app('auth')->guard('sanctum')->forgetUser();

        $afterResponse = $this->getJson('/api/tenant-example', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $afterResponse->assertStatus(401);
    }
}
