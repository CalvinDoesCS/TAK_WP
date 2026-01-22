<?php

namespace App\Services\AddonService;

use App\Models\Settings;
use Exception;
use Illuminate\Support\Facades\Schema;
use Nwidart\Modules\Facades\Module;

class AddonService implements IAddonService
{
    private ModuleDependencyService $dependencyService;

    public function __construct(ModuleDependencyService $dependencyService)
    {
        $this->dependencyService = $dependencyService;
    }

    /**
     * Check if the addon/module is enabled
     *
     * In SaaS mode, also checks if the tenant's plan includes the module.
     * Uses defensive programming so it works even if MultiTenancyCore is not shipped.
     *
     * @param  string  $name  Module name
     * @param  bool  $isStandard  Deprecated - kept for backward compatibility, ignored
     */
    public function isAddonEnabled(string $name, bool $isStandard = false): bool
    {
        try {
            // Check if settings table exists before querying
            if (! Schema::hasTable('settings')) {
                return false;
            }

            $settings = Settings::first();
            if (! $settings) {
                return false;
            }

            $addons = $settings->available_modules ?? [];

            if ($isStandard) {
                return is_array($addons) && \in_array($name, $addons);
            }

            // Check if module exists and is enabled globally
            $module = Module::find($name);
            if ($module === null || ! $module->isEnabled()) {
                return false;
            }

            // In SaaS mode, also check if tenant's plan includes this module
            // Uses function_exists() so it works even if MultiTenancyCore not shipped
            if (\function_exists('isSaaSMode') && isSaaSMode()) {
                // Core modules are always accessible
                if (\function_exists('isCoreModule') && isCoreModule($name)) {
                    return true;
                }

                // Check tenant's plan - no direct class imports
                if (app()->has('tenant')) {
                    $tenant = app('tenant');

                    // Ensure tenant has activeSubscription method
                    if (\is_object($tenant) && \method_exists($tenant, 'activeSubscription')) {
                        $subscription = $tenant->activeSubscription()->with('plan')->first();

                        if ($subscription && $subscription->plan && \method_exists($subscription->plan, 'hasModule')) {
                            return $subscription->plan->hasModule($name);
                        }
                    }

                    // No valid subscription = deny addon access in SaaS mode
                    return false;
                }
                // No tenant context (central domain) = allow all modules
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if a module can be enabled (all dependencies met)
     *
     * @param  string  $moduleName  The module name
     * @return bool True if module can be enabled
     */
    public function canEnableModule(string $moduleName): bool
    {
        return $this->dependencyService->canEnable($moduleName);
    }

    /**
     * Check if a module can be disabled (no enabled dependents)
     *
     * @param  string  $moduleName  The module name
     * @return bool True if module can be disabled
     */
    public function canDisableModule(string $moduleName): bool
    {
        return $this->dependencyService->canDisable($moduleName);
    }

    /**
     * Get missing dependencies for a module
     *
     * @param  string  $moduleName  The module name
     * @return array Array of missing dependencies with details
     */
    public function getMissingDependencies(string $moduleName): array
    {
        return $this->dependencyService->getMissingDependencies($moduleName);
    }

    /**
     * Get enabled modules that depend on this module
     *
     * @param  string  $moduleName  The module name
     * @return array Array of enabled dependent module names
     */
    public function getEnabledDependents(string $moduleName): array
    {
        return $this->dependencyService->getEnabledDependents($moduleName);
    }

    /**
     * Get the dependency service instance
     */
    public function getDependencyService(): ModuleDependencyService
    {
        return $this->dependencyService;
    }
}
