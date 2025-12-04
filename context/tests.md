tests (Feature)
===============

Resumen
-------
Listado de tests en `tests/Feature` y funciones (métodos de test) encontradas en cada clase. Incluye los nombres de los métodos de prueba para referencia.

Tests por archivo
------------------

### tests/Feature/Partners/PartnerModelTest.php
- test_partner_belongs_to_company
- test_company_has_many_partners
- test_partner_casts_is_active_and_driver_quota
- test_partner_factory_creates_valid_partner

### tests/Feature/Partners/PartnerLogicTest.php
- test_partner_uses_own_driver_quota_if_set
- test_partner_uses_company_default_quota_if_own_quota_is_null
- test_partner_effective_quota_is_null_if_no_company_config

### tests/Feature/Partners/PartnerCrudTest.php
- test_company_admin_with_permission_can_list_partners
- test_company_user_without_permission_cannot_list_partners
- test_company_admin_with_permission_can_create_partner
- test_company_user_without_create_permission_cannot_create_partner
- test_company_admin_with_permission_can_show_partner
- test_tenant_mismatch_on_show_is_forbidden_for_non_superadmin
- test_company_admin_with_update_permission_can_update_partner
- test_company_user_without_update_permission_cannot_update_partner
- test_tenant_mismatch_on_update_is_forbidden_for_non_superadmin
- test_company_admin_with_delete_permission_deactivates_partner_instead_of_deleting
- test_tenant_mismatch_on_delete_is_forbidden_for_non_superadmin
- test_superadmin_can_manage_partners_across_companies
- test_user_override_permission_allows_access_even_without_role_permission

### tests/Feature/Corporates/CorporateCrudTest.php
- test_company_admin_with_permission_can_list_corporates
- test_company_user_without_permission_cannot_list_corporates
- test_company_admin_with_permission_can_create_corporate_respecting_tenant
- test_tenant_mismatch_on_show_is_forbidden_for_non_superadmin
- test_company_admin_with_permission_can_update_corporate
- test_company_admin_with_deactivate_permission_deactivates_corporate_instead_of_deleting
- test_superadmin_can_manage_corporates_across_companies
- test_user_override_permission_allows_access_even_without_role_permission

### tests/Feature/Vehicles/VehicleCrudTest.php
- test_company_admin_with_permission_can_list_vehicles
- test_company_user_without_permission_cannot_list_vehicles
- test_company_admin_with_permission_can_create_vehicle_respecting_tenant
- test_tenant_mismatch_on_show_is_forbidden_for_non_superadmin
- test_company_admin_with_permission_can_update_vehicle
- test_company_admin_with_deactivate_permission_deactivates_vehicle_instead_of_deleting
- test_superadmin_can_manage_vehicles_across_companies
- test_user_override_permission_allows_access_even_without_role_permission

### tests/Feature/Auth/RolePermissionSyncServiceTest.php
- test_sync_creates_role_permissions_for_company_from_config
- test_sync_is_idempotent_and_does_not_duplicate_records
- test_sync_ignores_permissions_not_present_in_database

### tests/Feature/Auth/CompanyPermissionsEndpointsTest.php
- test_company_admin_can_view_available_permissions
- test_company_user_without_manage_permission_cannot_access_role_permissions
- test_company_admin_can_update_role_permissions_for_company_user_role
- test_company_admin_can_view_and_update_user_specific_permissions
- test_superadmin_cannot_manage_company_permissions

### tests/Feature/Auth/CompanyConfigEndpointsTest.php
- test_company_admin_with_permission_can_view_config
- test_company_user_without_permission_cannot_view_config
- test_company_admin_with_permission_can_update_config
- test_superadmin_cannot_update_company_config

### tests/Feature/Auth/CompanyOnboardingFlowTest.php
- test_full_company_onboarding_and_password_change_flow

### tests/Feature/Auth/PermissionSeederTest.php
- test_permissions_config_has_unique_names
- test_permission_seeder_creates_permissions_from_config

### tests/Feature/Auth/UserPermissionsTest.php
- test_superadmin_always_has_any_permission
- test_role_permissions_are_applied_when_no_user_override
- test_user_specific_permissions_override_role_permissions
- test_permission_is_denied_when_not_assigned_anywhere
- test_permissions_are_tenant_scoped

### tests/Feature/Auth/SecurityMiddlewaresTest.php
- test_pending_password_user_can_access_me_and_change_password_routes
- test_pending_password_user_is_blocked_from_protected_routes
- test_inactive_user_is_blocked_even_with_valid_token
- test_company_inactive_blocks_access_even_if_user_is_active

