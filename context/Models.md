# Models

A continuación los modelos con sus relaciones y propiedades clave.

---

# Models/User.php

*Modelo de usuario autenticable de la plataforma, asociado opcionalmente a una compañía y a un partner.*

Traits:
- `HasFactory` - Permite crear instancias de `User` mediante factories de pruebas.
- `Notifiable` - Habilita el envío de notificaciones al usuario.
- `HasApiTokens` - Proporciona soporte para tokens de API (Laravel Sanctum).

Relaciones:
- `company(): BelongsTo` - Relación con `Company`; indica a qué compañía pertenece el usuario (puede ser `null` para SUPERADMIN).
- `userPermissions(): HasMany` - Relación con `UserPermission`; permisos específicos del usuario por compañía.
- `rolePermissions(): HasMany` - Relación con `RolePermission` filtrada por `role` y `company_id` del usuario (permisos derivados del rol en su compañía).
- `partner(): BelongsTo` - Relación con `Partner`; indica el partner asociado al usuario (si aplica).

Funciones:
- `casts(): array` - Define casts para `email_verified_at`, `password` (hashed), `is_active`, `must_change_password` y `role` (cast a `UserRole`).
- `company()` / `userPermissions()` / `rolePermissions()` / `partner()` - Métodos de relación descritos arriba.
- `isSuperAdmin(): bool` - Devuelve `true` si el rol del usuario es `UserRole::SUPERADMIN`.
- `hasPermission(string $permissionName): bool` - Comprueba si el usuario tiene un permiso dado, siguiendo el orden:
  1. Permisos específicos de usuario (`user_permissions` para su `company_id`).
  2. Permisos asociados a su rol (`role_permissions` en la misma compañía).
  3. Devuelve `false` por defecto si no se encuentra. Para SUPERADMIN siempre devuelve `true`.

---

# Models/Company.php

*Modelo de compañía, usado como tenant en el esquema multi-tenant.*

Traits:
- `HasFactory` - Habilita la creación de compañías vía factory.

Relaciones:
- `users(): HasMany` - Usuarios asociados a la compañía.
- `partners(): HasMany` - Partners (socios) de la compañía.
- `drivers(): HasMany` - Conductores (`Driver`) asociados a la compañía.
- `config(): HasOne` - Configuración (`CompanyConfig`) de la compañía.

Funciones:
- `fillable` incluye `name`, `code`, `country`, `timezone`, `is_active`.

---

# Models/CompanyConfig.php

*Modelo de configuración operativa de la compañía (horarios, cuotas, parámetros varios).* 

Traits:
- `HasFactory` - Permite crear configuraciones de prueba.

Relaciones:
- `company(): BelongsTo` - Compañía a la que pertenece esta configuración.

Funciones:
- `casts(): array` - Castea `planning_cutoff_time` a `datetime:H:i:s`, `default_waiting_minutes`, `max_drivers_per_partner`, `driver_quota_default` a enteros, `allow_driver_reorder` a booleano y `settings` a array.
- Atributos principales: `company_id`, `planning_cutoff_time`, `default_waiting_minutes`, `max_drivers_per_partner`, `driver_quota_default`, `allow_driver_reorder`, `settings`.

---

# Models/Partner.php

*Modelo de partner (socio) de transporte asociado a una compañía.*

Traits:
- `HasFactory` - Factory para generar partners.

Relaciones:
- `company(): BelongsTo` - Compañía propietaria del partner.
- `drivers(): HasMany` - Conductores asociados a este partner.
- `users(): HasMany` - Usuarios asociados al partner.

Funciones:
- `casts(): array` - Castea `driver_quota` como entero y `is_active` como booleano.
- `effectiveDriverQuota(): ?int` - Devuelve la cuota de drivers efectiva para el partner: si `driver_quota` no es `null`, la retorna; en caso contrario, intenta usar `company->config->driver_quota_default` y si no hay config, devuelve `null`.

---

# Models/Driver.php

*Modelo de conductor asociado a una compañía y partner, opcionalmente vinculado a un usuario.*

Traits:
- `HasFactory` - Habilita la generación de drivers mediante factory.

Relaciones:
- `company(): BelongsTo` - Compañía a la que pertenece el driver.
- `partner(): BelongsTo` - Partner que "administra" al driver.
- `user(): BelongsTo` - Usuario asociado al driver (si existe).

