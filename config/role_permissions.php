<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default role permissions per company
    |--------------------------------------------------------------------------
    |
    | This file defines the baseline permissions each role gets when a company
    | is created. These defaults are per-tenant; they are copied into the
    | role_permissions table for each company.
    |
    | Keys must match the UserRole enum values:
    | SUPERADMIN, COMPANY_ADMIN, COMPANY_USER, PARTNER_ADMIN, DRIVER
    |
    */

    'defaults' => [

        'SUPERADMIN' => [
            'view_company_settings',
            'update_company_settings',

            'view_partners',
            'create_partner',
            'update_partner',
            'delete_partner',
            'manage_partner_drivers',

            'view_drivers',
            'create_driver',
            'update_driver',
            'deactivate_driver',

            'view_vehicles',
            'create_vehicle',
            'update_vehicle',
            'deactivate_vehicle',

            'view_corporates',
            'create_corporate',
            'update_corporate',
            'deactivate_corporate',

            'view_passengers',
            'create_passenger',
            'update_passenger',
            'deactivate_passenger',

            'view_routes',
            'create_route',
            'update_route',
            'deactivate_route',

            'view_route_definitions',
            'create_route_definition',
            'update_route_definition',
            'deactivate_route_definition',

            'view_runs',
            'approve_run',
            'cancel_run',
            'force_close_run',

            'view_manifests',
            'export_manifests',

            'view_billing',
            'view_reports',

            'manage_company_role_permissions',
            'manage_company_user_permissions',
        ],

        'COMPANY_ADMIN' => [
            'view_company_settings',
            'update_company_settings',

            'view_partners',
            'create_partner',
            'update_partner',
            'delete_partner',
            'manage_partner_drivers',

            'view_drivers',
            'create_driver',
            'update_driver',
            'deactivate_driver',

            'view_vehicles',
            'create_vehicle',
            'update_vehicle',
            'deactivate_vehicle',

            'view_corporates',
            'create_corporate',
            'update_corporate',
            'deactivate_corporate',

            'view_passengers',
            'create_passenger',
            'update_passenger',
            'deactivate_passenger',

            'view_routes',
            'create_route',
            'update_route',
            'deactivate_route',

            'view_route_definitions',
            'create_route_definition',
            'update_route_definition',
            'deactivate_route_definition',

            'view_runs',
            'approve_run',
            'cancel_run',

            'view_manifests',
            'export_manifests',

            'view_billing',
            'view_reports',

            'manage_company_role_permissions',
            'manage_company_user_permissions',
        ],

        'COMPANY_USER' => [
            'view_partners',
            'view_drivers',
            'view_corporates',
            'view_passengers',

            'view_routes',
            'create_route',
            'update_route',

            'view_route_definitions',
            'create_route_definition',
            'update_route_definition',

            'view_runs',

            'view_manifests',

            'view_billing',
            'view_reports',
        ],

        'PARTNER_ADMIN' => [
            'view_partners',
            'view_drivers',
            'create_driver',
            'update_driver',

            'view_vehicles',
            'create_vehicle',
            'update_vehicle',
            'deactivate_vehicle',

            'view_routes',

            'view_route_definitions',

            'view_runs',

            'view_manifests',
        ],

        'DRIVER' => [
            // No web panel permissions. Mobile endpoints will use policies.
        ],
    ],
];
