Services
========

## app/Services/RolePermissionSyncService.php

Resumen
-------
Servicio que sincroniza los permisos por defecto (definidos en `config/role_permissions.php`) para una compañía dada. Es idempotente y evita duplicados.

Métodos
-------
- public function syncForCompany(Company $company): void
  - Lee `config('role_permissions.defaults')`.
  - Consulta la tabla `permissions` y hace un mapping nombre => id.
  - Para cada rol y lista de permission names en los defaults crea `RolePermission::firstOrCreate([...])` para la compañía dada.
  - Ignora entradas de configuración cuyo permiso no exista en la BD.

Notas
-----
- Se usa durante el onboarding (ej: al crear compañía con el comando `CreateCompanyAdmin`) para inicializar permisos por rol.
- Está cubierto por tests que verifican idempotencia y comportamiento ante permisos inexistentes.

