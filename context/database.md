# database/factories

*Factories para generar datos de prueba de los modelos.*

Funciones (factories disponibles):
- `CompanyFactory` (`database/factories/CompanyFactory.php`) - Genera compañías con campos: `name`, `code` (prefijo `COMP-` y valor único), `country` (EC), `timezone` (`America/Guayaquil`), `is_active` (true).
- `CompanyConfigFactory` (`database/factories/CompanyConfigFactory.php`) - Genera configuraciones de compañía con valores por defecto (`planning_cutoff_time` 18:00:00, `default_waiting_minutes` 5, `max_drivers_per_partner` 0, `allow_driver_reorder` true, `settings` []).
- `PartnerFactory` (`database/factories/PartnerFactory.php`) - Genera partners asociados a una compañía (creada via factory si no se provee), con `name` (faker company), `code` (tipo `P####`), `tax_id`, `driver_quota` (número entre 0 y 50) e `is_active` true.
- `DriverFactory` (`database/factories/DriverFactory.php`) - Genera drivers asociados a una compañía y partner, con `full_name`, `phone`, `license_number` y `is_active` true; `user_id` se deja por defecto `null`.
- `VehicleFactory` (`database/factories/VehicleFactory.php`) - Genera vehículos con compañía y partner coherentes, `plate` (tipo `ABC-####`), `model` (dos palabras), `capacity` (entre 4 y 40), `is_active` true.
- `CorporateFactory` (`database/factories/CorporateFactory.php`) - Genera corporates con `company_id` de una compañía creada, `name` (faker company), `tax_id`, `contact_name`, `contact_email`, `is_active` true.
- `PassengerFactory` (`database/factories/PassengerFactory.php`) - Genera pasajeros asociados a una compañía y corporate coherentes, con `full_name`, `employee_code`, `document_id`, `home_address`, `home_lat`, `home_lng`, `shift_code`, `is_active` true.
- `UserFactory` (`database/factories/UserFactory.php`) - Genera usuarios con `name`, `email`, `email_verified_at`, `password` (bcrypt('password')), rol por defecto `COMPANY_USER`, `is_active` true, `company_id` y `partner_id` por defecto `null`. Expone estados:
  - `superAdmin()` - Rol SUPERADMIN y `company_id` null.
  - `forCompany(?Company $company = null)` - Asigna `company_id` a la compañía dada o crea una nueva.
  - `inactive()` - Marca `is_active=false`.

---

# database/seeders

*Seeders para poblar tablas con datos iniciales.*

Funciones (seeders disponibles):
- `PermissionSeeder` (`database/seeders/PermissionSeeder.php`) - Lee la configuración `config('permissions.permissions')` y crea/actualiza `Permission` con `name` y `description` usando `updateOrCreate`. Se asegura de omitir entradas sin `name`.
- `DatabaseSeeder` (`database/seeders/DatabaseSeeder.php`) - Seeder raíz que actualmente llama a `PermissionSeeder` para poblar la tabla de permisos.

---

# database/migrations

*Listado de migraciones y tablas que crean o modifican.*

Funciones (migraciones principales y su propósito):
- `0001_01_01_000000_create_users_table.php` - Crea la tabla `users` con campos básicos de autenticación.
- `0001_01_01_000001_create_cache_table.php` - Crea la tabla `cache` para almacenamiento de caché en BD.
- `0001_01_01_000002_create_jobs_table.php` - Crea la tabla `jobs` para la cola de trabajos.
- `2025_12_04_133945_create_personal_access_tokens_table.php` - Crea la tabla `personal_access_tokens` usada por Laravel Sanctum para los tokens de API.
- `2025_12_04_134116_create_companies_table.php` - Crea la tabla `companies` (campos esperados: `name`, `code`, `country`, `timezone`, `is_active`, timestamps).
- `2025_12_04_134150_create_partners_table.php` - Crea la tabla `partners` asociando partners a compañías.
- `2025_12_04_134218_add_multitenant_fields_to_users_table.php` - Agrega campos de multitenancy a `users`, como `company_id` y `partner_id` (entre otros relacionados con el modelo multi-tenant).
- `2025_12_04_144054_add_must_change_password_to_users_table.php` - Agrega el campo booleano `must_change_password` a la tabla `users`.
- `2025_12_04_161114_create_permissions_table.php` - Crea la tabla `permissions` con columnas como `name` y `description`.
- `2025_12_04_161115_create_role_permissions_table.php` - Crea la tabla `role_permissions`, que vincula `company_id`, `role` y `permission_id`.
- `2025_12_04_161117_create_user_permissions_table.php` - Crea la tabla `user_permissions` que vincula `company_id`, `user_id` y `permission_id`.
- `2025_12_04_174738_create_company_configs_table.php` - Crea la tabla `company_configs` con campos para ajustes operativos (planning_cutoff_time, default_waiting_minutes, max_drivers_per_partner, allow_driver_reorder, settings).
- `2025_12_04_180804_add_tax_id_and_driver_quota_to_partners_table.php` - Agrega columnas `tax_id` y `driver_quota` a la tabla `partners`.
- `2025_12_04_185851_add_driver_quota_default_to_company_configs_table.php` - Agrega el campo `driver_quota_default` a la tabla `company_configs`.
- `2025_12_04_191140_create_drivers_table.php` - Crea la tabla `drivers`, enlazando drivers con `company_id`, `partner_id`, `user_id` y campos de perfil.
- `2025_12_04_202350_create_vehicles_table.php` - Crea la tabla `vehicles`, enlazando vehículos con compañía y partner, junto con campos como `plate`, `model`, `capacity`, `is_active`.
- `2025_12_04_203825_create_corporates_table.php` - Crea la tabla `corporates` (clientes corporativos) con `company_id`, `name`, `tax_id`, `contact_name`, `contact_email`, `is_active`.
- `2025_12_04_204300_create_passengers_table.php` - Crea la tabla `passengers`, enlazando pasajeros con compañía y corporate, y campos como nombre, código de empleado, direcciones, coordenadas, `shift_code`, `is_active`.

---

# config/database.php

*Configuración de conexiones de base de datos y migraciones.*

Funciones relevantes:
- Conexión por defecto: `default => env('DB_CONNECTION', 'sqlite')` (sqlite por defecto si no se define otra cosa).
- Definiciones de conexiones para `sqlite`, `mysql`, `mariadb`, `pgsql`, `sqlsrv`, incluyendo opciones de charset, collation y opciones específicas (SSL, cluster Redis, etc.).
- Configuración de migrations:
  - `migrations.table => 'migrations'` - Nombre de la tabla donde se registran migraciones ejecutadas.
  - `update_date_on_publish => true` - Opción para actualizar fechas en ciertas operaciones de publicación de migraciones.
