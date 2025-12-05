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

        // Corporates (clientes corporativos)
        [
            'name' => 'view_corporates',
            'description' => 'View corporates list and details.',
        ],
        [
            'name' => 'create_corporate',
            'description' => 'Create new corporates.',
        ],
        [
            'name' => 'update_corporate',
            'description' => 'Edit corporate information.',
        ],
        [
            'name' => 'deactivate_corporate',
            'description' => 'Deactivate corporates (soft delete).',
        ],

        // Passengers
        [
            'name' => 'view_passengers',
            'description' => 'View passengers list and details.',
        ],
        [
            'name' => 'create_passenger',
            'description' => 'Create new passengers.',
        ],
        [
            'name' => 'update_passenger',
            'description' => 'Edit passenger information.',
        ],
        [
            'name' => 'deactivate_passenger',
            'description' => 'Deactivate passengers (soft delete).',
        ],

        // Routes (catálogo base)
        [
            'name' => 'view_routes',
            'description' => 'View routes list and details.',
        ],
        [
            'name' => 'create_route',
            'description' => 'Create new routes.',
        ],
        [
            'name' => 'update_route',
            'description' => 'Edit routes.',
        ],
        [
            'name' => 'deactivate_route',
            'description' => 'Deactivate routes (soft delete).',
        ],

        // Route definitions (plantillas operativas)
        [
            'name' => 'view_route_definitions',
            'description' => 'View route definitions.',
        ],
        [
            'name' => 'create_route_definition',
            'description' => 'Create route definitions.',
        ],
        [
            'name' => 'update_route_definition',
            'description' => 'Update route definitions.',
        ],
        [
            'name' => 'deactivate_route_definition',
            'description' => 'Deactivate route definitions (soft delete).',
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

        // Manifests / passengers in runs
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
    ],
];
