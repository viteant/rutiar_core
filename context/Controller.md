# Http/Controllers/Controller.php

*Clase base abstracta de controladores, provee helpers de autorización y validación.*

Traits:
- `AuthorizesRequests` - Proporciona el método `authorize()` y helpers para verificar políticas de autorización.
- `ValidatesRequests` - Proporciona el método `validate()` y helpers para validar peticiones HTTP.

Funciones:
- (Heredadas a través de los traits; esta clase no define métodos adicionales propios.)

---

# Http/Controllers/BaseApiController.php

*Controlador base para endpoints API multi-tenant que trabajan con usuario autenticado y compañía (tenant).* 

Traits:
- (Ninguno directamente; hereda traits desde `Controller`.)

Funciones:
- `protected function user(): User` - Devuelve el usuario autenticado actual (`request()->user()`).
- `protected function tenant(): ?Company` - Devuelve la compañía (tenant) resuelta por el middleware `ResolveTenantFromUser` desde `request()->attributes->get('tenant')`.
- `protected function isSuperAdmin(): bool` - Indica si el usuario actual tiene rol `SUPERADMIN`.
- `protected function withCompanyId(array $data): array` - Normaliza el `company_id` dentro de un array de datos: si es SUPERADMIN respeta el `company_id` suministrado; si no, fuerza el `company_id` del tenant actual.

---

# Http/Controllers/Auth/AuthController.php

*Controlador de autenticación; gestiona login multi-tenant, obtención de perfil, logout y cambio de contraseña.*

Traits:
- (Hereda `AuthorizesRequests` y `ValidatesRequests` desde `Controller`.)

Funciones:
- `login(LoginRequest $request): JsonResponse` - Valida credenciales, aplica rate limiting, verifica estado (`is_active`), resuelve `company_code` para usuarios no SUPERADMIN, genera token Sanctum (revocando tokens previos con el mismo `device_name`) y devuelve `token`, `token_type` y datos del `user` (incluyendo compañía asociada si aplica).
- `me(Request $request): JsonResponse` - Devuelve JSON con `user` autenticado y `tenant` (compañía actual o null para SUPERADMIN) tomado de los atributos de la request.
- `logout(Request $request): JsonResponse` - Elimina todos los `personal_access_tokens` (`$user->tokens()->delete()`) del usuario autenticado y devuelve mensaje de confirmación.
- `changePassword(ChangePasswordRequest $request): JsonResponse` - Verifica la contraseña actual (`current_password`), actualiza `password`, marca `must_change_password=false` y devuelve mensaje de éxito.
- `protected ensureIsNotRateLimited(Request $request): void` - Comprueba que no se haya excedido el número de intentos de login (máximo 5) usando `RateLimiter`; si lo supera lanza `abort(429, ...)`.
- `protected throttleKey(Request $request): string` - Construye la clave de rate limiting a partir de `email` e IP (`email|ip`).

---

# Http/Controllers/Company/CompanyPermissionController.php

*Controlador para gestionar permisos (roles y usuarios) a nivel de compañía.*

Traits:
- (Hereda traits desde `BaseApiController` → `Controller`.)

Funciones:
- `availablePermissions(): JsonResponse` - Lista todos los permisos registrados (`permissions` table) y retorna `id`, `name`, `description` para cada uno. Requiere autorización `manageRolePermissions` sobre la compañía actual.
- `listRolePermissions(): JsonResponse` - Devuelve un JSON agrupando permisos por rol (`role_permissions` table) para la compañía actual. Cada entrada contiene el rol como clave y un array de nombres de permisos.
- `updateRolePermissions(string $role, UpdateRolePermissionsRequest $request): JsonResponse` - Reemplaza el conjunto de permisos asociado a un rol (`COMPANY_ADMIN`, `COMPANY_USER`, `PARTNER_ADMIN`, `DRIVER`) en la compañía actual. Valida que los nombres de permisos existan en BD y devuelve error 422 si hay nombres desconocidos.
- `showUserPermissions(int $userId): JsonResponse` - Muestra para un usuario dado: id, rol, permisos derivados de rol (`role_permissions`) y overrides específicos (`user_permissions`) dentro de la compañía actual. Verifica que el usuario objetivo pertenezca a la misma compañía.
- `updateUserPermissions(int $userId, UpdateUserPermissionsRequest $request): JsonResponse` - Reemplaza los permisos específicos de un usuario (overrides) en la compañía actual. Asegura que no se puedan gestionar permisos de usuarios `SUPERADMIN` y valida nombres de permisos.
- `protected resolveCompanyOrAbort(string $ability): Company` - Resuelve la compañía actual (`tenant()`), lanza 403 si no existe y llama a `authorize($ability, $company)`; devuelve la compañía autorizada.
- `protected resolvePermissionsOrFail(array $permissionNames): Collection` - Mapea una lista de nombres de permisos a sus IDs; si alguno no existe, responde con JSON 422 y mensaje `Unknown permissions: ...`.

