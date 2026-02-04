<?php

return [
    'name' => 'MultiTenancyCore',

    // Database root credentials for VPS auto-provisioning
    // These are infrastructure-level credentials that should NOT be in admin-configurable settings
    'tenant_db_root_user' => env('TENANT_DB_ROOT_USER', 'root'),
    'tenant_db_root_password' => env('TENANT_DB_ROOT_PASSWORD', ''),

    // Central domain configuration
    'central_domains' => [
        env('APP_DOMAIN', 'localhost'),
    ],

    // Modules to exclude from tenant migrations
    'excluded_modules' => [
        'MultiTenancyCore',
    ],

    // Development/Debug notification settings
    'notify_on_provisioning_request' => true,
    'notification_email' => env('ADMIN_NOTIFICATION_EMAIL'),

    // Fallback values (primary source: SaasSetting database table)
    // These are only used if the database setting is not found
    'default_admin_password' => env('TENANT_DEFAULT_PASSWORD', 'password123'),
];
