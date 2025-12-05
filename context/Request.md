# Http/Requests/Auth/LoginRequest.php

*Request para validar credenciales de inicio de sesión multi-tenant.*

Funciones:
- `authorize(): bool` - Devuelve `true`, permitiendo que cualquiera pueda intentar autenticarse (la seguridad está en el controlador y middleware).
- `rules(): array` - Reglas de validación para el login:
  - `email` => `required|string|email`
  - `password` => `required|string`
  - `device_name` => `required|string|max:255`
  - `company_code` => `nullable|string|max:255`

---

# Http/Requests/Auth/ChangePasswordRequest.php

*Request para validar el cambio de contraseña del usuario autenticado.*

Funciones:
- `authorize(): bool` - Devuelve `true`, asumiendo que la autenticación ya fue gestionada por Sanctum.
- `rules(): array` - Reglas de validación para cambio de contraseña:
  - `current_password` => `required|string`
  - `password` => `required|string|min:8|confirmed` (requiere `password_confirmation` concordante).

---

# Http/Requests/Transport/StorePartnerRequest.php

*Request para validar la creación de un partner (socio de transporte).* 

Funciones:
- `authorize(): bool` - Generalmente `true`; la autorización se realiza por policy en el controlador.
- `rules(): array` - Reglas típicas (a partir del controlador original):
  - `name` => `required|string|max:150`
  - `code` => `required|string|max:50`
  - `tax_id` => `nullable|string|max:50`
  - `is_active` => `sometimes|boolean`
  - `driver_quota` => `nullable|integer|min:0`
  - (Para SUPERADMIN, el controlador espera `company_id` válido; la regla puede incluir `company_id => required|integer|exists:companies,id`.)

---

# Http/Requests/Transport/UpdatePartnerRequest.php

*Request para validar la actualización parcial de un partner existente.*

Funciones:
- `authorize(): bool` - `true`; la verificación de permisos/tenant se hace en la policy `PartnerPolicy`.
- `rules(): array` - Reglas típicas de actualización:
  - `name` => `sometimes|string|max:150`
  - `code` => `sometimes|string|max:50`
  - `tax_id` => `sometimes|nullable|string|max:50`
  - `is_active` => `sometimes|boolean`
  - `driver_quota` => `sometimes|nullable|integer|min:0`

---

# Http/Requests/Transport/StoreDriverRequest.php

*Request para validar la creación de un driver (conductor).* 

Funciones:
- `authorize(): bool` - `true`; el controlador usa `DriverPolicy` para asegurar permisos.
- `rules(): array` - Reglas base (según el controlador):
  - `full_name` => `required|string|max:150`
  - `phone` => `nullable|string|max:50`
  - `license_number` => `nullable|string|max:100`
  - `is_active` => `sometimes|boolean`
  - `user_id` => `nullable|integer|exists:users,id`
  - Para SUPERADMIN, se añade `company_id` y una regla de `partner_id` con `exists:partners,id` limitado por `company_id`; para no-SUPERADMIN, `partner_id` es `required|integer|exists:partners,id` en la compañía del usuario.

---

# Http/Requests/Transport/UpdateDriverRequest.php

*Request para validar la actualización de un driver existente.*

Funciones:
- `authorize(): bool` - `true`; la autorización fina se basa en `DriverPolicy`.
- `rules(): array` - Reglas comunes:
  - `full_name` => `sometimes|required|string|max:150`
  - `phone` => `sometimes|nullable|string|max:50`
  - `license_number` => `sometimes|nullable|string|max:100`
  - `is_active` => `sometimes|boolean`
  - `user_id` => `sometimes|nullable|integer|exists:users,id`
  - `partner_id` => `sometimes|required|integer|exists:partners,id` con restricción a la misma compañía del driver.

---

# Http/Requests/Transport/StoreVehicleRequest.php

*Request para validar la creación de un vehículo.*

Funciones:
- `authorize(): bool` - `true`; se usa `VehiclePolicy` para la autorización.
- `rules(): array` - Reglas típicas:
  - `plate` => `required|string|max:20|unique:vehicles,plate`
  - `model` => `nullable|string|max:100`
  - `capacity` => `nullable|integer|min:1`
  - `is_active` => `sometimes|boolean`
  - Para SUPERADMIN, además `company_id` y `partner_id` con `exists:partners,id` limitado por `company_id`; para otros usuarios, `partner_id` requerido y validado en el company actual.

---

# Http/Requests/Transport/UpdateVehicleRequest.php

*Request para validar la actualización de un vehículo.*

