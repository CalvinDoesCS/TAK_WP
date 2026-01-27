<?php

namespace App\Services\AddonService;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;

class ModuleDependencyService
{
    private const CACHE_KEY = 'module_dependencies_cache';

    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Validate if all required dependencies for a module are enabled
     *
     * @param  string  $moduleName  The module name to validate
     * @return array Returns ['valid' => bool, 'missing' => array]
     */
    public function validateDependencies(string $moduleName): array
    {
        $dependencies = $this->getModuleDependencies($moduleName);

        if (empty($dependencies)) {
            return ['valid' => true, 'missing' => []];
        }

        $missingDependencies = [];

        foreach ($dependencies as $dependency) {
            $dependencyModule = Module::find($dependency);

            if (! $dependencyModule) {
                $missingDependencies[] = [
                    'name' => $dependency,
                    'status' => 'not_found',
                    'message' => "Module '{$dependency}' does not exist",
                ];

                continue;
            }

            if (! $dependencyModule->isEnabled()) {
                $missingDependencies[] = [
                    'name' => $dependency,
                    'status' => 'disabled',
                    'message' => "Module '{$dependency}' is disabled",
                ];
            }
        }

        return [
            'valid' => empty($missingDependencies),
            'missing' => $missingDependencies,
        ];
    }

    /**
     * Get list of dependencies for a module
     *
     * @param  string  $moduleName  The module name
     * @return array Array of module names that this module depends on
     */
    public function getDependencies(string $moduleName): array
    {
        return $this->getModuleDependencies($moduleName);
    }

    /**
     * Get list of modules that depend on the specified module
     *
     * @param  string  $moduleName  The module name to check dependents for
     * @return array Array of module names that depend on this module
     */
    public function getDependents(string $moduleName): array
    {
        $allModules = Module::all();
        $dependents = [];

        foreach ($allModules as $module) {
            $dependencies = $this->getModuleDependencies($module->getName());

            if (in_array($moduleName, $dependencies)) {
                $dependents[] = $module->getName();
            }
        }

        return $dependents;
    }

    /**
     * Get full dependency tree for a module recursively
     *
     * @param  string  $moduleName  The module name
     * @param  array  $visited  Internal parameter to track visited modules (prevents circular references)
     * @return array Nested array structure representing the dependency tree
     */
    public function getDependencyTree(string $moduleName, array &$visited = []): array
    {
        // Prevent circular dependencies from causing infinite recursion
        if (in_array($moduleName, $visited)) {
            return [
                'name' => $moduleName,
                'circular' => true,
                'dependencies' => [],
            ];
        }

        $visited[] = $moduleName;

        $module = Module::find($moduleName);
        $dependencies = $this->getModuleDependencies($moduleName);

        $tree = [
            'name' => $moduleName,
            'exists' => $module !== null,
            'enabled' => $module ? $module->isEnabled() : false,
            'dependencies' => [],
        ];

        foreach ($dependencies as $dependency) {
            $tree['dependencies'][] = $this->getDependencyTree($dependency, $visited);
        }

        return $tree;
    }

    /**
     * Check if a module can be safely enabled
     *
     * @param  string  $moduleName  The module name to check
     * @return bool True if module can be enabled
     */
    public function canEnable(string $moduleName): bool
    {
        $module = Module::find($moduleName);

        if (! $module) {
            return false;
        }

        // If already enabled, return true
        if ($module->isEnabled()) {
            return true;
        }

        // Check if all dependencies are met
        $validation = $this->validateDependencies($moduleName);

        return $validation['valid'];
    }

    /**
     * Check if a module can be safely disabled
     *
     * @param  string  $moduleName  The module name to check
     * @return bool True if module can be disabled
     */
    public function canDisable(string $moduleName): bool
    {
        $module = Module::find($moduleName);

        if (! $module) {
            return false;
        }

        // If already disabled, return true
        if (! $module->isEnabled()) {
            return true;
        }

        // Check if any enabled modules depend on this module
        $dependents = $this->getDependents($moduleName);
        $enabledDependents = [];

        foreach ($dependents as $dependent) {
            $dependentModule = Module::find($dependent);
            if ($dependentModule && $dependentModule->isEnabled()) {
                $enabledDependents[] = $dependent;
            }
        }

        return empty($enabledDependents);
    }

