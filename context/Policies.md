# Policies/CompanyPermissionPolicy.php

*Policy que controla quién puede gestionar permisos y configuración de compañía.*

Funciones:
- `manageRolePermissions(User $user, Company $company): bool` - Devuelve `true` solo si el usuario NO es SUPERADMIN, tiene `company_id` igual al de la compañía y posee el permiso `manage_company_role_permissions`.
- `manageUserPermissions(User $user, Company $company): bool` - Similar a `manageRolePermissions`, pero exige el permiso `manage_company_user_permissions`.
- `viewSettings(User $user, Company $company): bool` - Permite ver configuración si el usuario pertenece a la compañía, no es SUPERADMIN y tiene el permiso `view_company_settings`.
- `updateSettings(User $user, Company $company): bool` - Permite actualizar configuración si el usuario pertenece a la compañía, no es SUPERADMIN y tiene el permiso `update_company_settings`.

---

# Policies/PassengerPolicy.php

*Policy para controlar acceso a pasajeros.*

Funciones:
- `viewAny(User $user): bool` - Permite listar pasajeros si el usuario es SUPERADMIN o, si no, tiene `company_id` no nulo y el permiso `view_passengers`.
- `view(User $user, Passenger $passenger): bool` - Permite ver un pasajero si el usuario es SUPERADMIN o si pertenece a la misma compañía (`company_id` coincide) y tiene `view_passengers`.
- `create(User $user): bool` - Permite crear pasajeros si es SUPERADMIN o si pertenece a una compañía y tiene `create_passenger`.
- `update(User $user, Passenger $passenger): bool` - Permite actualizar si es SUPERADMIN o si pertenece a la misma compañía y tiene `update_passenger`.
- `delete(User $user, Passenger $passenger): bool` - Permite desactivar (DELETE) si es SUPERADMIN o si pertenece a la misma compañía y tiene `deactivate_passenger`.

---

# Policies/PartnerPolicy.php

*Policy para controlar acceso y operaciones sobre partners.*

Funciones:
- `viewAny(User $user): bool` - Permite listar partners si el usuario es SUPERADMIN o si tiene `company_id` y el permiso `view_partners`.
- `view(User $user, Partner $partner): bool` - Permite ver partner si es SUPERADMIN o si `inSameTenant($user, $partner)` y tiene `view_partners`.
- `create(User $user): bool` - Permite crear partner si es SUPERADMIN o si tiene `company_id` y el permiso `create_partner`.
- `update(User $user, Partner $partner): bool` - Permite actualizar si es SUPERADMIN o si `inSameTenant` y tiene `update_partner`.
- `delete(User $user, Partner $partner): bool` - Permite desactivar si es SUPERADMIN o si `inSameTenant` y tiene `delete_partner`.
- `protected inSameTenant(User $user, Partner $partner): bool` - Helper que comprueba que `user.company_id` sea igual a `partner.company_id` y no nulo.

---

# Policies/DriverPolicy.php

*Policy para controlar acceso y operaciones sobre drivers.*

Funciones:
- `viewAny(User $user): bool` - Permite listar drivers si es SUPERADMIN o si `company_id` no es nulo y tiene `view_drivers`.
- `view(User $user, Driver $driver): bool` - Permite ver driver si es SUPERADMIN o si `company_id` coincide con `driver.company_id` y tiene `view_drivers`.
- `create(User $user): bool` - Permite crear driver si es SUPERADMIN o si tiene `company_id` y el permiso `create_driver`.
- `update(User $user, Driver $driver): bool` - Permite actualizar si es SUPERADMIN o si la compañía coincide y tiene `update_driver`.
- `delete(User $user, Driver $driver): bool` - Permite "eliminar" (desactivar) el driver si es SUPERADMIN o si pertenece a la misma compañía y tiene `deactivate_driver`.

---

# Policies/VehiclePolicy.php

*Policy para controlar acceso y operaciones sobre vehículos.*

Funciones:
- `viewAny(User $user): bool` - Permite listar vehículos si es SUPERADMIN o si `company_id` no es nulo y tiene `view_vehicles`.
- `view(User $user, Vehicle $vehicle): bool` - Permite ver vehículo si es SUPERADMIN o si `company_id` coincide con la del vehículo y tiene `view_vehicles`.
- `create(User $user): bool` - Permite crear vehículo si es SUPERADMIN o si tiene `company_id` y `create_vehicle`.
- `update(User $user, Vehicle $vehicle): bool` - Permite actualizar si es SUPERADMIN o si `company_id` coincide y tiene `update_vehicle`.
- `delete(User $user, Vehicle $vehicle): bool` - Permite desactivar vehículo (DELETE) si es SUPERADMIN o si `company_id` coincide y tiene `deactivate_vehicle`.

---

# Policies/CorporatePolicy.php

*Policy para controlar acceso y operaciones sobre corporates (clientes corporativos).* 

Funciones:
- `viewAny(User $user): bool` - Permite listar corporates si es SUPERADMIN o si `company_id` no es nulo y tiene `view_corporates`.
- `view(User $user, Corporate $corporate): bool` - Permite ver corporate si es SUPERADMIN o si `company_id` coincide con `corporate.company_id` y tiene `view_corporates`.
- `create(User $user): bool` - Permite crear corporate si es SUPERADMIN o si tiene `company_id` y `create_corporate`.
- `update(User $user, Corporate $corporate): bool` - Permite actualizar si es SUPERADMIN o si `company_id` coincide y tiene `update_corporate`.
- `delete(User $user, Corporate $corporate): bool` - Permite desactivar corporate si es SUPERADMIN o si `company_id` coincide y tiene `deactivate_corporate`.

---

# Policies/Traits/HandlesTenantAuthorization.php

*Trait de soporte para policies que necesitan verificar pertenencia a un mismo tenant (company).* 

Funciones:
- `protected sameTenant(User $user, Model $model): bool` - Devuelve `true` si el usuario es SUPERADMIN (bypass) o si el modelo tiene propiedad `company_id` y `user.company_id` no es nulo y coincide con `model.company_id`. Devuelve `false` si el modelo no expone `company_id` o si los `company_id` no coinciden.
