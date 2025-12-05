# Tests (Feature)

Este documento lista las pruebas (clases y métodos) encontradas en `tests/Feature` y una breve descripción de cada grupo.

## Tests/Feature/Partners/PartnerCrudTest.php

*Pruebas de integración para el CRUD de partners bajo distintos roles, permisos y tenants.*

Funciones (métodos de test):
- `test_company_admin_with_permission_can_list_partners(): void` - Verifica que un COMPANY_ADMIN con permiso `view_partners` puede listar solo los partners de su compañía.
- `test_company_user_without_permission_cannot_list_partners(): void` - Asegura que un COMPANY_USER sin permiso `view_partners` recibe 403 al listar.
- `test_company_admin_with_permission_can_create_partner(): void` - Comprueba que un COMPANY_ADMIN con permiso `create_partner` puede crear un partner en su compañía.
- `test_company_user_without_create_permission_cannot_create_partner(): void` - Asegura que un COMPANY_USER sin `create_partner` no puede crear partners (403) y que no se persisten datos.
- `test_company_admin_with_permission_can_show_partner(): void` - Verifica que un COMPANY_ADMIN con `view_partners` puede ver detalles de un partner de su compañía.
- `test_tenant_mismatch_on_show_is_forbidden_for_non_superadmin(): void` - Comprueba que un admin de una compañía no puede ver un partner de otra compañía (403), asegurando aislamiento de tenant.
- `test_company_admin_with_update_permission_can_update_partner(): void` - Asegura que un COMPANY_ADMIN con `update_partner` puede actualizar datos de un partner de su compañía.
- `test_company_user_without_update_permission_cannot_update_partner(): void` - Verifica que un COMPANY_USER sin `update_partner` no puede actualizar partners.
- `test_tenant_mismatch_on_update_is_forbidden_for_non_superadmin(): void` - Verifica que un admin no pueda actualizar partners de otra compañía.
- `test_company_admin_with_delete_permission_deactivates_partner_instead_of_deleting(): void` - Comprueba que el DELETE marca `is_active=false` en lugar de borrar el registro.
- `test_tenant_mismatch_on_delete_is_forbidden_for_non_superadmin(): void` - Verifica que no se pueda desactivar un partner de otra compañía.
- `test_superadmin_can_manage_partners_across_companies(): void` - Asegura que SUPERADMIN puede listar, crear y desactivar partners en cualquier compañía.

---

## Tests/Feature/Partners/PartnerModelTest.php

*Pruebas de modelo para relaciones y casts de Partner.*

Funciones:
- `test_partner_belongs_to_company(): void` - Verifica que `partner->company` devuelve la compañía correcta.
- `test_company_has_many_partners(): void` - Comprueba que la relación `company->partners` incluye todos los partners creados para esa compañía.
- `test_partner_casts_is_active_and_driver_quota(): void` - Asegura que `is_active` se castea a boolean y `driver_quota` a entero.
- `test_partner_factory_creates_valid_partner(): void` - Verifica que la factory crea partners consistentes con una compañía existente.

---

## Tests/Feature/Partners/PartnerLogicTest.php

*Pruebas de lógica de negocio asociada a `Partner::effectiveDriverQuota()`.*

Funciones:
- `test_partner_uses_own_driver_quota_if_set(): void` - Verifica que si `driver_quota` no es null en el partner, se usa esa cuota.
- `test_partner_uses_company_default_quota_if_own_quota_is_null(): void` - Comprueba que si `driver_quota` es null, se usa `company->config->driver_quota_default`.
- `test_partner_effective_quota_is_null_if_no_company_config(): void` - Asegura que si no hay configuración de compañía, `effectiveDriverQuota()` devuelve null.

---

## Tests/Feature/Corporates/CorporateCrudTest.php

*Pruebas de integración para CRUD de corporates con permisos, tenant y overrides de usuario.*