    /**
     * Get list of modules that must be enabled before this module
     *
     * @param  string  $moduleName  The module name
     * @return array Array of module names that need to be enabled
     */
    public function getRequiredToEnable(string $moduleName): array
    {
        $dependencies = $this->getModuleDependencies($moduleName);
        $required = [];

        foreach ($dependencies as $dependency) {
            $dependencyModule = Module::find($dependency);

            // If dependency doesn't exist or is disabled, it's required
            if (! $dependencyModule || ! $dependencyModule->isEnabled()) {
                $required[] = $dependency;

                // Recursively get dependencies of this dependency
                $nestedRequired = $this->getRequiredToEnable($dependency);
                $required = array_merge($required, $nestedRequired);
            }
        }

        // Remove duplicates and return
        return array_values(array_unique($required));
    }

    /**
     * Get list of missing dependencies with detailed information
     *
     * @param  string  $moduleName  The module name
     * @return array Array with module names and details about why they're missing
     */
    public function getMissingDependencies(string $moduleName): array
    {
        $validation = $this->validateDependencies($moduleName);

        return $validation['missing'];
    }

    /**
     * Calculate correct order to enable multiple modules
     * Resolves dependency chains to determine proper enabling sequence
     *
     * @param  array  $moduleNames  Array of module names to enable
     * @return array Ordered array of module names
     *
     * @throws Exception If circular dependency is detected
     */
    public function getEnableOrder(array $moduleNames): array
    {
        $ordered = [];
        $visited = [];
        $visiting = [];

        foreach ($moduleNames as $moduleName) {
            $this->topologicalSort($moduleName, $visited, $visiting, $ordered);
        }

        return array_reverse($ordered);
    }

    /**
     * Topological sorting for dependency resolution
     *
     * @param  string  $moduleName  Current module being sorted
     * @param  array  $visited  Modules that have been fully processed
     * @param  array  $visiting  Modules currently being processed (for circular detection)
     * @param  array  $ordered  Final ordered result
     *
     * @throws Exception If circular dependency is detected
     */
    private function topologicalSort(string $moduleName, array &$visited, array &$visiting, array &$ordered): void
    {
        // If already processed, skip
        if (in_array($moduleName, $visited)) {
            return;
        }

        // Circular dependency detection
        if (in_array($moduleName, $visiting)) {
            throw new Exception("Circular dependency detected involving module: {$moduleName}");
        }

        // Mark as currently being processed
        $visiting[] = $moduleName;

        // Process all dependencies first
        $dependencies = $this->getModuleDependencies($moduleName);
        foreach ($dependencies as $dependency) {
            $this->topologicalSort($dependency, $visited, $visiting, $ordered);
        }

        // Remove from visiting and add to visited
        $visiting = array_diff($visiting, [$moduleName]);
        $visited[] = $moduleName;
        $ordered[] = $moduleName;
    }

    /**
     * Get dependencies declared in module.json for a specific module
     *
     * @param  string  $moduleName  The module name
     * @return array Array of dependency module names
     */
    private function getModuleDependencies(string $moduleName): array
    {
        $moduleData = $this->getModuleJsonData($moduleName);

        if (! $moduleData) {
            return [];
        }

        // Check for 'dependencies' or 'requires' key
        $dependencies = $moduleData['dependencies'] ?? $moduleData['requires'] ?? [];

        // Ensure it's an array
        if (! is_array($dependencies)) {
            return [];
        }

        return array_values($dependencies);
    }

