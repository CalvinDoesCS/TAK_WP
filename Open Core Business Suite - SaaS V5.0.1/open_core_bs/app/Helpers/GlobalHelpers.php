<?php

/**
 * Global helper functions that need to be available before modules are loaded
 */

if (! function_exists('isSaaSMode')) {
    /**
     * Check if MultiTenancyCore module is enabled (SaaS mode)
     * This is a global helper that works even if the module is disabled
     */
    function isSaaSMode(): bool
    {
        try {
            // Check if app is booted and Module facade is available
            if (! app()->bound('modules')) {
                return false;
            }

            $module = app('modules')->find('MultiTenancyCore');
            return $module?->isEnabled() ?? false;
        } catch (\Exception | \Error $e) {
            // If Module is not available yet or any error occurs, return false
            return false;
        }
    }
}

if (! function_exists('moduleExists')) {
    /**
     * Check if a module exists and is enabled
     */
    function moduleExists(string $moduleName): bool
    {
        try {
            // Check if app is booted and Module facade is available
            if (! app()->bound('modules')) {
                return false;
            }

            $module = app('modules')->find($moduleName);
            return $module?->isEnabled() ?? false;
        } catch (\Exception | \Error $e) {
            return false;
        }
    }
}
