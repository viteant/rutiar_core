<?php

namespace App\Enums;

enum RunEventSource: string
{
    case DRIVER_APP = 'driver_app';
    case BACKOFFICE = 'backoffice';
    case SYSTEM     = 'system';
}
