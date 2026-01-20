<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Excluded Modules for Tenants
    |--------------------------------------------------------------------------
    |
    | These modules are main application specific and should NOT be migrated
    | or available in tenant databases. They are only for the central/main
    | application.
    |
    */
    'excluded_modules' => [
        'MultiTenancyCore',      // Multi-tenancy management
        'SubscriptionManagement', // Subscription management for SaaS
        'PayPalGateway',         // Payment gateways managed centrally
        'StripeGateway',         // Payment gateways managed centrally
        'RazorpayGateway',       // Payment gateways managed centrally
        'LandingPage',           // Landing page for main application
        'Billing',               // Central billing management
    ],

    /*
    |--------------------------------------------------------------------------
    | Core System Tables to Exclude
    |--------------------------------------------------------------------------
    |
    | These are core Laravel/system tables that should not be migrated to
    | tenant databases as they are managed centrally.
    |
    */
    'excluded_core_migrations' => [
        // These patterns will be matched against migration filenames
        'telescope_entries',
        'failed_jobs',
        'jobs',
        'job_batches',
        'cache',
        'cache_locks',
        'sessions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Priority Modules
    |--------------------------------------------------------------------------
    |
    | These modules should be migrated first as other modules depend on them.
    | Order is critical - modules are migrated in the exact order listed below.
    |
    */
    'priority_modules' => [
        'SystemCore',        // Core system functionality
        'FieldManager',      // Creates clients table (required by ProductOrder, FieldTask, Calendar, SiteAttendance, PaymentCollection)
        'SiteAttendance',    // Creates sites table (required by GeofenceSystem, IpAddressAttendance, attendance core)
        'BreakSystem',       // Creates attendance_breaks table (required by attendance tracking)
        'Payroll',           // Creates payroll_records table (required by expenses)
        'GeofenceSystem',    // Creates geofence_groups (uses sites table)
        'AccountingCore',    // Accounting functionality
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Specific Seeders (Core App)
    |--------------------------------------------------------------------------
    |
    | Core application seeders that run for tenant databases.
    | These are called by LiveSeeder during tenant provisioning.
    |
    */
    'tenant_seeders' => [
        'Database\Seeders\ERPPermissionSeeder',
        'Database\Seeders\DepartmentSeeder',
        'Database\Seeders\ShiftSeeder',
        'Database\Seeders\LeaveSeeder',
        'Database\Seeders\HolidaySeeder',
        'Database\Seeders\WorkPatternSeeder',
        'Database\Seeders\DefaultEmployeeSeeder', // Creates default admin employee
    ],

    /*
    |--------------------------------------------------------------------------
    | Essential Module Seeders for Tenant Databases
    |--------------------------------------------------------------------------
    |
    | These seeders run during tenant provisioning. They create essential
    | permissions, settings, and configurations ONLY.
    |
    | NEVER include Demo/Sample seeders here - those only run via
    | the opencorebs:demo command.
    |
    */
    'essential_module_seeders' => [
        // === PERMISSION SEEDERS (ALL modules) ===
        'Modules\\AccountingCore\\Database\\Seeders\\AccountingCorePermissionSeeder',
        'Modules\\Assets\\Database\\Seeders\\AssetsPermissionsSeeder',
        'Modules\\DisciplinaryActions\\Database\\Seeders\\PermissionsSeeder',
        'Modules\\DocumentManagement\\Database\\Seeders\\DocumentManagementPermissionsSeeder',
        'Modules\\FormBuilder\\Database\\Seeders\\FormBuilderPermissionSeeder',
        'Modules\\LoanManagement\\Database\\Seeders\\LoanManagementPermissionSeeder',

        // === SETTINGS SEEDERS ===
        'Modules\\SystemCore\\Database\\Seeders\\SystemCoreSettingsSeeder',
        'Modules\\AccountingCore\\Database\\Seeders\\AccountingCoreSettingsSeeder',
        'Modules\\FieldManager\\Database\\Seeders\\FieldManagerSettingsSeeder',
        'Modules\\LoanManagement\\Database\\Seeders\\LoanManagementSettingsSeeder',
        'Modules\\PaymentCollection\\Database\\Seeders\\PaymentCollectionSettingsSeeder',
        'Modules\\GoogleReCAPTCHA\\Database\\Seeders\\GoogleReCAPTCHASettingsSeeder',

        // === ESSENTIAL TYPE SEEDERS ===
        'Modules\\FormBuilder\\Database\\Seeders\\FormFieldTypesSeeder', // Required for forms to work
    ],
];
