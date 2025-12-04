<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompanyAdminInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Company $company,
        public User $user,
        public string $temporaryPassword,
    ) {}

    public function build(): self
    {
        return $this->subject('Acceso a Rutiar - Panel de Empresa')
            ->markdown('emails.company_admin_invitation', [
                'company' => $this->company,
                'user' => $this->user,
                'temporaryPassword' => $this->temporaryPassword,
            ]);
    }
}
