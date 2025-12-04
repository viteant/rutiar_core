Policies
========

Resumen
-------
Descripción de todas las policies en `app/Policies` y métodos disponibles.

### app/Policies/CorporatePolicy.php
- viewAny(User $user): bool
- view(User $user, Corporate $corporate): bool
- create(User $user): bool
- update(User $user, Corporate $corporate): bool
- delete(User $user, Corporate $corporate): bool

Comportamiento: SUPERADMIN siempre true; usuarios no-tenant requieren permisos `view_corporates`, `create_corporate`, `update_corporate`, `deactivate_corporate` según acción; además verifican que `user->company_id === corporate->company_id` para operaciones tenant-scoped.

### app/Policies/CompanyPermissionPolicy.php
- manageRolePermissions(User $user, Company $company): bool
- manageUserPermissions(User $user, Company $company): bool
- viewSettings(User $user, Company $company): bool
- updateSettings(User $user, Company $company): bool

Comportamiento: SUPERADMIN explícitamente NO puede gestionar permisos a nivel de compañía mediante estas policies (devuelven false para SUPERADMIN). Requiere que el user pertenezca a la misma company y tenga permisos correspondientes `manage_company_role_permissions`, `manage_company_user_permissions`, `view_company_settings`, `update_company_settings`.

### app/Policies/PassengerPolicy.php
- viewAny(User $user): bool
- view(User $user, Passenger $passenger): bool
- create(User $user): bool
- update(User $user, Passenger $passenger): bool
- delete(User $user, Passenger $passenger): bool

Comportamiento: similar a CorporatePolicy pero con permisos `view_passengers`, `create_passenger`, `update_passenger`, `deactivate_passenger`.

### app/Policies/PartnerPolicy.php
- viewAny(User $user): bool
- view(User $user, Partner $partner): bool
- create(User $user): bool
- update(User $user, Partner $partner): bool
- delete(User $user, Partner $partner): bool

Comportamiento: SUPERADMIN bypass; para usuarios tenant verifica `inSameTenant` (mismo company_id) y permisos `view_partners`, `create_partner`, `update_partner`, `delete_partner`.

### app/Policies/DriverPolicy.php
- viewAny(User $user): bool
- view(User $user, Driver $driver): bool
- create(User $user): bool
- update(User $user, Driver $driver): bool
- delete(User $user, Driver $driver): bool

Comportamiento: SUPERADMIN bypass; verifica tenant match y permisos `view_drivers`, `create_driver`, `update_driver`, `deactivate_driver`.

### app/Policies/VehiclePolicy.php
- viewAny(User $user): bool
- view(User $user, Vehicle $vehicle): bool
- create(User $user): bool
- update(User $user, Vehicle $vehicle): bool
- delete(User $user, Vehicle $vehicle): bool

Comportamiento: SUP ERADMIN bypass; permisos `view_vehicles`, `create_vehicle`, `update_vehicle`, `deactivate_vehicle`.

### app/Policies/Traits/HandlesTenantAuthorization.php
- protected function sameTenant(User $user, Model $model): bool
  - Utilidad común para comprobar si `model->company_id` coincide con `user->company_id`; SUPERADMIN bypass.

Notas
-----
- Las policies mezclan comprobaciones de rol/permiso con restricciones tenant (company_id). Esto permite granularidad por compañía y overrides por usuario.