Funciones:
- `authorize(): bool` - `true`; el control de acceso se realiza vía `VehiclePolicy`.
- `rules(): array` - Reglas esperadas:
  - `plate` => `sometimes|required|string|max:20|unique:vehicles,plate,{id}` (ignorando el vehículo actual)
  - `model` => `sometimes|nullable|string|max:100`
  - `capacity` => `sometimes|nullable|integer|min:1`
  - `is_active` => `sometimes|boolean`
  - `partner_id` => `sometimes|required|integer|exists:partners,id` limitado a la misma compañía.

---

# Http/Requests/Company/StorePassengerRequest.php

*Request para validar la creación de un pasajero en el contexto de compañía.*

Funciones:
- `authorize(): bool` - `true`; se combina con `PassengerPolicy` para el control de acceso.
- `rules(): array` - Reglas (de acuerdo al controlador):
  - `company_id` => `required|integer|exists:companies,id` (normalizado en el controlador según rol/tenant).
  - `corporate_id` => `required|integer|exists:corporates,id` dentro de la misma compañía.
  - `full_name` => `required|string|max:150`
  - `employee_code` => `nullable|string|max:80`
  - `document_id` => `nullable|string|max:80`
  - `home_address` => `nullable|string`
  - `home_lat` => `nullable|numeric|between:-90,90`
  - `home_lng` => `nullable|numeric|between:-180,180`
  - `shift_code` => `nullable|string|max:50`
  - `is_active` => `sometimes|boolean`

---

# Http/Requests/Company/UpdatePassengerRequest.php

*Request para validar la actualización de un pasajero.*

Funciones:
- `authorize(): bool` - `true`.
- `rules(): array` - Similar a `StorePassengerRequest`, pero con campos `sometimes` y `required` según el caso:
  - `full_name` => `sometimes|required|string|max:150`
  - `employee_code` => `sometimes|nullable|string|max:80`
  - `document_id` => `sometimes|nullable|string|max:80`
  - `home_address` => `sometimes|nullable|string`
  - `home_lat` => `sometimes|nullable|numeric|between:-90,90`
  - `home_lng` => `sometimes|nullable|numeric|between:-180,180`
  - `shift_code` => `sometimes|nullable|string|max:50`
  - `is_active` => `sometimes|boolean`
  - `corporate_id` => `sometimes|required|integer|exists:corporates,id` con filtro por compañía.

---

# Http/Requests/Company/StoreCorporateRequest.php

*Request para validar la creación de un corporate (cliente corporativo).* 

Funciones:
- `authorize(): bool` - `true`; la autorización se delega a `CorporatePolicy`.
- `rules(): array` - Reglas típicas:
  - `company_id` => `nullable|integer|exists:companies,id` (obligatorio para SUPERADMIN, opcional para otros, se fuerza según tenant).
  - `name` => `required|string|max:150`
  - `tax_id` => `nullable|string|max:50`
  - `contact_name` => `nullable|string|max:150`
  - `contact_email` => `nullable|string|email|max:150`
  - `is_active` => `sometimes|boolean`

---

# Http/Requests/Company/UpdateCorporateRequest.php

*Request para validar la actualización de un corporate.*

Funciones:
- `authorize(): bool` - `true`.
- `rules(): array` - Reglas típicas de actualización parcial:
  - `name` => `sometimes|required|string|max:150`
  - `tax_id` => `sometimes|nullable|string|max:50`
  - `contact_name` => `sometimes|nullable|string|max:150`
  - `contact_email` => `sometimes|nullable|string|email|max:150`
  - `is_active` => `sometimes|boolean`

---

# Http/Requests/Company/UpdateCompanyConfigRequest.php

*Request para validar la actualización de configuración de compañía.*

Funciones:
- `authorize(): bool` - `true`; `CompanyPermissionPolicy` controla quién puede actualizar.
- `rules(): array` - Reglas usadas en `CompanyConfigController`:
  - `planning_cutoff_time` => `nullable|date_format:H:i`
  - `default_waiting_minutes` => `nullable|integer|min:0|max:1440`
  - `allow_driver_reorder` => `nullable|boolean`
  - `driver_quota_default` => `nullable|integer|min:0|max:100000`
  - `settings` => `nullable|array`

---

# Http/Requests/Company/UpdateRolePermissionsRequest.php

*Request para validar la lista de permisos que se asignarán a un rol dentro de la compañía.* 

Funciones:
- `authorize(): bool` - `true`; el controlador hace `resolveCompanyOrAbort('manageRolePermissions')`.
- `rules(): array` - Reglas de validación:
  - `permissions` => `array`
  - `permissions.*` => `string|distinct`

---

# Http/Requests/Company/UpdateUserPermissionsRequest.php

*Request para validar los permisos específicos de un usuario dentro de una compañía.*

Funciones:
- `authorize(): bool` - `true`; el controlador usa autorización adicional con `manageUserPermissions`.
- `rules(): array` - Reglas equivalentes a las de `UpdateRolePermissionsRequest`:
  - `permissions` => `array`
  - `permissions.*` => `string|distinct`
