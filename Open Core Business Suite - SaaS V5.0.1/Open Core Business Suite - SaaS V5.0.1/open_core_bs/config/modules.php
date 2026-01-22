<?php

use Nwidart\Modules\Activators\FileActivator;
use Nwidart\Modules\Providers\ConsoleServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Module Namespace
    |--------------------------------------------------------------------------
    |
    | Default module namespace.
    |
    */
    'namespace' => 'Modules',

    /*
    |--------------------------------------------------------------------------
    | Module Stubs
    |--------------------------------------------------------------------------
    |
    | Default module stubs.
    |
    | IMPORTANT: Custom stubs are enabled and located at stubs/nwidart-stubs/
    | These stubs use uppercase 'App/' paths for PSR-4 compliance on Linux.
    |
    */
    'stubs' => [
        'enabled' => true,
        'path' => base_path('stubs/nwidart-stubs'),
        'files' => [
            'routes/web' => 'routes/web.php',
            'routes/api' => 'routes/api.php',
            'views/index' => 'resources/views/index.blade.php',
            'views/master' => 'resources/views/layouts/master.blade.php',
            'scaffold/config' => 'config/config.php',
            'composer' => 'composer.json',
            'assets/js/app' => 'resources/assets/js/app.js',
            'assets/sass/app' => 'resources/assets/sass/app.scss',
            'vite' => 'vite.config.js',
            'package' => 'package.json',
        ],
        'replacements' => [
            'routes/web' => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE', 'CONTROLLER_NAMESPACE'],
            'routes/api' => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE', 'CONTROLLER_NAMESPACE'],
            'vite' => ['LOWER_NAME', 'STUDLY_NAME'],
            'json' => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE', 'PROVIDER_NAMESPACE'],
            'views/index' => ['LOWER_NAME'],
            'views/master' => ['LOWER_NAME', 'STUDLY_NAME'],
            'scaffold/config' => ['STUDLY_NAME'],
            'composer' => [
                'LOWER_NAME',
                'STUDLY_NAME',
                'VENDOR',
                'AUTHOR_NAME',
                'AUTHOR_EMAIL',
                'MODULE_NAMESPACE',
                'PROVIDER_NAMESPACE',
                'APP_FOLDER_NAME',
            ],
        ],
        'gitkeep' => true,
    ],
    'paths' => [
        /*
        |--------------------------------------------------------------------------
        | Modules path
        |--------------------------------------------------------------------------
        |
        | This path is used to save the generated module.
        | This path will also be added automatically to the list of scanned folders.
        |
        */
        'modules' => base_path('Modules'),

        /*
        |--------------------------------------------------------------------------
        | Modules assets path
        |--------------------------------------------------------------------------
        |
        | Here you may update the modules' assets path.
        |
        */
        'assets' => public_path('modules'),

        /*
        |--------------------------------------------------------------------------
        | The migrations' path
        |--------------------------------------------------------------------------
        |
        | Where you run the 'module:publish-migration' command, where do you publish the
        | the migration files?
        |
        */
        'migration' => base_path('database/migrations'),

        /*
        |--------------------------------------------------------------------------
        | The app path
        |--------------------------------------------------------------------------
        |
        | IMPORTANT: PSR-4 Compliance - Must use uppercase 'App/'
        |
        | DO NOT change to lowercase 'app/' - this will break autoloading on
        | case-sensitive filesystems (Linux). All module code lives in App/
        | and namespaces must match: Modules\ModuleName\App\...
        |
        */
        'app_folder' => 'App/',

        /*
        |--------------------------------------------------------------------------
        | Generator path
        |--------------------------------------------------------------------------
        | Customise the paths where the folders will be generated.
        | Setting the generate key to false will not generate that folder
        */
        'generator' => [
            /*
            |--------------------------------------------------------------------------
            | IMPORTANT: Explicit namespaces required for PSR-4 compliance
            |--------------------------------------------------------------------------
            |
            | Each generator must have an explicit 'namespace' key that includes 'App\'
            | prefix. Without this, the generator strips 'App/' and creates wrong
            | namespaces like 'Modules\X\Providers' instead of 'Modules\X\App\Providers'.
            |
            */

            // App/
            'actions' => ['path' => 'App/Actions', 'namespace' => 'App\\Actions', 'generate' => false],
            'casts' => ['path' => 'App/Casts', 'namespace' => 'App\\Casts', 'generate' => false],
            'channels' => ['path' => 'App/Broadcasting', 'namespace' => 'App\\Broadcasting', 'generate' => false],
            'class' => ['path' => 'App/Classes', 'namespace' => 'App\\Classes', 'generate' => false],
            'command' => ['path' => 'App/Console', 'namespace' => 'App\\Console', 'generate' => false],
            'component-class' => ['path' => 'App/View/Components', 'namespace' => 'App\\View\\Components', 'generate' => false],
            'emails' => ['path' => 'App/Emails', 'namespace' => 'App\\Emails', 'generate' => false],
            'event' => ['path' => 'App/Events', 'namespace' => 'App\\Events', 'generate' => false],
            'enums' => ['path' => 'App/Enums', 'namespace' => 'App\\Enums', 'generate' => false],
            'exceptions' => ['path' => 'App/Exceptions', 'namespace' => 'App\\Exceptions', 'generate' => false],
            'jobs' => ['path' => 'App/Jobs', 'namespace' => 'App\\Jobs', 'generate' => false],
            'helpers' => ['path' => 'App/Helpers', 'namespace' => 'App\\Helpers', 'generate' => false],
            'interfaces' => ['path' => 'App/Interfaces', 'namespace' => 'App\\Interfaces', 'generate' => false],
            'listener' => ['path' => 'App/Listeners', 'namespace' => 'App\\Listeners', 'generate' => false],
            'model' => ['path' => 'App/Models', 'namespace' => 'App\\Models', 'generate' => false],
            'notifications' => ['path' => 'App/Notifications', 'namespace' => 'App\\Notifications', 'generate' => false],
            'observer' => ['path' => 'App/Observers', 'namespace' => 'App\\Observers', 'generate' => false],
            'policies' => ['path' => 'App/Policies', 'namespace' => 'App\\Policies', 'generate' => false],
            'provider' => ['path' => 'App/Providers', 'namespace' => 'App\\Providers', 'generate' => true],
            'repository' => ['path' => 'App/Repositories', 'namespace' => 'App\\Repositories', 'generate' => false],
            'resource' => ['path' => 'App/Transformers', 'namespace' => 'App\\Transformers', 'generate' => false],
            'route-provider' => ['path' => 'App/Providers', 'namespace' => 'App\\Providers', 'generate' => true],
            'rules' => ['path' => 'App/Rules', 'namespace' => 'App\\Rules', 'generate' => false],
            'services' => ['path' => 'App/Services', 'namespace' => 'App\\Services', 'generate' => false],
            'scopes' => ['path' => 'App/Models/Scopes', 'namespace' => 'App\\Models\\Scopes', 'generate' => false],
            'traits' => ['path' => 'App/Traits', 'namespace' => 'App\\Traits', 'generate' => false],

            // App/Http/
            'controller' => ['path' => 'App/Http/Controllers', 'namespace' => 'App\\Http\\Controllers', 'generate' => true],
            'filter' => ['path' => 'App/Http/Middleware', 'namespace' => 'App\\Http\\Middleware', 'generate' => false],
            'request' => ['path' => 'App/Http/Requests', 'namespace' => 'App\\Http\\Requests', 'generate' => false],

            // config/
            'config' => ['path' => 'config', 'generate' => true],

            // Database/
            'factory' => ['path' => 'Database/Factories', 'namespace' => 'Database\\Factories', 'generate' => true],
            'migration' => ['path' => 'Database/Migrations', 'generate' => true],
            'seeder' => ['path' => 'Database/Seeders', 'namespace' => 'Database\\Seeders', 'generate' => true],

            // lang/
            'lang' => ['path' => 'lang', 'generate' => false],

            // resource/
            'assets' => ['path' => 'resources/assets', 'generate' => true],
            'component-view' => ['path' => 'resources/views/components', 'generate' => false],
            'views' => ['path' => 'resources/views', 'generate' => true],

            // routes/
            'routes' => ['path' => 'routes', 'generate' => true],

            // tests/
            'test-feature' => ['path' => 'tests/Feature', 'namespace' => 'Tests\\Feature', 'generate' => true],
            'test-unit' => ['path' => 'tests/Unit', 'namespace' => 'Tests\\Unit', 'generate' => true],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Package commands
    |--------------------------------------------------------------------------
    |
    | Here you can define which commands will be visible and used in your
    | application. You can add your own commands to merge section.
    |
    */
    'commands' => ConsoleServiceProvider::defaultCommands()
        ->merge([
            // New commands go here
        ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Scan Path
    |--------------------------------------------------------------------------
    |
    | Here you define which folder will be scanned. By default will scan vendor
    | directory. This is useful if you host the package in packagist website.
    |
    */
    'scan' => [
        'enabled' => false,
        'paths' => [
            base_path('vendor/*/*'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Composer File Template
    |--------------------------------------------------------------------------
    |
    | Here is the config for the composer.json file, generated by this package
    |
    */
    'composer' => [
        'vendor' => env('MODULE_VENDOR', 'nwidart'),
        'author' => [
            'name' => env('MODULE_AUTHOR_NAME', 'Nicolas Widart'),
            'email' => env('MODULE_AUTHOR_EMAIL', 'n.widart@gmail.com'),
        ],
        'composer-output' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Here is the config for setting up the caching feature.
    |
    */
    'cache' => [
        'enabled' => env('MODULES_CACHE_ENABLED', false),
        'driver' => env('MODULES_CACHE_DRIVER', 'file'),
        'key' => env('MODULES_CACHE_KEY', 'laravel-modules'),
        'lifetime' => env('MODULES_CACHE_LIFETIME', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Choose what laravel-modules will register as custom namespaces.
    | Setting one to false will require you to register that part
    | in your own Service Provider class.
    |--------------------------------------------------------------------------
    */
    'register' => [
        'translations' => true,
        /**
         * load files on boot or register method
         */
        'files' => 'register',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activators
    |--------------------------------------------------------------------------
    |
    | You can define new types of activators here, file, database, etc. The only
    | required parameter is 'class'.
    | The file activator will store the activation status in storage/installed_modules
    */
    'activators' => [
        'file' => [
            'class' => FileActivator::class,
            'statuses-file' => base_path('modules_statuses.json'),
            'cache-key' => 'activator.installed',
            'cache-lifetime' => 604800,
        ],
    ],

    'activator' => 'file',
];