Funciones:
- `test_company_admin_with_permission_can_list_corporates(): void` - Asegura que un COMPANY_ADMIN con `view_corporates` puede listar solo corporates de su compañía.
- `test_company_user_without_permission_cannot_list_corporates(): void` - Verifica que un COMPANY_USER sin `view_corporates` no puede listar (403).
- `test_company_admin_with_permission_can_create_corporate_respecting_tenant(): void` - Comprueba que un COMPANY_ADMIN puede crear un corporate y que se asigna correctamente su `company_id`.
- `test_tenant_mismatch_on_show_is_forbidden_for_non_superadmin(): void` - Asegura que un admin no pueda ver corporates de otra compañía.
- `test_company_admin_with_permission_can_update_corporate(): void` - Verifica que se pueden actualizar campos de corporate con permisos adecuados.
- `test_company_admin_with_deactivate_permission_deactivates_corporate_instead_of_deleting(): void` - Comprueba que el DELETE marca `is_active=false`.
- `test_superadmin_can_manage_corporates_across_companies(): void` - Asegura que SUPERADMIN puede listar, crear y desactivar corporates en distintas compañías.
- `test_user_override_permission_allows_access_even_without_role_permission(): void` - Verifica que un usuario con override de permiso individual (`user_permissions`) pueda acceder aunque su rol no tenga el permiso.

---

## Tests/Feature/Drivers/DriverModelTest.php

*Pruebas de relaciones de Driver con Company, Partner y User, y de casts.*

Funciones:
- `test_driver_belongs_to_company_and_partner(): void` - Verifica relaciones `driver->company` y `driver->partner`.
- `test_driver_may_belong_to_user(): void` - Comprueba que `driver->user` devuelve el usuario asociado si `user_id` está definido.
- `test_company_has_many_drivers(): void` - Asegura que `company->drivers` contiene todos los drivers de esa compañía.
- `test_partner_has_many_drivers(): void` - Asegura que `partner->drivers` contiene todos los drivers asignados al partner.
- `test_is_active_casts_to_boolean(): void` - Verifica que `is_active` se castea a boolean.

---

## Tests/Feature/Drivers/DriverCrudTest.php

*Pruebas de CRUD de drivers, incluyendo cuotas por partner, tenant y overrides de permisos.*

Funciones:
- `test_company_admin_with_permission_can_list_drivers(): void` - Verifica listado de drivers para COMPANY_ADMIN con `view_drivers` limitado a su compañía.
- `test_company_user_without_permission_cannot_list_drivers(): void` - Asegura que COMPANY_USER sin permiso `view_drivers` recibe 403.
- `test_company_admin_with_permission_can_create_driver_respecting_tenant(): void` - Comprueba que se puede crear driver respetando `company_id` del tenant.
- `test_create_driver_fails_when_quota_is_exceeded(): void` - Verifica que, al superar la cuota de drivers para un partner, la creación falla con 422.
- `test_tenant_mismatch_on_show_is_forbidden_for_non_superadmin(): void` - Asegura error 403 al intentar ver driver de otra compañía.
- `test_company_admin_with_permission_can_update_driver(): void` - Comprueba que se puede actualizar driver (incluyendo cambio de partner) con permisos adecuados.
- `test_company_admin_with_deactivate_permission_deactivates_driver_instead_of_deleting(): void` - Verifica soft delete para drivers.
- `test_superadmin_can_manage_drivers_across_companies(): void` - Asegura que SUPERADMIN puede listar, crear y desactivar drivers en cualquier compañía.
- `test_user_override_permission_allows_access_even_without_role_permission(): void` - Asegura que un usuario con override de permiso `view_drivers` puede listar drivers sin tener el permiso por rol.

---

## Tests/Feature/Passengers/PassengerCrudTest.php

*Pruebas de CRUD de pasajeros, incluyendo tenant, permisos y overrides.*

Funciones:
- `test_company_admin_with_permission_can_list_passengers(): void` - Verifica que un COMPANY_ADMIN con `view_passengers` lista solo pasajeros de su compañía.
- `test_company_user_without_permission_cannot_list_passengers(): void` - Asegura que COMPANY_USER sin `view_passengers` recibe 403.
- `test_company_admin_with_permission_can_create_passenger_respecting_tenant(): void` - Verifica creación de pasajeros respetando `company_id` del tenant y `corporate_id` válido.
- `test_tenant_mismatch_on_show_is_forbidden_for_non_superadmin(): void` - Asegura 403 al mostrar pasajero de otra compañía.
- `test_company_admin_with_permission_can_update_passenger(): void` - Verifica actualización de pasajero y cambio de corporate dentro de la misma compañía.
- `test_company_admin_with_deactivate_permission_deactivates_passenger_instead_of_deleting(): void` - Comprueba soft delete (`is_active=false`) en eliminar.
- `test_superadmin_can_manage_passengers_across_companies(): void` - Verifica que SUPERADMIN maneja pasajeros en múltiples compañías.
- `test_user_override_permission_allows_access_even_without_role_permission(): void` - Asegura que override de permiso `view_passengers` permite acceso sin permiso de rol.

