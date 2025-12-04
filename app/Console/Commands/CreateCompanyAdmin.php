<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Mail\CompanyAdminInvitationMail;
use App\Models\Company;
use App\Models\CompanyConfig;
use App\Models\User;
use App\Services\RolePermissionSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateCompanyAdmin extends Command
{
    protected $signature = 'rutiar:create-company-admin';

    protected $description = 'Crea una compañía y un usuario COMPANY_ADMIN con contraseña temporal';

    public function __construct(
        protected RolePermissionSyncService $rolePermissionSyncService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Creación de nueva compañía y usuario COMPANY_ADMIN');
        $this->newLine();

        // Company name
        $companyName = $this->askRequired('Nombre de la compañía');

        // Company code (unique)
        $companyCode = $this->askUniqueCompanyCode();

        // Admin name
        $adminName = $this->askRequired('Nombre completo del administrador');

        // Admin email
        $adminEmail = $this->askUniqueEmail('Correo del administrador (email de acceso)');

        // Timezone (simple por ahora, fijo)
        $timezone = $this->anticipate(
            'Zona horaria (enter para usar America/Guayaquil)',
            ['America/Guayaquil'],
            'America/Guayaquil'
        );

        $this->newLine();
        $this->info('Resumen de datos:');
        $this->line(" - Compañía: {$companyName}");
        $this->line(" - Código: {$companyCode}");
        $this->line(" - Admin: {$adminName}");
        $this->line(" - Email: {$adminEmail}");
        $this->line(" - Timezone: {$timezone}");

        $this->newLine();
        if (! $this->confirm('¿Deseas continuar con la creación?', true)) {
            $this->warn('Operación cancelada. No se creó nada.');
            return self::SUCCESS;
        }

        $temporaryPassword = Str::random(12);

        DB::beginTransaction();

        try {
            /** @var Company $company */
            $company = Company::create([
                'name' => $companyName,
                'code' => $companyCode,
                'country' => 'EC',
                'timezone' => $timezone,
                'is_active' => true,
            ]);

            CompanyConfig::create([
                'company_id' => $company->id,
                'planning_cutoff_time' => '18:00:00',
                'default_waiting_minutes' => 5,
                'max_drivers_per_partner' => 0,
                'allow_driver_reorder' => true,
                'settings' => [],
            ]);

            /** @var User $user */
            $user = User::create([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => Hash::make($temporaryPassword),
                'company_id' => $company->id,
                'role' => UserRole::COMPANY_ADMIN,
                'is_active' => true,
                'must_change_password' => true,
            ]);

            $this->rolePermissionSyncService->syncForCompany($company);

            Mail::to($user->email)->send(
                new CompanyAdminInvitationMail($company, $user, $temporaryPassword)
            );

            DB::commit();


            $this->newLine();
            $this->info('Compañía y usuario COMPANY_ADMIN creados correctamente.');
            $this->line(" - ID compañía: {$company->id}");
            $this->line(" - Código compañía: {$company->code}");
            $this->line(" - Email admin: {$user->email}");

            $this->newLine();
            $this->warn('IMPORTANTE:');
            $this->line('Se ha enviado un correo al administrador con la contraseña temporal.');
            $this->line('En caso de problemas con el correo, esta es la contraseña temporal:');
            $this->line(" >> {$temporaryPassword}");

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Error al crear la compañía/administrador: '.$e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function askRequired(string $question): string
    {
        do {
            $value = trim((string) $this->ask($question));
            if ($value === '') {
                $this->error('Este campo es obligatorio. Inténtalo de nuevo.');
            }
        } while ($value === '');

        return $value;
    }

    protected function askUniqueCompanyCode(): string
    {
        do {
            $code = trim((string) $this->askRequired('Código de la compañía (debe ser único, ej: ACME-001)'));

            if (Company::where('code', $code)->exists()) {
                $this->error("Ya existe una compañía con el código \"{$code}\". Usa otro.");
                $code = '';
            }
        } while ($code === '');

        return $code;
    }

    protected function askUniqueEmail(string $question): string
    {
        do {
            $email = trim((string) $this->askRequired($question));

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error('El formato del correo no es válido. Inténtalo de nuevo.');
                $email = '';
                continue;
            }

            if (User::where('email', $email)->exists()) {
                $this->error("Ya existe un usuario con el correo \"{$email}\". Usa otro.");
                $email = '';
            }
        } while ($email === '');

        return $email;
    }
}
