database
========

Resumen
-------
Información sobre factories, seeders y migraciones encontradas en el repositorio.

Factories (database/factories)
------------------------------
- CompanyConfigFactory.php
- CompanyFactory.php
- CorporateFactory.php
- DriverFactory.php
- PartnerFactory.php
- PassengerFactory.php
- UserFactory.php
- VehicleFactory.php

Seeders (database/seeders)
---------------------------
- PermissionSeeder.php
  - Lee `config('permissions.permissions')` y crea/actualiza permisos en tabla `permissions`.
- DatabaseSeeder.php
  - Llama a `PermissionSeeder::class`.

Migraciones (database/migrations) - tablas identificadas por archivos
--------------------------------------------------------------------
- 0001_01_01_000000_create_users_table.php -> tabla: users
- 0001_01_01_000001_create_cache_table.php -> tabla: cache?
- 0001_01_01_000002_create_jobs_table.php -> tabla: jobs
- 2025_12_04_133945_create_personal_access_tokens_table.php -> tabla: personal_access_tokens
- 2025_12_04_134116_create_companies_table.php -> tabla: companies
- 2025_12_04_134150_create_partners_table.php -> tabla: partners
- 2025_12_04_134218_add_multitenant_fields_to_users_table.php -> modificación/columnas en users (company_id, partner_id, role, ...)
- 2025_12_04_144054_add_must_change_password_to_users_table.php -> modificación en users (must_change_password)
- 2025_12_04_161114_create_permissions_table.php -> tabla: permissions
- 2025_12_04_161115_create_role_permissions_table.php -> tabla: role_permissions
- 2025_12_04_161117_create_user_permissions_table.php -> tabla: user_permissions
- 2025_12_04_174738_create_company_configs_table.php -> tabla: company_configs
- 2025_12_04_180804_add_driver_quota_default_to_company_configs_table.php -> modificación company_configs
- 2025_12_04_185851_add_driver_quota_default_to_company_configs_table.php (o similar) -> revisa diferencias (hay migración con ese propósito)
- 2025_12_04_191140_create_drivers_table.php -> tabla: drivers
- 2025_12_04_202350_create_vehicles_table.php -> tabla: vehicles
- 2025_12_04_203825_create_corporates_table.php -> tabla: corporates
- 2025_12_04_204300_create_passengers_table.php -> tabla: passengers

Notas
-----
- Las factories listadas se usan extensivamente en tests para poblar datos tenant-scoped.
- El seeder principal es `PermissionSeeder` que asegura que la tabla `permissions` contiene las entradas definidas en `config/permissions.php`.