---

## Tests/Feature/Vehicles/VehicleCrudTest.php

*Pruebas de CRUD de vehículos con tenant, permisos y overrides.*

Funciones:
- `test_company_admin_with_permission_can_list_vehicles(): void` - Verifica que COMPANY_ADMIN con `view_vehicles` lista solo vehículos de su compañía.
- `test_company_user_without_permission_cannot_list_vehicles(): void` - Asegura 403 para COMPANY_USER sin `view_vehicles`.
- `test_company_admin_with_permission_can_create_vehicle_respecting_tenant(): void` - Comprueba creación de vehículo respetando `company_id` del tenant.
- `test_tenant_mismatch_on_show_is_forbidden_for_non_superadmin(): void` - Asegura que un admin no pueda ver vehículo de otra compañía.
- `test_company_admin_with_permission_can_update_vehicle(): void` - Comprueba actualización de campos y cambio de partner.
- `test_company_admin_with_deactivate_permission_deactivates_vehicle_instead_of_deleting(): void` - Verifica soft delete de vehículos.
- `test_superadmin_can_manage_vehicles_across_companies(): void` - Verifica que SUPERADMIN puede listar, crear y desactivar vehículos en distintas compañías.
- `test_user_override_permission_allows_access_even_without_role_permission(): void` - Asegura que override de `view_vehicles` permite listar sin permiso de rol.

---

## Tests/Feature/Auth/MultiTenantAuthTest.php

*Pruebas del flujo de autenticación multi-tenant y resolución de tenant.*

Funciones:
- `test_superadmin_can_login_without_company_code(): void` - Verifica que SUPERADMIN puede iniciar sesión sin `company_code` y que la respuesta incluye `user.role` y `company=null`.
- `test_company_user_can_login_with_valid_company_code(): void` - Comprueba login exitoso para COMPANY_ADMIN con `company_code` correcto, incluyendo compañía en la respuesta.
- `test_non_superadmin_requires_company_code(): void` - Verifica que un usuario no-SUPERADMIN sin `company_code` recibe 422 y error de validación.
- `test_login_fails_with_invalid_company_code(): void` - Asegura que un `company_code` incorrecto produce error 422 y validación en ese campo.
- `test_tenant_is_resolved_on_protected_route_for_company_user(): void` - Confirma que, con un token válido, `/api/tenant-example` devuelve `tenant` con la compañía correcta.
- `test_superadmin_has_null_tenant_on_protected_route(): void` - Verifica que SUPERADMIN recibe `tenant=null` en `/api/tenant-example`.
- `test_logout_revokes_current_token(): void` - Comprueba que `/api/auth/logout` elimina tokens, y que peticiones posteriores con el mismo token son 401.

---

## Tests/Feature/Auth/RolePermissionSyncServiceTest.php

*Pruebas del servicio `RolePermissionSyncService`.*

Funciones:
- `test_sync_creates_role_permissions_for_company_from_config(): void` - Verifica que al sincronizar para una compañía se crean `RolePermission` para cada rol y permiso definido en `role_permissions.defaults` que exista en `permissions`.
- `test_sync_is_idempotent_and_does_not_duplicate_records(): void` - Comprueba que dos llamadas consecutivas a `syncForCompany` no duplican registros.
- `test_sync_ignores_permissions_not_present_in_database(): void` - Verifica que si en config hay permisos inexistentes en BD, no se crean `RolePermission` para ellos.

---

## Tests/Feature/Auth/CompanyPermissionsEndpointsTest.php

*Pruebas de los endpoints de permisos de compañía (`/api/company/permissions/*`).*

Funciones:
- `test_company_admin_can_view_available_permissions(): void` - Verifica que un COMPANY_ADMIN con permiso de gestión puede listar todos los permisos disponibles.
- `test_company_user_without_manage_permission_cannot_access_role_permissions(): void` - Asegura que un COMPANY_USER sin permiso de gestión recibe 403 al acceder a `/roles`.
- `test_company_admin_can_update_role_permissions_for_company_user_role(): void` - Comprueba que un admin puede actualizar permisos para el rol COMPANY_USER.
- `test_company_admin_can_view_and_update_user_specific_permissions(): void` - Verifica que un admin con permiso adecuado puede actualizar y consultar los permisos específicos de un usuario.
- `test_superadmin_cannot_manage_company_permissions(): void` - Asegura que SUPERADMIN no pueda llamar a estos endpoints (403), por diseño.

