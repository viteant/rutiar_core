Enums
=====

## app/Enums/UserRole.php

Resumen
-------
Enum `UserRole` que modela los roles de usuario del sistema. Está tipado como string.

Valores (cases)
---------------
- SUPERADMIN = 'SUPERADMIN'
- COMPANY_ADMIN = 'COMPANY_ADMIN'
- COMPANY_USER = 'COMPANY_USER'
- PARTNER_ADMIN = 'PARTNER_ADMIN'
- DRIVER = 'DRIVER'

Métodos
-------
- public function isSuperAdmin(): bool
  - Devuelve true si el enum es `SUPERADMIN`.

- public function isCompanyRole(): bool
  - Devuelve true si el enum es `COMPANY_ADMIN` o `COMPANY_USER`.

- public function isPartnerRole(): bool
  - Devuelve true si el enum es `PARTNER_ADMIN`.

- public function isDriver(): bool
  - Devuelve true si el enum es `DRIVER`.

Notas
-----
Este enum se usa ampliamente en modelos, policies y lógica de autorización para distinguir comportamientos multi-tenant y permisos globales (SUPERADMIN).

