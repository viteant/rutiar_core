# config/permissions.php

*Registro global de permisos de la aplicación (fuente de verdad para la tabla `permissions`).*

Funciones (permisos definidos):
- `view_company_settings` - View company configuration, cutoff times and global rules.
- `update_company_settings` - Update company configuration, cutoff times and global rules.

- `view_partners` - View partners list and partner details.
- `create_partner` - Create new partners.
- `update_partner` - Edit partner information.
- `delete_partner` - Delete or deactivate partners.
- `manage_partner_drivers` - Assign or remove drivers from partners.

- `view_drivers` - View drivers list and details.
- `create_driver` - Create new drivers.
- `update_driver` - Edit driver information.
- `deactivate_driver` - Deactivate drivers.

- `view_route_definitions` - View route templates and their configuration.
- `create_route_definition` - Create new route templates.
- `update_route_definition` - Edit existing route templates.
- `delete_route_definition` - Delete or deactivate route templates.

- `view_runs` - View runs for the company routes.
- `approve_run` - Approve planned runs.
- `cancel_run` - Cancel runs before execution.
- `force_close_run` - Force close runs in exceptional cases.

- `view_manifests` - View manifests with passengers and stops.
- `export_manifests` - Export manifests for control or external tools.

- `view_billing` - View billing and pre-invoices generated from runs.
- `view_reports` - Access operational and KPI reports.

- `manage_company_role_permissions` - Manage role permissions for this company.
- `manage_company_user_permissions` - Manage user-specific permissions for this company.

- `view_vehicles` - View vehicles list and details.
- `create_vehicle` - Create new vehicles.
- `update_vehicle` - Edit vehicle information.
- `deactivate_vehicle` - Deactivate vehicles (soft delete).

- `view_corporates` - View corporates list and details.
- `create_corporate` - Create new corporates.
- `update_corporate` - Edit corporate information.
- `deactivate_corporate` - Deactivate corporates (soft delete).

- `view_passengers` - View passengers list and details.
- `create_passenger` - Create new passengers.
- `update_passenger` - Edit passenger information.
- `deactivate_passenger` - Deactivate passengers (soft delete).
