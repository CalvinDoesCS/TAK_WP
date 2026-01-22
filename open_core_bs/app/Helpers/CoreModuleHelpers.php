<?php

/**
 * Core Module Helper Functions
 *
 * These functions help identify and manage core modules vs addon modules
 * in the subscription plan system.
 */
if (! function_exists('getCoreModules')) {
    /**
     * Get list of core modules (always accessible in any plan)
     */
    function getCoreModules(): array
    {
        return config('core_modules.core_modules', []);
    }
}

if (! function_exists('getSystemModules')) {
    /**
     * Get list of system modules (never in plans, admin-only)
     */
    function getSystemModules(): array
    {
        return config('core_modules.system_modules', []);
    }
}

if (! function_exists('isCoreModule')) {
    /**
     * Check if a module is a core module (by name suffix or explicit list)
     */
    function isCoreModule(string $moduleName): bool
    {
        $coreModules = getCoreModules();
        $coreSuffix = config('core_modules.core_suffix', 'Core');

        // Check explicit list
        if (in_array($moduleName, $coreModules)) {
            return true;
        }

        // Check by suffix pattern
        return str_ends_with($moduleName, $coreSuffix);
    }
}

if (! function_exists('isSystemModule')) {
    /**
     * Check if a module is a system module (admin-only, never in plans)
     */
    function isSystemModule(string $moduleName): bool
    {
        return in_array($moduleName, getSystemModules());
    }
}

if (! function_exists('getAvailableAddonModules')) {
    /**
     * Get all available addon modules (enabled modules minus core and system modules)
     */
    function getAvailableAddonModules(): array
    {
        $modulesStatusPath = base_path('modules_statuses.json');

        if (! file_exists($modulesStatusPath)) {
            return [];
        }

        $modulesStatus = json_decode(file_get_contents($modulesStatusPath), true);

        if (! is_array($modulesStatus)) {
            return [];
        }

        $coreModules = getCoreModules();
        $systemModules = getSystemModules();

        // Get only enabled modules that are not core or system modules
        $addonModules = [];
        foreach ($modulesStatus as $moduleName => $isEnabled) {
            if (
                $isEnabled &&
                ! in_array($moduleName, $coreModules) &&
                ! in_array($moduleName, $systemModules)
            ) {
                $addonModules[] = $moduleName;
            }
        }

        sort($addonModules);

        return $addonModules;
    }
}

if (! function_exists('getAllEnabledModules')) {
    /**
     * Get all enabled modules including core modules
     */
    function getAllEnabledModules(): array
    {
        $modulesStatusPath = base_path('modules_statuses.json');

        if (! file_exists($modulesStatusPath)) {
            return getCoreModules();
        }

        $modulesStatus = json_decode(file_get_contents($modulesStatusPath), true);

        if (! is_array($modulesStatus)) {
            return getCoreModules();
        }

        $enabledModules = [];
        foreach ($modulesStatus as $moduleName => $isEnabled) {
            if ($isEnabled && ! isSystemModule($moduleName)) {
                $enabledModules[] = $moduleName;
            }
        }

        sort($enabledModules);

        return $enabledModules;
    }
}

if (! function_exists('getModuleCategory')) {
    /**
     * Get module category (core, system, or addon)
     */
    function getModuleCategory(string $moduleName): string
    {
        if (isCoreModule($moduleName)) {
            return 'core';
        }

        if (isSystemModule($moduleName)) {
            return 'system';
        }

        return 'addon';
    }
}
