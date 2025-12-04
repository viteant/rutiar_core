Models
======

Resumen
-------
Resumen de modelos en `app/Models`: atributos importantes, relaciones y métodos útiles.

### User
- Archivo: app/Models/User.php
- Fillable: name, email, password, company_id, partner_id, role, is_active, must_change_password
- Hidden: password, remember_token
- Casts: email_verified_at (datetime), password (hashed), is_active (boolean), must_change_password (boolean), role => UserRole enum
- Relaciones:
  - company(): BelongsTo(Company::class)
  - userPermissions(): HasMany(UserPermission::class)
  - rolePermissions(): HasMany(RolePermission::class, 'role', 'role') -> filtered by company_id
  - partner(): BelongsTo(Partner::class)
- Métodos:
  - isSuperAdmin(): bool
  - hasPermission(string $permissionName): bool
    - Resolución: SUPERADMIN true; sino primero userPermissions (company-scoped); sino rolePermissions (company-scoped).

### RolePermission
- Fillable: company_id, role, permission_id
- Relaciones: company(), permission()

### Driver
- Fillable: company_id, partner_id, user_id, full_name, phone, license_number, is_active
- Casts: is_active => boolean
- Relaciones: company(), partner(), user()

### Company
- Fillable: name, code, country, timezone, is_active
- Relaciones: users(), partners(), drivers(), config() (HasOne CompanyConfig)

### Corporate
- Table: corporates
- Fillable: company_id, name, tax_id, contact_name, contact_email, is_active
- Casts: is_active => boolean
- Relaciones: company(), passengers()

### Passenger
- Table: passengers
- Fillable: company_id, corporate_id, full_name, employee_code, document_id, home_address, home_lat, home_lng, shift_code, is_active
- Casts: home_lat decimal:7, home_lng decimal:7, is_active boolean
- Relaciones: company(), corporate()

### Permission
- Fillable: name, description
- Relaciones: rolePermissions(), userPermissions()

### UserPermission
- Fillable: company_id, user_id, permission_id
- Relaciones: company(), user(), permission()

### CompanyConfig
- Fillable: company_id, planning_cutoff_time, default_waiting_minutes, max_drivers_per_partner, driver_quota_default, allow_driver_reorder, settings
- Casts: planning_cutoff_time datetime:H:i:s, default_waiting_minutes integer, max_drivers_per_partner integer, driver_quota_default integer, allow_driver_reorder boolean, settings array
- Relaciones: company()

### Vehicle
- Table: vehicles
- Fillable: company_id, partner_id, plate, model, capacity, is_active
- Casts: capacity integer, is_active boolean
- Relaciones: company(), partner()

### Partner
- Fillable: company_id, name, code, tax_id, is_active, driver_quota
- Casts: driver_quota integer, is_active boolean
- Relaciones: company(), drivers(), users()
- Métodos:
  - effectiveDriverQuota(): ?int
    - Retorna driver_quota si está seteado; si no, busca company->config->driver_quota_default; si no existe config retorna null.

Notas
-----
- Los modelos usan factories (ver database/factories) y están preparados para un comportamiento multi-tenant (company_id en la mayoría).