### tests/Feature/Auth/MultiTenantAuthTest.php
- test_superadmin_can_login_without_company_code
- test_company_user_can_login_with_valid_company_code
- test_non_superadmin_requires_company_code
- test_login_fails_with_invalid_company_code
- test_tenant_is_resolved_on_protected_route_for_company_user
- test_superadmin_has_null_tenant_on_protected_route
- test_logout_revokes_current_token

### tests/Feature/Drivers/DriverModelTest.php
- test_driver_belongs_to_company_and_partner
- test_driver_may_belong_to_user
- test_company_has_many_drivers
- test_partner_has_many_drivers
- test_is_active_casts_to_boolean

### tests/Feature/Drivers/DriverCrudTest.php
- test_company_admin_with_permission_can_list_drivers
- test_company_user_without_permission_cannot_list_drivers
- test_company_admin_with_permission_can_create_driver_respecting_tenant
- test_create_driver_fails_when_quota_is_exceeded
- test_tenant_mismatch_on_show_is_forbidden_for_non_superadmin
- test_company_admin_with_permission_can_update_driver
- test_company_admin_with_deactivate_permission_deactivates_driver_instead_of_deleting
- test_superadmin_can_manage_drivers_across_companies
- test_user_override_permission_allows_access_even_without_role_permission

### tests/Feature/Passengers/PassengerCrudTest.php
- test_company_admin_with_permission_can_list_passengers
- test_company_user_without_permission_cannot_list_passengers
- test_company_admin_with_permission_can_create_passenger_respecting_tenant
- test_tenant_mismatch_on_show_is_forbidden_for_non_superadmin
- test_company_admin_with_permission_can_update_passenger
- test_company_admin_with_deactivate_permission_deactivates_passenger_instead_of_deleting
- test_superadmin_can_manage_passengers_across_companies
- test_user_override_permission_allows_access_even_without_role_permission

Notas
-----
- Los tests cubren tanto unidad de modelos como integraciones de endpoints, policies, middlewares y sincronización de permisos.
- Muchos tests usan `RefreshDatabase`, factories, Sanctum y el `PermissionSeeder` para asegurar el estado base.
Console
=======

## app/Console/Commands/CreateCompanyAdmin.php

Resumen
-------
Comando de consola para crear una nueva compañía y un usuario COMPANY_ADMIN con contraseña temporal y envío de correo de invitación.

Firma de la clase
-----------------
class CreateCompanyAdmin extends Illuminate\Console\Command

Propiedades importantes
-----------------------
- protected $signature = 'rutiar:create-company-admin'
- protected $description = 'Crea una compañía y un usuario COMPANY_ADMIN con contraseña temporal'
- protected RolePermissionSyncService $rolePermissionSyncService (inyección en constructor)

Funciones públicas
------------------
- public function __construct(RolePermissionSyncService $rolePermissionSyncService)
  - Constructor que recibe el servicio `RolePermissionSyncService` por inyección.

- public function handle(): int
  - Flujo principal del comando:
    1. Pide al usuario: nombre de compañía, código (único), nombre del admin, email (único) y zona horaria.
    2. Muestra resumen y pide confirmación.
    3. Genera una contraseña temporal (Str::random(12)).
    4. Crea Company, CompanyConfig y User dentro de una transacción DB.
    5. Llama a `$this->rolePermissionSyncService->syncForCompany($company)` para sincronizar permisos por defecto.
    6. Envía `CompanyAdminInvitationMail` al email del admin con la contraseña temporal.
    7. Hace commit y muestra información; en caso de error hace rollback y devuelve FAILURE.
  - Devuelve int (constantes self::SUCCESS/self::FAILURE).

Funciones protegidas/auxiliares
-------------------------------
- protected function askRequired(string $question): string
  - Pregunta repetidamente al usuario hasta recibir una respuesta no vacía.
  - Devuelve el string ingresado.

- protected function askUniqueCompanyCode(): string
  - Solicita un código de compañía y valida que no exista ya en `companies`.
  - Reintenta hasta obtener uno único.

- protected function askUniqueEmail(string $question): string
  - Solicita un email, valida formato y unicidad en la tabla `users`.
  - Reintenta hasta obtener uno válido.

Notas
-----
- El comando depende de `RolePermissionSyncService` y de la existencia de la plantilla de correo `emails.company_admin_invitation`.
- Realiza validaciones básicas de integridad (unicidad) antes de crear registros.

