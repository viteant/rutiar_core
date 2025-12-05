# Rutas de la aplicación

Este documento lista todas las rutas definidas en el proyecto (archivos `routes/api.php`, `routes/web.php`, `routes/console.php`) con: método HTTP / comando de consola, URI o nombre, controlador y método (si aplica), middleware aplicado, nombre de la ruta (si existe) y una breve descripción de su propósito.

---

## routes/api.php

Todas las rutas del archivo `routes/api.php` están agrupadas en su mayoría bajo el middleware `auth:sanctum` (autenticación vía Laravel Sanctum). A continuación cada ruta:

### Grupo: prefix `auth`

- POST /api/auth/login
  - Nombre: `auth.login`
  - Controlador: `App\Http\Controllers\Auth\AuthController@login`
  - Middleware: ninguna (ruta pública)
  - Descripción: Inicia sesión y devuelve credenciales/token (normalmente devuelve token de Sanctum y datos del usuario).
  - Parámetros: credenciales (por ejemplo `email`, `password`).

- GET /api/auth/me
  - Nombre: `auth.me`
  - Controlador: `App\Http\Controllers\Auth\AuthController@me`
  - Middleware: `auth:sanctum`
  - Descripción: Devuelve los datos del usuario autenticado.

- POST /api/auth/logout
  - Nombre: `auth.logout`
  - Controlador: `App\Http\Controllers\Auth\AuthController@logout`
  - Middleware: `auth:sanctum`
  - Descripción: Cierra la sesión/revoca el token del usuario autenticado.

- POST /api/auth/change-password
  - Nombre: `auth.change-password`
  - Controlador: `App\Http\Controllers\Auth\AuthController@changePassword`
  - Middleware: `auth:sanctum`
  - Descripción: Permite al usuario autenticado cambiar su contraseña (requiere password actual y nuevo).


### Rutas protegidas por `auth:sanctum` (grupo principal)

- GET /api/company/config
  - Controlador: `App\Http\Controllers\CompanyConfigController@show`
  - Middleware: `auth:sanctum`
  - Descripción: Muestra la configuración de la compañía asociada al usuario/tenant.

- PUT /api/company/config
  - Controlador: `App\Http\Controllers\CompanyConfigController@update`
  - Middleware: `auth:sanctum`
  - Descripción: Actualiza la configuración de la compañía.


#### Prefijo: `company/permissions`
- GET /api/company/permissions/available
  - Controlador: `App\Http\Controllers\CompanyPermissionController@availablePermissions`
  - Middleware: `auth:sanctum`
  - Descripción: Devuelve todas las permisos disponibles en la plataforma (útil para asignar/mostrar checklists).

- GET /api/company/permissions/roles
  - Controlador: `App\Http\Controllers\CompanyPermissionController@listRolePermissions`
  - Middleware: `auth:sanctum`
  - Descripción: Lista roles con sus permisos asociados en la compañía.

- PUT /api/company/permissions/roles/{role}
  - Controlador: `App\Http\Controllers\CompanyPermissionController@updateRolePermissions`
  - Middleware: `auth:sanctum`
  - Descripción: Actualiza los permisos asociados a un rol identificando `{role}` (id o slug según implementación).
  - Parámetros: `{role}` (identificador del rol), body con permisos a asignar.

- GET /api/company/permissions/users/{userId}
  - Controlador: `App\Http\Controllers\CompanyPermissionController@showUserPermissions`
  - Middleware: `auth:sanctum`
  - Descripción: Muestra los permisos efectivos de un usuario dentro de la compañía (por id `{userId}`).

- PUT /api/company/permissions/users/{userId}
  - Controlador: `App\Http\Controllers\CompanyPermissionController@updateUserPermissions`
  - Middleware: `auth:sanctum`
  - Descripción: Actualiza permisos personalizados de un usuario (asignación directa de permisos).
  - Parámetros: `{userId}` y body con permisos a añadir/quitar.


### Recursos API (apiResource)

Las siguientes rutas usan `Route::apiResource(...)`, por lo que tienen los métodos RESTful estándar: index (GET /resource), store (POST /resource), show (GET /resource/{id}), update (PUT/PATCH /resource/{id}), destroy (DELETE /resource/{id}).

