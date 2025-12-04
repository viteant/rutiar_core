Requests
========

Resumen
-------
Lista de FormRequest definidos en `app/Http/Requests` y reglas de validación.

### app/Http/Requests/Auth/LoginRequest.php
- authorize(): bool -> true
- rules(): array
  - 'email' => ['required', 'string', 'email']
  - 'password' => ['required', 'string']
  - 'device_name' => ['required', 'string', 'max:255']
  - 'company_code' => ['nullable', 'string', 'max:255']

Uso: validación de payload para `AuthController::login`.

### app/Http/Requests/Auth/ChangePasswordRequest.php
- authorize(): bool -> true
- rules(): array
  - 'current_password' => ['required', 'string']
  - 'password' => ['required', 'string', 'min:8', 'confirmed']

Uso: validación de payload para `AuthController::changePassword`.

Notas
-----
- Ambos requests permiten la validación automática antes de entrar a la lógica del controlador.
- `ChangePasswordRequest` usa `confirmed` para requerir `password_confirmation` en el body.

