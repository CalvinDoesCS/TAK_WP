<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the multi-tenancy module
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Tenant Identification
    |--------------------------------------------------------------------------
    |
    | How tenants are identified in the application
    |
    */
    'identification' => [
        'method' => 'subdomain', // subdomain, domain, header, session
        'header_key' => 'X-Tenant-ID', // If using header method
    ],

    /*
    |--------------------------------------------------------------------------
    | Central Domain
    |--------------------------------------------------------------------------
    |
    | The main domain where the central application runs
    | This is used to extract subdomains
    |
    */
    'central_domain' => env('CENTRAL_DOMAIN', parse_url(config('app.url'), PHP_URL_HOST)),

    /*
    |--------------------------------------------------------------------------
    | Tenant Database
    |--------------------------------------------------------------------------
    |
    | Configuration for tenant databases
    |
    */
    'database' => [
        'prefix' => 'tenant_', // Prefix for tenant database names
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'auto_migrate' => true, // Automatically run migrations after provisioning
        'auto_seed' => true, // Automatically run seeders after provisioning
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Routes
    |--------------------------------------------------------------------------
    |
    | Routes that should not use tenant identification
    | These routes will always use the central database
    |
    */
    'excluded_routes' => [
        'multitenancycore.register',
        'multitenancycore.register.post',
        'multitenancycore.admin.*',
        'multitenancycore.tenant.*',
        'password.*',
        'sanctum.*',
        'telescope.*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Models
    |--------------------------------------------------------------------------
    |
    | Models that should use the tenant database connection
    | Add your models here to automatically use tenant connection
    |
    */
    'tenant_models' => [
        \App\Models\User::class,
        // Add other models that should use tenant database
    ],

    /*
    |--------------------------------------------------------------------------
    | Central Models
    |--------------------------------------------------------------------------
    |
    | Models that should always use the central database
    | These models will not be affected by tenant context
    |
    */
    'central_models' => [
        \Modules\MultiTenancyCore\App\Models\Tenant::class,
        \Modules\MultiTenancyCore\App\Models\Plan::class,
        \Modules\MultiTenancyCore\App\Models\Subscription::class,
        \Modules\MultiTenancyCore\App\Models\Payment::class,
        \Modules\MultiTenancyCore\App\Models\TenantDatabase::class,
        \Modules\MultiTenancyCore\App\Models\SaasSetting::class,
        \Modules\MultiTenancyCore\App\Models\SaasNotificationTemplate::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Grace Period Days (Fallback)
    |--------------------------------------------------------------------------
    |
    | Grace period after subscription expires before tenant suspension.
    | PRIMARY SOURCE: SaasSetting 'general_grace_period_days' (admin-configurable)
    | This config value is only used as a fallback if the database setting is not found.
    |
    */
    'grace_period_days' => env('SUBSCRIPTION_GRACE_PERIOD_DAYS', 3),
];
