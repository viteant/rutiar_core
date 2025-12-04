<?php

namespace Auth;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityMiddlewaresTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_password_user_can_access_me_and_change_password_routes(): void
    {
        $company = Company::factory()->create([
            'code' => 'ACME-001',
            'is_active' => true,
        ]);

        $user = User::factory()
            ->forCompany($company)
            ->create([
                'role' => UserRole::COMPANY_USER,
                'is_active' => true,
                'must_change_password' => true,
                'password' => Hash::make('TempPass123!'),
            ]);

        $user->refresh();
        $this->assertTrue($user->must_change_password);

        $token = $user->createToken('phpunit')->plainTextToken;

        // /auth/me must be allowed
        $meResponse = $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $meResponse
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);

        // /auth/change-password must be allowed
        $changeResponse = $this->postJson(
            '/api/auth/change-password',
            [
                'current_password' => 'TempPass123!',
                'password' => 'NewPass123!',
                'password_confirmation' => 'NewPass123!',
            ],
            [
                'Authorization' => 'Bearer '.$token,
            ],
        );

        $changeResponse
            ->assertOk()
            ->assertJsonFragment([
                'message' => 'Password updated successfully.',
            ]);

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('NewPass123!', $user->password));
    }

    public function test_pending_password_user_is_blocked_from_protected_routes(): void
    {
        $company = Company::factory()->create([
            'code' => 'ACME-002',
            'is_active' => true,
        ]);

        $user = User::factory()
            ->forCompany($company)
            ->create([
                'role' => UserRole::COMPANY_USER,
                'is_active' => true,
                'must_change_password' => true,
                'password' => Hash::make('TempPass999!'),
            ]);

        $user->refresh();
        $this->assertTrue($user->must_change_password);

        $token = $user->createToken('phpunit')->plainTextToken;

        // /tenant-example must be blocked by EnsurePasswordIsChanged
        $tenantResponse = $this->getJson('/api/tenant-example', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $tenantResponse
            ->assertStatus(423)
            ->assertJsonFragment([
                'code' => 'PASSWORD_CHANGE_REQUIRED',
            ]);
    }

    public function test_inactive_user_is_blocked_even_with_valid_token(): void
    {
        $company = Company::factory()->create([
            'code' => 'ACME-003',
            'is_active' => true,
        ]);

        $user = User::factory()
            ->forCompany($company)
            ->create([
                'role' => UserRole::COMPANY_USER,
                'is_active' => false,
                'must_change_password' => false,
            ]);

        $token = $user->createToken('phpunit')->plainTextToken;

        $response = $this->getJson('/api/tenant-example', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response
            ->assertStatus(403)
            ->assertJsonFragment([
                'code' => 'USER_INACTIVE',
            ]);
    }

    public function test_company_inactive_blocks_access_even_if_user_is_active(): void
    {
        $company = Company::factory()->create([
            'code' => 'ACME-004',
            'is_active' => false,
        ]);

        $user = User::factory()
            ->forCompany($company)
            ->create([
                'role' => UserRole::COMPANY_USER,
                'is_active' => true,
                'must_change_password' => false,
            ]);

        $token = $user->createToken('phpunit')->plainTextToken;

        $response = $this->getJson('/api/tenant-example', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response
            ->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Company is not active or not assigned.',
            ]);
    }
}
