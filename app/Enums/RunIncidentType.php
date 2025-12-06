<?php

namespace App\Enums;

enum RunIncidentType: string
{
    case SECURITY          = 'security';
    case HEALTH            = 'health';
    case VEHICLE_BREAKDOWN = 'vehicle_breakdown';
    case TRAFFIC           = 'traffic';
    case OTHER             = 'other';
}