---

## Tests/Feature/Auth/CompanyConfigEndpointsTest.php

*Pruebas de los endpoints `/api/company/config` para ver y actualizar configuración.*

Funciones:
- `test_company_admin_with_permission_can_view_config(): void` - Verifica que un COMPANY_ADMIN con `view_company_settings` puede obtener la configuración de su compañía.
- `test_company_user_without_permission_cannot_view_config(): void` - Asegura que COMPANY_USER sin dicho permiso recibe 403.
- `test_company_admin_with_permission_can_update_config(): void` - Comprueba que un admin con `update_company_settings` puede actualizar campos de configuración (y que se reflejan en BD).
- `test_superadmin_cannot_update_company_config(): void` - Verifica que SUPERADMIN recibe 403 al intentar actualizar configuración (política explícita).

---

## Tests/Feature/Auth/CompanyOnboardingFlowTest.php

*Prueba end-to-end del flujo de onboarding de compañía y admin, incluido cambio de contraseña.*

Funciones:
- `test_full_company_onboarding_and_password_change_flow(): void` - Simula la ejecución del comando `rutiar:create-company-admin` respondiendo preguntas; verifica creación de compañía y usuario COMPANY_ADMIN, envío de correo (mockado con `Mail::fake()`), login con contraseña temporal, bloqueo por middleware de cambio de contraseña en rutas protegidas, cambio efectivo de contraseña vía `/api/auth/change-password` y acceso posterior a rutas protegidas con tenant resuelto.

---

## Tests/Feature/Auth/PermissionSeederTest.php

*Pruebas de la validez de `config/permissions.php` y del seeder de permisos.*

Funciones:
- `test_permissions_config_has_unique_names(): void` - Verifica que no haya nombres duplicados en la configuración de permisos.
- `test_permission_seeder_creates_permissions_from_config(): void` - Asegura que `PermissionSeeder` crea correctamente todos los permisos definidos en config.

---

## Tests/Feature/Auth/UserPermissionsTest.php

*Pruebas de la lógica `User::hasPermission` en distintos escenarios.*

Funciones:
- `test_superadmin_always_has_any_permission(): void` - Verifica que SUPERADMIN tiene cualquier permiso, exista o no.
- `test_role_permissions_are_applied_when_no_user_override(): void` - Comprueba que los permisos por rol se aplican cuando no hay overrides de usuario.
- `test_user_specific_permissions_override_role_permissions(): void` - Verifica que un permiso asignado por `UserPermission` se respeta incluso sin permisos por rol.
- `test_permission_is_denied_when_not_assigned_anywhere(): void` - Comprueba que permisos no asignados ni por rol ni por usuario se niegan.
- `test_permissions_are_tenant_scoped(): void` - Verifica que los permisos se resuelven por compañía (tenant) y que permisos en otra compañía no aplican.

---

## Tests/Feature/Auth/SecurityMiddlewaresTest.php

*Pruebas de los middlewares de seguridad: `EnsurePasswordIsChanged`, `EnsureUserIsActive` y `ResolveTenantFromUser` indirectamente.*

Funciones:
- `test_pending_password_user_can_access_me_and_change_password_routes(): void` - Verifica que un usuario con `must_change_password=true` puede usar `/api/auth/me` y `/api/auth/change-password` y que al cambiar contraseña se limpia el flag.
- `test_pending_password_user_is_blocked_from_protected_routes(): void` - Asegura que un usuario pendiente de cambio de contraseña recibe 423 al acceder a `/api/tenant-example`.
- `test_inactive_user_is_blocked_even_with_valid_token(): void` - Comprueba que un usuario inactivo es bloqueado (403, código `USER_INACTIVE`) aunque tenga token válido.
- `test_company_inactive_blocks_access_even_if_user_is_active(): void` - Verifica que si la compañía está inactiva, el acceso es bloqueado con mensaje `Company is not active or not assigned.`.

---

# Resumen

*En la carpeta `tests/Feature` se cubren:*
- Validación de permisos por rol y a nivel de usuario.
- Aislamiento por tenant para partners, drivers, corporates, passengers y vehicles.
- Flujos de autenticación multi-tenant y middlewares de seguridad.
- Servicios auxiliares como `RolePermissionSyncService` y `PermissionSeeder`.
