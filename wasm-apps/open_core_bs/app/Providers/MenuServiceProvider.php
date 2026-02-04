<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load all menu files once
        $verticalMenuJson = file_get_contents(base_path('resources/menu/verticalMenu.json'));
        $verticalMenuData = json_decode($verticalMenuJson);

        $hrMenuJson = file_get_contents(base_path('resources/menu/hrMenu.json'));
        $hrMenuData = json_decode($hrMenuJson);

        $employeeMenuJson = file_get_contents(base_path('resources/menu/employeeMenu.json'));
        $employeeMenuData = json_decode($employeeMenuJson);

        $horizontalMenuJson = file_get_contents(base_path('resources/menu/horizontalMenu.json'));
        $horizontalMenuData = json_decode($horizontalMenuJson);

        $quickCreateMenuJson = file_get_contents(base_path('resources/menu/quickCreateMenu.json'));
        $quickCreateMenuData = json_decode($quickCreateMenuJson);

        // Use a view composer to determine menu based on authenticated user
        // This runs when the view is rendered, after authentication
        $this->app->make('view')->composer('*', function ($view) use ($verticalMenuData, $hrMenuData, $employeeMenuData, $horizontalMenuData, $quickCreateMenuData) {
            // Load tenant menu only if MultiTenancyCore module is enabled
            $tenantMenuData = null;
            $addonService = app(\App\Services\AddonService\IAddonService::class);
            if ($addonService->isAddonEnabled('MultiTenancyCore')) {
                $tenantMenuPath = module_path('MultiTenancyCore', 'resources/menu/tenantMenu.json');
                if (file_exists($tenantMenuPath)) {
                    $tenantMenuJson = file_get_contents($tenantMenuPath);
                    $tenantMenuData = json_decode($tenantMenuJson);
                }
            }

            // Determine which menu to use for the authenticated user
            $activeVerticalMenuData = $verticalMenuData; // Default to admin menu
            $activeHorizontalMenuData = $horizontalMenuData; // Default to horizontal menu

            if (auth()->check()) {
                $user = auth()->user();

                // Check user role and assign appropriate menu
                if ($user->hasRole('tenant') && $tenantMenuData !== null) {
                    // Tenant users get the tenant-specific menu (only if MultiTenancyCore is enabled)
                    $activeVerticalMenuData = $tenantMenuData;
                    $activeHorizontalMenuData = $tenantMenuData;
                } elseif ($user->hasRole(['super_admin', 'admin'])) {
                    // Super Admin and Admin get full menu
                    $activeVerticalMenuData = $verticalMenuData;
                    $activeHorizontalMenuData = $horizontalMenuData;
                } elseif ($user->hasRole(['hr', 'hr_manager', 'hr_executive'])) {
                    // HR users get the HR-specific menu
                    $activeVerticalMenuData = $hrMenuData;
                    $activeHorizontalMenuData = $hrMenuData;
                } elseif ($user->hasRole(['employee', 'field_employee', 'office_employee', 'manager', 'team_leader'])) {
                    // Employees get the employee menu
                    $activeVerticalMenuData = $employeeMenuData;
                    $activeHorizontalMenuData = $employeeMenuData;
                } else {
                    // Default to admin menu for any other roles
                    $activeVerticalMenuData = $verticalMenuData;
                    $activeHorizontalMenuData = $horizontalMenuData;
                }
            }

            // Share all menuData to all the views
            // Index 0: vertical menu (admin), Index 1: horizontal menu (role-based), Index 2: quick create menu, Index 3: active vertical menu (role-based)
            $view->with('menuData', [$verticalMenuData, $activeHorizontalMenuData, $quickCreateMenuData, $activeVerticalMenuData]);
        });
    }
}
