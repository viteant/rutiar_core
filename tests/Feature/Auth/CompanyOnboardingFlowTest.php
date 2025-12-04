<?php

namespace Auth;

use App\Enums\UserRole;
use App\Mail\CompanyAdminInvitationMail;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CompanyOnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_company_onboarding_and_password_change_flow(): void
    {
        // 1) Mock de Mail para no enviar correos reales
        Mail::fake();

        $companyName = 'ACME Logistics';
        $companyCode = 'ACME-001';
        $adminName = 'Jane Doe';
        $adminEmail = 'jane.doe@acme.test';

        // 2) Ejecutar el comando CLI como lo haría un junior (respondiendo preguntas)
        $this->artisan('rutiar:create-company-admin')
            ->expectsOutput('Creación de nueva compañía y usuario COMPANY_ADMIN')
            ->expectsQuestion('Nombre de la compañía', $companyName)
            ->expectsQuestion('Código de la compañía (debe ser único, ej: ACME-001)', $companyCode)
            ->expectsQuestion('Nombre completo del administrador', $adminName)
            ->expectsQuestion('Correo del administrador (email de acceso)', $adminEmail)
            ->expectsQuestion('Zona horaria (enter para usar America/Guayaquil)', 'America/Guayaquil')
            ->expectsConfirmation('¿Deseas continuar con la creación?', 'yes')
            ->assertExitCode(0);

        // 3) Verificar que la compañía se creó correctamente
        $company = Company::where('code', $companyCode)->first();
        $this->assertNotNull($company);
        $this->assertSame($companyName, $company->name);
        $this->assertSame('America/Guayaquil', $company->timezone);
        $this->assertTrue($company->is_active);

        // 4) Verificar que el usuario COMPANY_ADMIN se creó correctamente
        $user = User::where('email', $adminEmail)->first();
        $this->assertNotNull($user);
        $this->assertSame($adminName, $user->name);
        $this->assertSame($company->id, $user->company_id);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->must_change_password);

        // Normaliza el rol a string y compara el value del Enum
        $roleValue = $user->role instanceof UserRole
            ? $user->role->value
            : $user->role;

        $this->assertSame(UserRole::COMPANY_ADMIN->value, $roleValue);

        // 5) Verificar que se envió el correo al admin
        Mail::assertSent(CompanyAdminInvitationMail::class, function (CompanyAdminInvitationMail $mail) use ($company, $user) {
            return $mail->hasTo($user->email)
                && $mail->company->is($company)
                && $mail->user->is($user);
        });

        // 6) Para poder probar el login real, necesitamos conocer la contraseña temporal.
        //    Como el comando la generó internamente, aquí simulamos el escenario:
        //    forzamos una nueva contraseña temporal que sí controlamos.
        $temporaryPassword = 'TempPass123!';
        $user->password = Hash::make($temporaryPassword);
        $user->must_change_password = true;
        $user->save();

        // 7) Login con la contraseña temporal
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $adminEmail,
            'password' => $temporaryPassword,
            'device_name' => 'phpunit',
            'company_code' => $companyCode,
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
                'user' => [
                    'id',
                    'email',
                    'role',
                    'must_change_password',
                    'company' => ['id', 'name', 'code'],
                ],
            ])
            ->assertJsonPath('user.must_change_password', true)
            ->assertJsonPath('user.company.code', $companyCode);

        $token = $loginResponse->json('token');
        $this->assertNotEmpty($token);

        // 8) Con el token, el usuario debería poder ver /auth/me
        $meResponse = $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $meResponse
            ->assertOk()
            ->assertJsonPath('user.email', $adminEmail);

        // 9) Pero NO debería poder acceder a rutas protegidas normales (por el middleware de cambio de password)
        $tenantResponse = $this->getJson('/api/tenant-example', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $tenantResponse
            ->assertStatus(423)
            ->assertJsonFragment([
                'code' => 'PASSWORD_CHANGE_REQUIRED',
            ]);

        // 10) Cambiar la contraseña usando /auth/change-password
        $newPassword = 'NewSecurePass123!';

        $changePasswordResponse = $this->postJson('/api/auth/change-password', [
            'current_password' => $temporaryPassword,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $changePasswordResponse
            ->assertOk()
            ->assertJsonFragment([
                'message' => 'Password updated successfully.',
            ]);

        // Refrescar el modelo desde BD
        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check($newPassword, $user->password));

        // 11) Ahora el middleware debería dejarle acceder a rutas protegidas
        $tenantResponseAfter = $this->getJson('/api/tenant-example', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $tenantResponseAfter
            ->assertOk()
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonPath('tenant.id', $company->id)
            ->assertJsonPath('tenant.code', $company->code);
    }
}
