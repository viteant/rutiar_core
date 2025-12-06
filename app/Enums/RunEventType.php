<?php

namespace App\Enums;

enum RunEventType: string
{
    case BOARDING          = 'boarding';
    case DROP_OFF          = 'drop_off';
    case NO_SHOW           = 'no_show';
    case ADDED_ON_ROUTE    = 'added_on_route';
    case REMOVED_FROM_ROUTE = 'removed_from_route';
    case INCIDENT          = 'incident';

    public function isIncident(): bool
    {
        return $this === self::INCIDENT;
    }
}
