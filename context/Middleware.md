# Middleware

## Http/Middleware/ResolveTenantFromUser.php

*Middleware que resuelve la compañía (tenant) a partir del usuario autenticado y la inyecta en la app y en la request.*

Funciones:
- `handle(Request $request, Closure $next): Response` - Obtiene el usuario autenticado. Si no hay usuario, continúa sin cambios. Si el usuario es SUPERADMIN, limpia cualquier instancia previa de tenant (`app()->forgetInstance('tenant')`) y setea `tenant` a `null` en los atributos de la request. Para otros usuarios, obtiene `company` desde la relación del usuario; si no existe o está inactiva, aborta con 403 y mensaje `Company is not active or not assigned.`. Si es válida, registra la compañía en el contenedor (`app()->instance('tenant', $company)`) y la adjunta a la request (`$request->attributes->set('tenant', $company)`), luego continúa la cadena de middleware.

---

## Http/Middleware/EnsureUserIsActive.php

*Middleware que bloquea el acceso a usuarios inactivos incluso si poseen un token válido.*

Funciones:
- `handle(Request $request, Closure $next): Response` - Obtiene el usuario autenticado. Si no hay usuario, permite continuar. Si el usuario existe y `is_active` es `false`, devuelve una respuesta JSON 403 con `message: 'User is inactive.'` y `code: 'USER_INACTIVE'`. En caso contrario, deja pasar la request al siguiente middleware/controlador.

---

## Http/Middleware/EnsurePasswordIsChanged.php

*Middleware que obliga a los usuarios marcados con `must_change_password` a cambiar su contraseña antes de acceder al resto de rutas protegidas.*

Funciones:
- `handle(Request $request, Closure $next): Response` - Obtiene el usuario autenticado. Si no hay usuario, permite continuar. Si el usuario no tiene el flag `must_change_password`, permite continuar. Si sí lo tiene, permite únicamente el acceso a las rutas `api/auth/me`, `api/auth/logout` y `api/auth/change-password` (métodos expuestos para ver su perfil y cambiar contraseña). Para cualquier otra ruta, devuelve respuesta JSON 423 con `message: 'Debes cambiar tu contraseña antes de continuar.'` y `code: 'PASSWORD_CHANGE_REQUIRED'`.

---

Notas:
- Estos middlewares se combinan para implementar seguridad: inactividad de usuario, requisito de cambio de contraseña y resolución de tenant antes de ejecutar controladores.
