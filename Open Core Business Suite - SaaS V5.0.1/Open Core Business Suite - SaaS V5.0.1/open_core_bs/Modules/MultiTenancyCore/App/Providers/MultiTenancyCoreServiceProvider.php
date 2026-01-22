<?php

namespace Modules\MultiTenancyCore\App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MultiTenancyCoreServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'MultiTenancyCore';

    protected string $nameLower = 'multitenancycore';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'Database/migrations'));
        $this->registerServices();
        $this->loadHelpers();

        // Register middleware
        $router = $this->app['router'];
        $router->aliasMiddleware('tenant.status', \Modules\MultiTenancyCore\App\Http\Middleware\CheckTenantStatus::class);
        $router->aliasMiddleware('tenant.identify', \Modules\MultiTenancyCore\App\Http\Middleware\IdentifyTenant::class);
        $router->aliasMiddleware('ensure.tenant', \Modules\MultiTenancyCore\App\Http\Middleware\EnsureTenantRole::class);
        $router->aliasMiddleware('tenant.portal.layout', \Modules\MultiTenancyCore\App\Http\Middleware\SetTenantPortalLayout::class);
        $router->aliasMiddleware('api.tenant.context', \Modules\MultiTenancyCore\App\Http\Middleware\ApiTenantContext::class);

        // Apply middleware to web group
        $router->pushMiddlewareToGroup('web', \Modules\MultiTenancyCore\App\Http\Middleware\CheckTenantStatus::class);

        // Add tenant identification to web middleware group with high priority
        $router->prependMiddlewareToGroup('web', \Modules\MultiTenancyCore\App\Http\Middleware\IdentifyTenant::class);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // Commands will be registered once created
        $this->commands([
            \Modules\MultiTenancyCore\App\Console\GenerateInvoices::class,
            \Modules\MultiTenancyCore\App\Console\CreateTenantCommand::class,
            \Modules\MultiTenancyCore\App\Console\TenantDemoCommand::class,
            \Modules\MultiTenancyCore\App\Console\ProvisionTenantDatabaseCommand::class,
            \Modules\MultiTenancyCore\App\Console\TenantStorageLinkCommand::class,
            \Modules\MultiTenancyCore\App\Console\TenantMigrateCommand::class,
            \Modules\MultiTenancyCore\App\Console\TenantMigrateRollbackCommand::class,
            \Modules\MultiTenancyCore\App\Console\TenantMigrateStatusCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $relativeConfigPath = config('modules.paths.generator.config.path');
        $configPath = module_path($this->name, $relativeConfigPath);

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $configKey = $this->nameLower.'.'.str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $relativePath);
                    $key = ($relativePath === 'config.php') ? $this->nameLower : $configKey;

                    $this->publishes([$file->getPathname() => config_path($relativePath)], 'config');
                    $this->mergeConfigFrom($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        $componentNamespace = $this->module_namespace($this->name, $this->app_path(config('modules.paths.generator.component-class.path')));
        Blade::componentNamespace($componentNamespace, $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }

    /**
     * Register module services.
     */
    protected function registerServices(): void
    {
        // Register TenantManager as singleton
        $this->app->singleton('tenant.manager', function () {
            return new \Modules\MultiTenancyCore\App\Services\TenantManager;
        });

        // Register TenantDatabaseService as singleton
        $this->app->singleton(\Modules\MultiTenancyCore\App\Services\TenantDatabaseService::class);
    }

    /**
     * Load helper files
     */
    protected function loadHelpers(): void
    {
        $helperPath = module_path($this->name, 'App/Helpers/tenant.php');
        if (file_exists($helperPath)) {
            require_once $helperPath;
        }
    }
}
