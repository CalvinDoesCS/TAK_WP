<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Core Modules
    |--------------------------------------------------------------------------
    |
    | Core modules are fundamental modules that are always accessible regardless
    | of subscription plan. These modules end with "Core" suffix and provide
    | essential business functionality.
    |
    | Core modules are automatically detected by the "Core" suffix but are
    | explicitly listed here for clarity and validation.
    |
    */

    'core_modules' => [
        'AccountingCore',
        'MultiTenancyCore',
    ],

    /*
    |--------------------------------------------------------------------------
    | System Modules
    |--------------------------------------------------------------------------
    |
    | System modules are administrative modules that should never be included
    | in subscription plans. These are managed by administrators only.
    |
    */

    'system_modules' => [
        'PayPalGateway',
        'StripeGateway',
        'RazorpayGateway',
        'GoogleReCAPTCHA',
        'MultiTenancyCore', // Admin/system use only
    ],

    /*
    |--------------------------------------------------------------------------
    | Core Module Detection Pattern
    |--------------------------------------------------------------------------
    |
    | Pattern used to automatically detect core modules by their name.
    | Modules matching this pattern are considered core modules.
    |
    */

    'core_suffix' => 'Core',

];

/*
|--------------------------------------------------------------------------
| Note: Helper Functions
|--------------------------------------------------------------------------
|
| Helper functions for working with core modules are available in:
| app/Helpers/CoreModuleHelpers.php
|
| Available functions:
| - getCoreModules()
| - getSystemModules()
| - isCoreModule($moduleName)
| - isSystemModule($moduleName)
| - getAvailableAddonModules()
| - getAllEnabledModules()
| - getModuleCategory($moduleName)
|
*/
