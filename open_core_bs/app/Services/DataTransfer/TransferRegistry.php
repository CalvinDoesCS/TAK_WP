<?php

namespace App\Services\DataTransfer;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;

/**
 * TransferRegistry Service
 *
 * Auto-discovers and manages import/export classes from enabled modules.
 * Scans all enabled modules for Import and Export classes and provides
 * metadata about available data transfer types.
 */
class TransferRegistry
{
    /**
     * Cache key for storing discovered transfer types
     */
    private const CACHE_KEY = 'transfer_registry_types';

    /**
     * Cache duration in seconds (1 hour)
     */
    private const CACHE_DURATION = 3600;

    /**
     * Default icon mapping for common entity types
     */
    private const DEFAULT_ICONS = [
        'employee' => 'bx bx-user',
        'department' => 'bx bx-buildings',
        'designation' => 'bx bx-briefcase',
        'shift' => 'bx bx-time',
        'holiday' => 'bx bx-calendar-event',
        'leave' => 'bx bx-calendar',
        'client' => 'bx bx-group',
        'product' => 'bx bx-package',
        'expense' => 'bx bx-money',
        'team' => 'bx bx-network-chart',
    ];

    /**
     * Get all available transfer types from enabled modules
     *
     * @param  bool  $fresh  Whether to bypass cache and get fresh results
     * @return array Array of transfer types with metadata
     */
    public function getAvailableTypes(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_DURATION, function () {
            return $this->discoverTransferTypes();
        });
    }

    /**
     * Discover transfer types from all enabled modules
     *
     * @return array Array of transfer types with complete metadata
     */
    private function discoverTransferTypes(): array
    {
        $types = [];

        // First, scan core app directory for Import/Export classes
        $coreTypes = $this->scanCoreAppClasses();
        $types = array_merge($types, $coreTypes);

        // Then scan all enabled modules
        $enabledModules = Module::allEnabled();

        foreach ($enabledModules as $module) {
            $moduleName = $module->getName();
            $moduleMetadata = $this->getModuleMetadata($moduleName);

            // Scan for import classes
            $imports = $this->scanForClasses($moduleName, 'Imports', 'Import');

            // Scan for export classes
            $exports = $this->scanForClasses($moduleName, 'Exports', 'Export');

            // Merge import and export discoveries
            $allClasses = array_merge(array_keys($imports), array_keys($exports));
            $uniqueTypes = array_unique($allClasses);

            foreach ($uniqueTypes as $typeName) {
                $types[] = [
                    'key' => Str::snake($typeName),
                    'name' => Str::headline($typeName),
                    'module' => $moduleName,
                    'category' => $moduleMetadata['category'] ?? 'General',
                    'icon' => $this->resolveIcon($typeName, $moduleMetadata),
                    'import_class' => $imports[$typeName] ?? null,
                    'export_class' => $exports[$typeName] ?? null,
                    'has_import' => isset($imports[$typeName]),
                    'has_export' => isset($exports[$typeName]),
                ];
            }
        }

        // Sort by category, then by name
        usort($types, function ($a, $b) {
            $categoryCompare = strcmp($a['category'], $b['category']);
            if ($categoryCompare !== 0) {
                return $categoryCompare;
            }

            return strcmp($a['name'], $b['name']);
        });

        return $types;
    }

    /**
     * Scan core app directory for Import/Export classes
     *
     * @return array Array of transfer types from core app
     */
    private function scanCoreAppClasses(): array
    {
        $types = [];
        $appPath = app_path();

        // Scan app/Imports directory
        $imports = [];
        $importsDir = "{$appPath}/Imports";
        if (File::isDirectory($importsDir)) {
            $files = File::glob("{$importsDir}/*Import.php");
            foreach ($files as $file) {
                $filename = basename($file, '.php');
                if (Str::endsWith($filename, 'Import')) {
                    $typeName = Str::before($filename, 'Import');
                    $className = "App\\Imports\\{$filename}";
                    if (class_exists($className)) {
                        $imports[$typeName] = $className;
                    }
                }
            }
        }

        // Scan app/Exports directory
        $exports = [];
        $exportsDir = "{$appPath}/Exports";
        if (File::isDirectory($exportsDir)) {
            $files = File::glob("{$exportsDir}/*Export.php");
            foreach ($files as $file) {
                $filename = basename($file, '.php');
                if (Str::endsWith($filename, 'Export')) {
                    $typeName = Str::before($filename, 'Export');
                    $className = "App\\Exports\\{$filename}";
                    if (class_exists($className)) {
                        $exports[$typeName] = $className;
                    }
                }
            }
        }

        // Merge and create types
        $allClasses = array_merge(array_keys($imports), array_keys($exports));
        $uniqueTypes = array_unique($allClasses);

        foreach ($uniqueTypes as $typeName) {
            $types[] = [
                'key' => Str::snake($typeName),
                'name' => Str::headline($typeName),
                'module' => 'Core',
                'category' => 'Core Models',
                'icon' => $this->resolveIcon($typeName, []),
                'import_class' => $imports[$typeName] ?? null,
                'export_class' => $exports[$typeName] ?? null,
                'has_import' => isset($imports[$typeName]),
                'has_export' => isset($exports[$typeName]),
            ];
        }

        return $types;
    }

    /**
     * Scan a module directory for Import or Export classes
     *
     * @param  string  $moduleName  Module name (e.g., 'DataImportExport')
     * @param  string  $subdirectory  Subdirectory to scan ('Imports' or 'Exports')
     * @param  string  $suffix  Class suffix ('Import' or 'Export')
     * @return array Associative array with type name as key and full class name as value
     */
    private function scanForClasses(string $moduleName, string $subdirectory, string $suffix): array
    {
        $classes = [];
        $directory = module_path($moduleName, "app/{$subdirectory}");

        if (! File::isDirectory($directory)) {
            return $classes;
        }

        $files = File::glob("{$directory}/*{$suffix}.php");

        foreach ($files as $file) {
            $filename = basename($file, '.php');

            // Extract type name by removing the suffix
            if (Str::endsWith($filename, $suffix)) {
                $typeName = Str::before($filename, $suffix);
                $className = "Modules\\{$moduleName}\\App\\{$subdirectory}\\{$filename}";

                // Verify class exists
                if (class_exists($className)) {
                    $classes[$typeName] = $className;
                }
            }
        }

        return $classes;
    }

    /**
     * Get metadata from module.json
     *
     * @param  string  $moduleName  Module name
     * @return array Module metadata
     */
    private function getModuleMetadata(string $moduleName): array
    {
        $modulePath = module_path($moduleName);
        $jsonPath = "{$modulePath}/module.json";

        if (! File::exists($jsonPath)) {
            return [];
        }

        $content = File::get($jsonPath);
        $data = json_decode($content, true);

        return $data ?? [];
    }

    /**
     * Resolve icon for a transfer type
     *
     * Priority:
     * 1. Check for icon in type name mapping
     * 2. Use default icon from module metadata
     * 3. Fall back to generic icon
     *
     * @param  string  $typeName  Type name (e.g., 'LeaveType')
     * @param  array  $moduleMetadata  Module metadata from module.json
     * @return string Boxicon class
     */
    private function resolveIcon(string $typeName, array $moduleMetadata): string
    {
        // Convert type name to lowercase for comparison
        $typeKey = strtolower($typeName);

        // Check each default icon key to see if it's contained in the type name
        foreach (self::DEFAULT_ICONS as $key => $icon) {
            if (Str::contains($typeKey, $key)) {
                return $icon;
            }
        }

        // Fall back to generic icon
        return 'bx bx-data';
    }

    /**
     * Get transfer type by key
     *
     * @param  string  $key  Transfer type key (snake_case)
     * @return array|null Transfer type data or null if not found
     */
    public function getTypeByKey(string $key): ?array
    {
        $types = $this->getAvailableTypes();

        foreach ($types as $type) {
            if ($type['key'] === $key) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Get all transfer types for a specific module
     *
     * @param  string  $moduleName  Module name
     * @return array Array of transfer types for the module
     */
    public function getTypesByModule(string $moduleName): array
    {
        $types = $this->getAvailableTypes();

        return array_filter($types, function ($type) use ($moduleName) {
            return $type['module'] === $moduleName;
        });
    }

    /**
     * Get all transfer types for a specific category
     *
     * @param  string  $category  Category name
     * @return array Array of transfer types in the category
     */
    public function getTypesByCategory(string $category): array
    {
        $types = $this->getAvailableTypes();

        return array_filter($types, function ($type) use ($category) {
            return $type['category'] === $category;
        });
    }

    /**
     * Check if a transfer type exists and supports import
     *
     * @param  string  $key  Transfer type key
     * @return bool True if type exists and has import support
     */
    public function hasImport(string $key): bool
    {
        $type = $this->getTypeByKey($key);

        return $type && $type['has_import'];
    }

    /**
     * Check if a transfer type exists and supports export
     *
     * @param  string  $key  Transfer type key
     * @return bool True if type exists and has export support
     */
    public function hasExport(string $key): bool
    {
        $type = $this->getTypeByKey($key);

        return $type && $type['has_export'];
    }

    /**
     * Get import class for a transfer type
     *
     * @param  string  $key  Transfer type key
     * @return string|null Fully qualified import class name or null
     */
    public function getImportClass(string $key): ?string
    {
        $type = $this->getTypeByKey($key);

        return $type['import_class'] ?? null;
    }

    /**
     * Get export class for a transfer type
     *
     * @param  string  $key  Transfer type key
     * @return string|null Fully qualified export class name or null
     */
    public function getExportClass(string $key): ?string
    {
        $type = $this->getTypeByKey($key);

        return $type['export_class'] ?? null;
    }

    /**
     * Clear the transfer types cache
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Refresh the cache by clearing and rebuilding
     *
     * @return array Fresh array of transfer types
     */
    public function refreshCache(): array
    {
        $this->clearCache();

        return $this->getAvailableTypes(true);
    }

    /**
     * Get all unique categories from available transfer types
     *
     * @return array Array of unique category names
     */
    public function getCategories(): array
    {
        $types = $this->getAvailableTypes();
        $categories = array_unique(array_column($types, 'category'));
        sort($categories);

        return $categories;
    }
}