---

# Http/Controllers/Company/CompanyConfigController.php

*Controlador para ver y actualizar la configuración operativa de una compañía.*

Traits:
- (Hereda traits desde `BaseApiController` → `Controller`.)

Funciones:
- `show(): JsonResponse` - Obtiene o crea la configuración (`CompanyConfig`) de la compañía actual con valores por defecto si no existe y devuelve un JSON serializado (`planning_cutoff_time`, `default_waiting_minutes`, `allow_driver_reorder`, `driver_quota_default`, `settings`). Autoriza con `viewSettings` sobre la compañía actual.
- `update(UpdateCompanyConfigRequest $request): JsonResponse` - Valida y actualiza campos de `CompanyConfig` para la compañía actual (`planning_cutoff_time`, `default_waiting_minutes`, `allow_driver_reorder`, `driver_quota_default`, `settings`). Autoriza con `updateSettings`.
- `protected resolveCompanyOrAbort(string $ability): Company` - Igual que en `CompanyPermissionController`, resuelve tenant y autoriza.
- `protected getOrCreateConfig(Company $company): CompanyConfig` - Devuelve la configuración asociada a la compañía, creándola con valores por defecto si no existe.
- `protected serializeConfig(CompanyConfig $config): array` - Convierte el modelo de configuración en un arreglo listo para respuesta JSON (formatea hora a `H:i:s`).

---

# Http/Controllers/Company/PassengerController.php

*Controlador para CRUD de pasajeros dentro del contexto de una compañía.*

Traits:
- (Hereda traits desde `BaseApiController` → `Controller`.)

Funciones:
- `index(Request $request): JsonResponse` - Lista pasajeros activos (`Passenger::active()`) con relación `corporate`, filtrando por `company_id` según tenant (o por query param si es SUPERADMIN) y opcionalmente por `corporate_id`.
- `store(StorePassengerRequest $request): JsonResponse` - Crea un pasajero validando datos básicos y asegurando que el `corporate_id` pertenezca a la misma compañía (`Corporate::where('company_id', $companyId)`). Devuelve pasajero creado con `corporate` cargado.
- `show(Passenger $passenger): JsonResponse` - Devuelve un pasajero concreto con la relación `corporate` cargada, respetando la policy `view`.
- `update(UpdatePassengerRequest $request, Passenger $passenger): JsonResponse` - Actualiza campos del pasajero y, si se cambia `corporate_id`, verifica que el corporate pertenezca a la misma compañía. Devuelve el pasajero actualizado con `corporate` cargado.
- `destroy(Passenger $passenger): JsonResponse` - Desactiva (soft delete) al pasajero a través del método `deactivate()` y devuelve 204.

---

# Http/Controllers/Company/CorporateController.php

*Controlador para CRUD de cuentas corporativas (clientes corporativos) por compañía.*

Traits:
- (Hereda traits desde `BaseApiController` → `Controller`.)

Funciones:
- `index(Request $request): JsonResponse` - Lista corporates activos (`Corporate::active()`), filtrando por tenant o por `company_id` si es SUPERADMIN.
- `store(StoreCorporateRequest $request): JsonResponse` - Crea un corporate usando `withCompanyId()` para fijar `company_id` según el contexto (tenant o explícito para SUPERADMIN). Devuelve entidad creada.
- `show(Corporate $corporate): JsonResponse` - Devuelve un corporate específico tras pasar la policy `view`.
- `update(UpdateCorporateRequest $request, Corporate $corporate): JsonResponse` - Actualiza nombre, tax_id y datos de contacto con la data validada.
- `destroy(Corporate $corporate): JsonResponse` - Desactiva el corporate usando `deactivate()` (soft delete) y devuelve 204.

---

# Http/Controllers/Transport/PartnerController.php

*Controlador para CRUD de partners (socios de transporte) dentro del contexto multi-tenant.*

Traits:
- (Hereda traits desde `BaseApiController` → `Controller`.)