    /**
     * Read and parse module.json file for a module
     * Results are cached for performance
     *
     * @param  string  $moduleName  The module name
     * @return array|null Parsed module.json data or null if file doesn't exist
     */
    private function getModuleJsonData(string $moduleName): ?array
    {
        // Check cache first
        $cacheKey = self::CACHE_KEY."_{$moduleName}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        // Get module path
        $module = Module::find($moduleName);
        if (! $module) {
            Cache::put($cacheKey, null, self::CACHE_TTL);

            return null;
        }

        $modulePath = $module->getPath();
        $moduleJsonPath = $modulePath.'/module.json';

        // Check if module.json exists
        if (! File::exists($moduleJsonPath)) {
            Cache::put($cacheKey, null, self::CACHE_TTL);

            return null;
        }

        // Read and parse JSON
        try {
            $content = File::get($moduleJsonPath);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Cache::put($cacheKey, null, self::CACHE_TTL);

                return null;
            }

            // Cache the result
            Cache::put($cacheKey, $data, self::CACHE_TTL);

            return $data;
        } catch (Exception $e) {
            Cache::put($cacheKey, null, self::CACHE_TTL);

            return null;
        }
    }

    /**
     * Clear the module dependencies cache
     * Useful after updating module.json files
     */
    public function clearCache(): void
    {
        $allModules = Module::all();

        foreach ($allModules as $module) {
            $cacheKey = self::CACHE_KEY.'_'.$module->getName();
            Cache::forget($cacheKey);
        }
    }

    /**
     * Get all modules with their dependency status
     * Useful for displaying module overview in UI
     *
     * @return array Array of modules with their dependency information
     */
    public function getAllModulesWithDependencyStatus(): array
    {
        $allModules = Module::all();
        $result = [];

        foreach ($allModules as $module) {
            $moduleName = $module->getName();
            $dependencies = $this->getModuleDependencies($moduleName);
            $dependents = $this->getDependents($moduleName);

            $result[] = [
                'name' => $moduleName,
                'enabled' => $module->isEnabled(),
                'dependencies' => $dependencies,
                'dependents' => $dependents,
                'can_enable' => $this->canEnable($moduleName),
                'can_disable' => $this->canDisable($moduleName),
                'missing_dependencies' => $this->getMissingDependencies($moduleName),
            ];
        }

        return $result;
    }

    /**
     * Check if a module has any dependencies
     *
     * @param  string  $moduleName  The module name
     * @return bool True if module has dependencies
     */
    public function hasDependencies(string $moduleName): bool
    {
        $dependencies = $this->getModuleDependencies($moduleName);

        return ! empty($dependencies);
    }

    /**
     * Check if a module has any dependents
     *
     * @param  string  $moduleName  The module name
     * @return bool True if other modules depend on this module
     */
    public function hasDependents(string $moduleName): bool
    {
        $dependents = $this->getDependents($moduleName);

        return ! empty($dependents);
    }

    /**
     * Get enabled dependents of a module
     * Useful for showing which modules will be affected if this module is disabled
     *
     * @param  string  $moduleName  The module name
     * @return array Array of enabled module names that depend on this module
     */
    public function getEnabledDependents(string $moduleName): array
    {
        $dependents = $this->getDependents($moduleName);
        $enabledDependents = [];

        foreach ($dependents as $dependent) {
            $dependentModule = Module::find($dependent);
            if ($dependentModule && $dependentModule->isEnabled()) {
                $enabledDependents[] = $dependent;
            }
        }

        return $enabledDependents;
    }

    /**
     * Get disabled dependencies of a module
     * Useful for showing which modules need to be enabled first
     *
     * @param  string  $moduleName  The module name
     * @return array Array of disabled module names that this module depends on
     */
    public function getDisabledDependencies(string $moduleName): array
    {
        $dependencies = $this->getModuleDependencies($moduleName);
        $disabledDependencies = [];

        foreach ($dependencies as $dependency) {
            $dependencyModule = Module::find($dependency);
            if (! $dependencyModule || ! $dependencyModule->isEnabled()) {
                $disabledDependencies[] = $dependency;
            }
        }

        return $disabledDependencies;
    }
}
