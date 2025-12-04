Mail
====

Resumen
-------
Descripción de las Mailable definidas en `app/Mail`.

### app/Mail/CompanyAdminInvitationMail.php
- Propósito: enviar un correo al administrador de la compañía con la contraseña temporal y detalles de acceso.
- Constructor: public function __construct(public Company $company, public User $user, public string $temporaryPassword)
- Métodos:
  - public function build(): self
    - Define subject 'Acceso a Rutiar - Panel de Empresa'
    - Usa plantilla markdown `emails.company_admin_invitation` y pasa `company`, `user` y `temporaryPassword` al view.

Notas
-----
- La clase usa `Queueable` y `SerializesModels`.
- La plantilla markdown debe existir en `resources/views/emails/company_admin_invitation.blade.php` (o similar) para renderizar el mensaje.

