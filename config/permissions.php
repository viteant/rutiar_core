<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global permission registry
    |--------------------------------------------------------------------------
    |
    | This is the single source of truth for all permission names used in Rutiar.
    | Every new module must register its permissions here.
    |
    | DO NOT remove permissions used in production without a migration strategy.
    |
    */

    'permissions' => [

        // Company settings & meta
        [
            'name' => 'view_company_settings',
            'description' => 'View company configuration, cutoff times and global rules.',
        ],
        [
            'name' => 'update_company_settings',
            'description' => 'Update company configuration, cutoff times and global rules.',
        ],

        // Partners (socios)
        [
            'name' => 'view_partners',
            'description' => 'View partners list and partner details.',
        ],
        [
            'name' => 'create_partner',
            'description' => 'Create new partners.',
        ],
        [
            'name' => 'update_partner',
            'description' => 'Edit partner information.',
        ],
        [
            'name' => 'delete_partner',
            'description' => 'Delete or deactivate partners.',
        ],
        [
            'name' => 'manage_partner_drivers',
            'description' => 'Assign or remove drivers from partners.',
        ],

        // Drivers
        [
            'name' => 'view_drivers',
            'description' => 'View drivers list and details.',
        ],
        [
            'name' => 'create_driver',
            'description' => 'Create new drivers.',
        ],
        [
            'name' => 'update_driver',
            'description' => 'Edit driver information.',
        ],
        [
            'name' => 'deactivate_driver',
            'description' => 'Deactivate drivers.',
        ],

        // Route definitions (plantillas)
        [
            'name' => 'view_route_definitions',
            'description' => 'View route templates and their configuration.',
        ],
        [
            'name' => 'create_route_definition',
            'description' => 'Create new route templates.',
        ],
        [
            'name' => 'update_route_definition',
            'description' => 'Edit existing route templates.',
        ],
        [
            'name' => 'delete_route_definition',
            'description' => 'Delete or deactivate route templates.',
        ],

        // Runs (ejecuciones diarias)
        [
            'name' => 'view_runs',
            'description' => 'View runs for the company routes.',
        ],
        [
            'name' => 'approve_run',
            'description' => 'Approve planned runs.',
        ],
        [
            'name' => 'cancel_run',
            'description' => 'Cancel runs before execution.',
        ],
        [
            'name' => 'force_close_run',
            'description' => 'Force close runs in exceptional cases.',
        ],

        // Manifiestos / pasajeros
        [
            'name' => 'view_manifests',
            'description' => 'View manifests with passengers and stops.',
        ],
        [
            'name' => 'export_manifests',
            'description' => 'Export manifests for control or external tools.',
        ],

        // Billing / reporting
        [
            'name' => 'view_billing',
            'description' => 'View billing and pre-invoices generated from runs.',
        ],
        [
            'name' => 'view_reports',
            'description' => 'Access operational and KPI reports.',
        ],

        // Permissions management (a nivel de compañía)
        [
            'name' => 'manage_company_role_permissions',
            'description' => 'Manage role permissions for this company.',
        ],
        [
            'name' => 'manage_company_user_permissions',
            'description' => 'Manage user-specific permissions for this company.',
        ],

        // Vehicles
        [
            'name' => 'view_vehicles',
            'description' => 'View vehicles list and details.',
        ],
        [
            'name' => 'create_vehicle',
            'description' => 'Create new vehicles.',
        ],
        [
            'name' => 'update_vehicle',
            'description' => 'Edit vehicle information.',
        ],
        [
            'name' => 'deactivate_vehicle',
            'description' => 'Deactivate vehicles (soft delete).',
        ],
    ],
];
