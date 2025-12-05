# Services/RolePermissionSyncService.php

*Servicio de dominio encargado de sincronizar los permisos por rol (`RolePermission`) de una compañía a partir de la configuración global.*

Funciones:
- `syncForCompany(Company $company): void` - Lee la configuración `config('role_permissions.defaults')`, obtiene un mapa `name => id` de la tabla `permissions`, y para cada rol y lista de nombres de permisos crea registros `RolePermission` que no existan todavía (`firstOrCreate`). Ignora cualquier permiso configurado que no exista en base de datos. Es idempotente: ejecutar varias veces para la misma compañía no duplica registros.
