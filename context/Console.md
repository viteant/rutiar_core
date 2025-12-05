# Console/Commands/CreateCompanyAdmin.php

*Comando para crear una compañía y un usuario COMPANY_ADMIN con contraseña temporal desde consola.*

Funciones:
- `__construct(RolePermissionSyncService $rolePermissionSyncService): void` - Inyecta el servicio de sincronización de permisos por rol para compañías y llama al constructor padre de `Command`.
- `handle(): int` - Flujo principal del comando: pregunta datos de compañía y administrador (nombre, código, email, timezone), muestra resumen, pide confirmación, crea `Company`, `CompanyConfig` y `User` en una transacción, llama a `RolePermissionSyncService::syncForCompany`, envía `CompanyAdminInvitationMail` con la contraseña temporal y muestra información y advertencias por consola. Retorna `Command::SUCCESS` o `Command::FAILURE` según el resultado.
- `askRequired(string $question): string` - Envuelve `$this->ask()` forzando que la respuesta no sea vacía; en caso contrario muestra error y vuelve a preguntar.
- `askUniqueCompanyCode(): string` - Solicita un código único de compañía, validando contra la tabla `companies` que no exista ya un registro con ese código; repite la pregunta si el código está duplicado.
- `askUniqueEmail(string $question): string` - Solicita un email válido y único: valida formato mediante `filter_var` y unicidad en la tabla `users` por el campo `email`; si no es válido o está repetido, vuelve a pedir el email.

