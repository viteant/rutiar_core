Middleware
==========

Resumen
-------
Descripciones de los middlewares definidos en `app/Http/Middleware` y su comportamiento.

Listado
-------

### app/Http/Middleware/EnsurePasswordIsChanged.php
- Método principal: handle(Request $request, Closure $next): Response
- Propósito: bloquear peticiones de usuarios que tienen `must_change_password = true` excepto rutas explícitas relacionadas con autenticación y cambio de contraseña.
- Comportamiento:
  - Si no hay usuario autenticado -> pasa al siguiente.
  - Si `must_change_password` es false -> pasa al siguiente.
  - Permite rutas: `api/auth/me`, `api/auth/logout`, `api/auth/change-password`.
  - Para cualquier otra ruta devuelve 423 con código 'PASSWORD_CHANGE_REQUIRED' y mensaje indicando que debe cambiar contraseña.

### app/Http/Middleware/ResolveTenantFromUser.php
- Método: handle(Request $request, Closure $next): Response
- Propósito: resolver el tenant (Company) desde el usuario autenticado y exponerlo en el contenedor de la app y en los atributos de la request.
- Comportamiento:
  - Si no hay usuario autenticado -> pasa.
  - Si usuario SUPERADMIN -> `tenant` se resuelve a null (app()->forgetInstance('tenant')).
  - Si usuario normal: obtiene `$company = $user->company`; si company no existe o `!is_active` aborta 403; registra `app()->instance('tenant', $company)` y setea `request->attributes->set('tenant', $company)`.

### app/Http/Middleware/EnsureUserIsActive.php
- Método: handle(Request $request, Closure $next): Response
- Propósito: bloquear usuarios desactivados.
- Comportamiento:
  - Si no hay usuario -> pasa.
  - Si `$user->is_active` es false -> responde 403 con código 'USER_INACTIVE'.
  - Si está activo -> pasa la request.

Notas
-----
- Estos middlewares implementan seguridad multi-tenant y políticas de acceso centralizadas (usuario/empresa activa, cambio obligatorio de contraseña, tenant resolution).