Funciones:
- `index(Request $request): JsonResponse` - Lista partners ordenados por `name`; si es SUPERADMIN puede filtrar por `company_id`, si no, se restringe a la compañía del tenant.
- `store(StorePartnerRequest $request): JsonResponse` - Crea un partner con campos `name`, `code`, `tax_id`, `is_active`, `driver_quota` y resuelve `company_id` vía `withCompanyId()`. Devuelve datos resumidos del partner.
- `show(Partner $partner): JsonResponse` - Devuelve un partner específico filtrando campos básicos (id, company_id, name, code, tax_id, is_active, driver_quota, timestamps).
- `update(Partner $partner, UpdatePartnerRequest $request): JsonResponse` - Actualiza campos de partner con datos validados.
- `destroy(Partner $partner): JsonResponse` - Desactiva el partner (`is_active=false`) y guarda; devuelve 204.

---

# Http/Controllers/Transport/DriverController.php

*Controlador para CRUD de drivers (conductores) con soporte de cuotas por partner y filtros por compañía/partner.*

Traits:
- `HasTransportIndexFilters` - Trait que aplica filtros estándar por `company_id` y `partner_id` a consultas de transporte.
- (Hereda traits desde `BaseApiController` → `Controller`.)

Funciones:
- `index(Request $request): JsonResponse` - Lista drivers activos (`Driver::active()`), cargando relaciones `company`, `partner`, `user`, y aplica filtros de compañía y partner usando el trait.
- `store(StoreDriverRequest $request): JsonResponse` - Crea un driver con datos validados, resuelve `company_id` vía `withCompanyId()`, busca el `Partner` correspondiente dentro de la compañía y verifica que no se exceda la cuota de drivers (`assertPartnerHasDriverQuota`). Crea el registro y devuelve el driver con relaciones cargadas.
- `show(Driver $driver): JsonResponse` - Devuelve driver específico con `company`, `partner` y `user` cargados, tras pasar la policy `view`.
- `update(UpdateDriverRequest $request, Driver $driver): JsonResponse` - Actualiza datos del driver y, si cambia de partner, vuelve a verificar la cuota de drivers (`assertPartnerHasDriverQuota`). Devuelve driver actualizado con relaciones.
- `destroy(Driver $driver): JsonResponse` - Desactiva el driver mediante `deactivate()` y devuelve 204.
- `protected assertPartnerHasDriverQuota(Partner $partner): void` - Lanza `HttpResponseException` 422 si el partner ha alcanzado su cuota de drivers efectivos (`effectiveDriverQuota()` vs `drivers()->count()`).

---

# Http/Controllers/Transport/VehicleController.php

*Controlador para CRUD de vehículos asociados a partners y compañías, con filtros por compañía y partner.*

Traits:
- `HasTransportIndexFilters` - Reutiliza filtros por `company_id` y `partner_id`.
- (Hereda traits desde `BaseApiController` → `Controller`.)

Funciones:
- `index(Request $request): JsonResponse` - Lista vehículos activos (`Vehicle::active()`) con relaciones `company` y `partner`, aplicando filtros de tenant y partner.
- `store(StoreVehicleRequest $request): JsonResponse` - Crea vehículo resolviendo `company_id` con `withCompanyId()`. Devuelve vehículo creado con relaciones.
- `show(Vehicle $vehicle): JsonResponse` - Devuelve vehículo específico con `company` y `partner` cargados.
- `update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse` - Actualiza datos del vehículo (incluyendo `partner_id` si se cambia) y devuelve entidad actualizada con relaciones.
- `destroy(Vehicle $vehicle): JsonResponse` - Desactiva el vehículo usando `deactivate()` y devuelve 204.

---

# Http/Controllers/Transport/Concerns/HasTransportIndexFilters.php

*Trait reusable para aplicar filtros estándar por compañía y partner en índices de transporte.*

Funciones:
- `protected applyCompanyFilter(Builder $query, Request $request): Builder` - Si es SUPERADMIN y existe `company_id` en la request, filtra por ese id; en caso contrario, impone el `company_id` del tenant actual (abortando 403 si no hay tenant).
- `protected applyPartnerFilter(Builder $query, Request $request): Builder` - Si `partner_id` viene en la request, filtra la consulta por ese partner.

---

## Traits (sección de resumen)

### Desde Controladores
- `AuthorizesRequests` - Trait de Laravel que proporciona helpers de autorización (`authorize`, `authorizeForUser`, etc.).
- `ValidatesRequests` - Trait de Laravel que agrupa helpers para validación de requests.
- `HasTransportIndexFilters` - Trait propio que aplica filtros por `company_id` y `partner_id` a consultas Eloquent para recursos de transporte.