- Resource: `partners` (limitado a `index, store, show, update, destroy`)
  - GET /api/partners -> `PartnerController@index` (listar)
  - POST /api/partners -> `PartnerController@store` (crear)
  - GET /api/partners/{partner} -> `PartnerController@show` (mostrar uno)
  - PUT/PATCH /api/partners/{partner} -> `PartnerController@update` (actualizar)
  - DELETE /api/partners/{partner} -> `PartnerController@destroy` (eliminar)
  - Middleware: `auth:sanctum`

- Resource: `drivers` (completo)
  - GET /api/drivers -> `DriverController@index`
  - POST /api/drivers -> `DriverController@store`
  - GET /api/drivers/{driver} -> `DriverController@show`
  - PUT/PATCH /api/drivers/{driver} -> `DriverController@update`
  - DELETE /api/drivers/{driver} -> `DriverController@destroy`
  - Middleware: `auth:sanctum`

- Resource: `vehicles` (completo)
  - GET /api/vehicles -> `VehicleController@index`
  - POST /api/vehicles -> `VehicleController@store`
  - GET /api/vehicles/{vehicle} -> `VehicleController@show`
  - PUT/PATCH /api/vehicles/{vehicle} -> `VehicleController@update`
  - DELETE /api/vehicles/{vehicle} -> `VehicleController@destroy`
  - Middleware: `auth:sanctum`

- Resource: `corporates` (completo)
  - GET /api/corporates -> `CorporateController@index`
  - POST /api/corporates -> `CorporateController@store`
  - GET /api/corporates/{corporate} -> `CorporateController@show`
  - PUT/PATCH /api/corporates/{corporate} -> `CorporateController@update`
  - DELETE /api/corporates/{corporate} -> `CorporateController@destroy`
  - Middleware: `auth:sanctum`

- Resource: `passengers` (completo)
  - GET /api/passengers -> `PassengerController@index`
  - POST /api/passengers -> `PassengerController@store`
  - GET /api/passengers/{passenger} -> `PassengerController@show`
  - PUT/PATCH /api/passengers/{passenger} -> `PassengerController@update`
  - DELETE /api/passengers/{passenger} -> `PassengerController@destroy`
  - Middleware: `auth:sanctum`


### Ruta de ejemplo tenant

- GET /api/tenant-example
  - Nombre: `tenant.example`
  - Controlador: Closure (función anónima definida en `api.php`)
  - Middleware: `auth:sanctum`
  - Descripción: Ruta de ejemplo que devuelve el `user_id` del usuario autenticado y el atributo `tenant` del request (probablemente establecido por un middleware multi-tenant).


---

## routes/web.php

- GET /
  - Controlador: Closure que devuelve la vista `welcome`.
  - Middleware: Web middleware por defecto (session, csrf, etc.).
  - Descripción: Página de inicio (vista welcome).


---

## routes/console.php (comandos Artisan definidos)

- Comando: `inspire`
  - Definición: `Artisan::command('inspire', function () { $this->comment(Inspiring::quote()); })->purpose('Display an inspiring quote');`
  - Descripción: Comando de consola que imprime una cita inspiradora. Propósito/documentación: "Display an inspiring quote".


---

### Notas generales

- Prefijo `api`: Las rutas definidas en `routes/api.php` se registran por defecto bajo el prefijo `/api` cuando se usan las configuraciones por defecto de Laravel, por eso las URIs listadas arriba incluyen `/api/...`.
- Middleware `auth:sanctum`: La mayoría de las rutas de `api.php` están protegidas por este middleware. Asegúrate de enviar el token/cookie de autenticación apropiado al probarlas.
- Parámetros en URIs: `{role}`, `{userId}`, `{partner}`, `{driver}`, `{vehicle}`, `{corporate}`, `{passenger}` indican parámetros de ruta; su tipo y validación dependen de los `Requests` y controladores.

Si quieres, puedo también:
- Generar una tabla en formato CSV/JSON con todas las rutas para importarlas en herramientas de documentación.
- Añadir ejemplos de request/responses basados en los controladores (si quieres que lea los controladores para extraer su comportamiento y respuestas esperadas).

---

Documento generado automáticamente a partir de `routes/api.php`, `routes/web.php` y `routes/console.php`.

