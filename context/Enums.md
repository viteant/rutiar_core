# Enums/UserRole.php

*Enum que define los distintos roles de usuario en la plataforma.*

Funciones:
- `SUPERADMIN` - Rol de superadministrador con acceso global (ignora restricciones de tenant y permisos en `User::hasPermission`).
- `COMPANY_ADMIN` - Rol de administrador de compañía (gestiona configuración, permisos, entidades de su compañía).
- `COMPANY_USER` - Rol de usuario de compañía con permisos limitados de consulta/operación.
- `PARTNER_ADMIN` - Rol de administrador de partner (gestiona drivers y vehículos de su partner).
- `DRIVER` - Rol de conductor (oriendado a uso en app móvil/endpoints específicos).
- `isSuperAdmin(): bool` - Devuelve `true` si el valor actual del enum es `SUPERADMIN`.
- `isCompanyRole(): bool` - Devuelve `true` si el valor actual es `COMPANY_ADMIN` o `COMPANY_USER`.
- `isPartnerRole(): bool` - Devuelve `true` si el valor actual es `PARTNER_ADMIN`.
- `isDriver(): bool` - Devuelve `true` si el valor actual es `DRIVER`.
