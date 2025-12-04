<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPERADMIN = 'SUPERADMIN';
    case COMPANY_ADMIN = 'COMPANY_ADMIN';
    case COMPANY_USER = 'COMPANY_USER';
    case PARTNER_ADMIN = 'PARTNER_ADMIN';
    case DRIVER = 'DRIVER';

    public function isSuperAdmin(): bool
    {
        return $this === self::SUPERADMIN;
    }

    public function isCompanyRole(): bool
    {
        return in_array($this, [
            self::COMPANY_ADMIN,
            self::COMPANY_USER,
        ], true);
    }

    public function isPartnerRole(): bool
    {
        return $this === self::PARTNER_ADMIN;
    }

    public function isDriver(): bool
    {
        return $this === self::DRIVER;
    }
}