Funciones:
- `casts(): array` - Castea `is_active` como booleano.
- Atributos `fillable`: `company_id`, `partner_id`, `user_id`, `full_name`, `phone`, `license_number`, `is_active`.

---

# Models/Vehicle.php

*Modelo de vehículo asociado a una compañía y a un partner.*

Traits:
- `HasFactory` - Permite crear vehículos en factories.

Relaciones:
- `company(): BelongsTo` - Compañía a la que pertenece el vehículo.
- `partner(): BelongsTo` - Partner dueño del vehículo.

Funciones:
- `casts(): array` - Castea `capacity` a entero y `is_active` a booleano.
- Atributos `fillable`: `company_id`, `partner_id`, `plate`, `model`, `capacity`, `is_active`.

---

# Models/Corporate.php

*Modelo de corporate (cliente corporativo) asociado a una compañía.*

Traits:
- `HasFactory` - Factory para crear corporates.

Relaciones:
- `company(): BelongsTo` - Compañía a la que pertenece el corporate.
- `passengers(): HasMany` - Pasajeros (`Passenger`) asociados a este corporate.

Funciones:
- `casts(): array` - Castea `is_active` como booleano.
- Atributos `fillable`: `company_id`, `name`, `tax_id`, `contact_name`, `contact_email`, `is_active`.

---

# Models/Passenger.php

*Modelo de pasajero asociado a un corporate y a una compañía.*

Traits:
- `HasFactory` - Factory para crear pasajeros.

Relaciones:
- `company(): BelongsTo` - Compañía propietaria del pasajero.
- `corporate(): BelongsTo` - Corporate al que pertenece el pasajero.

Funciones:
- `casts(): array` - Castea `home_lat` y `home_lng` como decimales (7 dígitos) y `is_active` como booleano.
- Atributos `fillable`: `company_id`, `corporate_id`, `full_name`, `employee_code`, `document_id`, `home_address`, `home_lat`, `home_lng`, `shift_code`, `is_active`.

---

# Models/Permission.php

*Modelo de permiso del sistema (catálogo global de permisos).* 

Traits:
- `HasFactory` - Permite crear permisos en tests.

Relaciones:
- `rolePermissions(): HasMany` - Permisos asociados a roles (`RolePermission`).
- `userPermissions(): HasMany` - Permisos específicos a usuarios (`UserPermission`).

Funciones:
- Atributos `fillable`: `name`, `description`.

---

# Models/RolePermission.php

*Modelo que vincula un permiso con un rol dentro de una compañía.*

Traits:
- `HasFactory` - Factory para role_permissions.

Relaciones:
- `company(): BelongsTo` - Compañía a la que se aplica este permiso de rol.
- `permission(): BelongsTo` - Registro de `Permission` asociado.

Funciones:
- Atributos `fillable`: `company_id`, `role`, `permission_id`.

---

# Models/UserPermission.php

*Modelo que vincula un permiso con un usuario específico dentro de una compañía (override de permisos).* 

Traits:
- `HasFactory` - Factory para user_permissions.

Relaciones:
- `company(): BelongsTo` - Compañía a la que pertenece este override.
- `user(): BelongsTo` - Usuario al que se le asigna el permiso.
- `permission(): BelongsTo` - Permiso asociado.

Funciones:
- Atributos `fillable`: `company_id`, `user_id`, `permission_id`.

---

# Models/Traits/BelongsToCompany.php

*Trait de modelo que estandariza la relación de pertenencia a una compañía.*

Funciones:
- `company(): BelongsTo` - Define la relación `belongsTo` con `Company` utilizando la convención de `company_id`.

---

# Models/Traits/Activatable.php

*Trait de modelo para recursos "activables" con soft delete lógico usando un campo booleano `is_active`.*

Funciones:
- `scopeActive(Builder $query): Builder` - Query scope local que filtra registros donde `is_active` es `true`.
- `deactivate(): void` - Marca el modelo actual como inactivo (`is_active=false`) y guarda los cambios.

---

Notas:
- El sistema combina `RolePermission` (permisos por rol por compañía) y `UserPermission` (overrides por usuario y compañía). Los métodos de los modelos y las policies asumen que las comprobaciones se realizan por `company_id` (tenant-scoped).
