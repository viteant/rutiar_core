Controllers
===========

Resumen
-------
Este documento lista los controladores definidos en `app/Http/Controllers` y describe sus métodos clave, responsabilidades y comportamientos principales (autorizaciones, validaciones y respuestas JSON).

Listado y funciones clave
-------------------------

### app/Http/Controllers/Controller.php
- Clase base abstracta que usa traits `AuthorizesRequests` y `ValidatesRequests`.

### app/Http/Controllers/CorporateController.php
- index(Request $request): JsonResponse
  - Autoriza 'viewAny' sobre Corporate.
  - Filtra `is_active` y por `company_id` (si no es SUPERADMIN se limita al tenant del usuario).
  - Retorna JSON con 'data' => colección de corporates.

- store(Request $request): JsonResponse
  - Autoriza 'create'.
  - Define reglas de validación (name, tax_id, contact, is_active).
  - Si SUPERADMIN permite enviar `company_id` explicitamente; si no, asigna `company_id` del usuario.
  - Crea el `Corporate` y devuelve 201 con el recurso.

- show(Request $request, Corporate $corporate): JsonResponse
  - Autoriza 'view' y devuelve el corporate.

- update(Request $request, Corporate $corporate): JsonResponse
  - Autoriza 'update', valida campos `sometimes`, actualiza y retorna el recurso.

- destroy(Request $request, Corporate $corporate): JsonResponse
  - Autoriza 'delete' y desactiva (`is_active = false`) en lugar de borrar, devuelve 204.

### app/Http/Controllers/VehicleController.php
- index(Request $request): JsonResponse
  - Autoriza 'viewAny' sobre Vehicle.
  - Carga relaciones `company` y `partner`, filtra por tenant y opcional `partner_id`.

- store(Request $request): JsonResponse
  - Autoriza 'create'.
  - Reglas base (plate único, model, capacity).
  - Si SUPERADMIN puede indicar `company_id`, además `partner_id` debe pertenecer a company.
  - Verifica integridad de partner y crea Vehicle, devuelve 201.

- show(Request $request, Vehicle $vehicle): JsonResponse
  - Autoriza 'view', carga relaciones y devuelve recurso.

- update(Request $request, Vehicle $vehicle): JsonResponse
  - Autoriza 'update'.
  - Validaciones con Rule::unique ignorando el id actual.
  - Valida que `partner_id` pertenezca al mismo tenant, actualiza y devuelve recurso.

- destroy(Request $request, Vehicle $vehicle): JsonResponse
  - Autoriza 'delete', desactiva (is_active=false) y devuelve 204.

### app/Http/Controllers/CompanyPermissionController.php
- availablePermissions(Request $request): JsonResponse
  - Lista todas las filas de `permissions` ordenadas. Requiere permiso `manageRolePermissions` (policy `CompanyPermissionPolicy::manageRolePermissions`).

- listRolePermissions(Request $request): JsonResponse
  - Devuelve `role_permissions` del company agrupadas por rol.

- updateRolePermissions(string $role, Request $request): JsonResponse
  - Reemplaza permisos de un rol para la compañía actual.
  - Valida roles permitidos (no SUPERADMIN), valida nombres de permisos y persiste `RolePermission`.

- showUserPermissions(int $userId, Request $request): JsonResponse
  - Muestra permisos role-based y overrides user-specific para un usuario del mismo company.

- updateUserPermissions(int $userId, Request $request): JsonResponse
  - Reemplaza overrides de permisos para un usuario dentro de la compañía.

### app/Http/Controllers/DriverController.php
- index, store, show, update, destroy: JsonResponses
  - Lógica similar a otros controladores: autorización con policy `DriverPolicy`, validaciones, tenant scoping,
  - `store` y `update` incluyen validación de cuotas de drivers por partner (partner->effectiveDriverQuota()).

### app/Http/Controllers/CompanyConfigController.php
- show(Request $request): JsonResponse
  - Verifica tenant y permiso `viewSettings`. Crea config por defecto si no existe. Devuelve estructura con campos `planning_cutoff_time`, `default_waiting_minutes`, `allow_driver_reorder`, `driver_quota_default`, `settings`.

- update(Request $request): JsonResponse
  - Autoriza `updateSettings`. Valida campos y persiste cambios en `CompanyConfig` del tenant.

### app/Http/Controllers/PartnerController.php
- index, store, show, update, destroy: JsonResponses
  - `store` y `update` validan `company_id` (solo SUPERADMIN puede enviar `company_id`), crean/actualizan Partner y respetan tenant.
  - `destroy` desactiva (`is_active = false`).

### app/Http/Controllers/PassengerController.php
- index, store, show, update, destroy: JsonResponses
  - Similar a otros: tenant scoping, validaciones (geolocalización, corporate perteneciente al tenant), soft-delete (is_active=false).

### app/Http/Controllers/Auth/AuthController.php
- login(LoginRequest $request): JsonResponse
  - Lógica de login con RateLimiter (5 intentos), verificación de password, verificación de `is_active`.
  - Multi-tenant: SUPERADMIN puede entrar sin `company_code`; usuarios normales requieren `company_code` que debe coincidir con `user->company_id`.
  - Crea token personal (`createToken($deviceName)`), devuelve token y datos del usuario + company.

- me(Request $request): JsonResponse
  - Devuelve `user` y `tenant` (resuelto por middleware).

- logout(Request $request): JsonResponse
  - Borra tokens del usuario actual.

- changePassword(ChangePasswordRequest $request): JsonResponse
  - Verifica `current_password`, actualiza password (hashed), setea `must_change_password=false`.

- Métodos protegidos para rate limiting: ensureIsNotRateLimited, throttleKey


Notas generales
--------------
- Todos los controladores devuelven JSON y usan policies para autorización.
- En endpoints que modifican o acceden a recursos tenant-scoped hay checks explícitos para asegurar coincidencia de `company_id` a menos que el usuario sea SUPERADMIN.

