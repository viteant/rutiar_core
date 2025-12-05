<?php

namespace App\Enums;

enum RunStatus: string
{
    case PLANNED      = 'PLANNED';
    case APPROVED     = 'APPROVED';
    case IN_PROGRESS  = 'IN_PROGRESS';
    case COMPLETED    = 'COMPLETED';
    case CANCELED     = 'CANCELED';
    case FORCE_CLOSED = 'FORCE_CLOSED';

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELED,
            self::FORCE_CLOSED,
        ], true);
    }

    public function canBeEdited(): bool
    {
        return ! $this->isTerminal();
    }
}
