# Mail/CompanyAdminInvitationMail.php

*Mailable para enviar la invitación y credenciales iniciales a un nuevo COMPANY_ADMIN.*

Funciones:
- `__construct(Company $company, User $user, string $temporaryPassword): void` - Recibe la compañía a la que pertenece el admin, el usuario creado y la contraseña temporal generada por el comando `rutiar:create-company-admin`. Expone estas propiedades como públicas para su uso en la vista.
- `build(): self` - Construye el correo estableciendo el subject `'Acceso a Rutiar - Panel de Empresa'` y usando la vista markdown `emails.company_admin_invitation`, a la que pasa las variables `company`, `user` y `temporaryPassword` para renderizar el contenido del email.

Qué se puede enviar:
- Correo de invitación a un nuevo administrador de compañía, incluyendo:
  - Datos básicos de la compañía (nombre, código, etc.).
  - Usuario al que se le otorga acceso (nombre, email).
  - Contraseña temporal generada para el primer acceso, con la expectativa de que sea cambiada en el primer login.
