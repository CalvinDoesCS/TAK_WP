<?php

namespace App\Http\Controllers;

use App\Services\AddonService\ModuleDependencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Facades\Module;
use Yajra\DataTables\Facades\DataTables;
use ZipArchive;

class AddonController extends Controller
{
    public function __construct(
        protected ModuleDependencyService $dependencyService
    ) {}

    public function index()
    {
        // Get all available modules
        $modules = Module::all();

        return view('addons.index', [
            'modules' => $modules,
        ]);
    }

    /**
     * Server-side DataTable endpoint for modules
     */
    public function indexAjax(Request $request)
    {
        // Get all modules and prepare data upfront to avoid timeout
        $modules = collect(Module::all())->map(function ($module) {
            $moduleJson = $this->getModuleJson($module);
            $moduleName = $module->getName();
            $isEnabled = $module->isEnabled();

            return [
                'name' => $moduleName,
                'displayName' => $moduleJson['displayName'] ?? $moduleName,
                'description' => $moduleJson['description'] ?? __('No description available'),
                'category' => $moduleJson['category'] ?? __('Uncategorized'),
                'version' => $moduleJson['version'] ?? '-',
                'isEnabled' => $isEnabled,
                'purchaseUrl' => $moduleJson['purchaseUrl'] ?? 'https://czappstudio.com/open-core-bs-addons/',
                'isCoreModule' => $moduleJson['isCoreModule'] ?? false,
                'dependencies' => $this->dependencyService->getDependencies($moduleName),
                'dependents' => $this->dependencyService->getDependents($moduleName),
            ];
        });

        // Apply filters
        if ($request->filled('category') && $request->category !== 'all') {
            $modules = $modules->where('category', $request->category);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $isEnabled = $request->status === 'enabled';
            $modules = $modules->where('isEnabled', $isEnabled);
        }

        // Apply search
        if ($request->filled('search.value')) {
            $search = strtolower($request->input('search.value'));
            $modules = $modules->filter(function ($module) use ($search) {
                return str_contains(strtolower($module['displayName']), $search)
                    || str_contains(strtolower($module['description']), $search)
                    || str_contains(strtolower($module['category']), $search);
            });
        }

        return DataTables::of($modules)
            ->editColumn('displayName', function ($module) {
                $name = e($module['displayName']);

                // Add core module badge if applicable
                if ($module['isCoreModule'] || isCoreModule($module['name'])) {
                    $name .= ' <span class="badge bg-label-success ms-2"><i class="bx bx-check-shield"></i> '.__('Core').'</span>';
                }

                return $name;
            })
            ->editColumn('category', function ($module) {
                return '<span class="badge bg-label-primary">'.$module['category'].'</span>';
            })
            ->editColumn('isEnabled', function ($module) {
                if ($module['isEnabled']) {
                    return '<span class="badge bg-label-success">'.__('Enabled').'</span>';
                }

                return '<span class="badge bg-label-secondary">'.__('Disabled').'</span>';
            })
            ->addColumn('dependencies_count', function ($module) {
                $count = count($module['dependencies']);
                if ($count > 0) {
                    $tooltip = implode(', ', $module['dependencies']);

                    return "<span class='badge bg-label-info' data-bs-toggle='tooltip' title='{$tooltip}'>{$count}</span>";
                }

                return '<span class="badge bg-label-secondary">0</span>';
            })
            ->addColumn('dependents_count', function ($module) {
                $count = count($module['dependents']);
                if ($count > 0) {
                    $tooltip = implode(', ', $module['dependents']);

                    return "<span class='badge bg-label-warning' data-bs-toggle='tooltip' title='{$tooltip}'>{$count}</span>";
                }

                return '<span class="badge bg-label-secondary">0</span>';
            })
            ->addColumn('actions', function ($module) {
                $actions = [];

                // Enable/Disable action
                if ($module['isEnabled']) {
                    $actions[] = [
                        'label' => __('Disable'),
                        'icon' => 'bx bx-power-off',
                        'class' => 'text-warning',
                        'onclick' => "disableModule('{$module['name']}')",
                    ];
                } else {
                    $actions[] = [
                        'label' => __('Enable'),
                        'icon' => 'bx bx-check-circle',
                        'class' => 'text-success',
                        'onclick' => "enableModule('{$module['name']}')",
                    ];
                }

                // View details
                $actions[] = [
                    'label' => __('View Details'),
                    'icon' => 'bx bx-show',
                    'onclick' => "showModuleDetails('{$module['name']}')",
                ];

                // Purchase/Uninstall
                if (! $module['isCoreModule']) {
                    $actions[] = [
                        'label' => __('Purchase'),
                        'icon' => 'bx bx-cart',
                        'href' => $module['purchaseUrl'],
                        'target' => '_blank',
                    ];

                    $actions[] = [
                        'label' => __('Uninstall'),
                        'icon' => 'bx bx-trash',
                        'class' => 'text-danger',
                        'onclick' => "uninstallModule('{$module['name']}')",
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $module['name'],
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['displayName', 'category', 'isEnabled', 'dependencies_count', 'dependents_count', 'actions'])
            ->make(true);
    }

    /**
     * Get module details for offcanvas
     */
    public function show(string $module)
    {
        $moduleInstance = Module::find($module);

        if (! $moduleInstance) {
            return response()->json([
                'success' => false,
                'message' => __('Module not found.'),
            ], 404);
        }

        $moduleJson = $this->getModuleJson($moduleInstance);

        // Get dependencies with status
        $dependencies = collect($this->dependencyService->getDependencies($module))->map(function ($dep) {
            $depModule = Module::find($dep);
            $depJson = $depModule ? $this->getModuleJson($depModule) : null;

            return [
                'name' => $dep,
                'displayName' => $depJson['displayName'] ?? $dep,
                'enabled' => $depModule && $depModule->isEnabled(),
                'installed' => $depModule !== null,
            ];
        })->toArray();

        // Get dependents with status
        $dependents = collect($this->dependencyService->getDependents($module))->map(function ($dep) {
            $depModule = Module::find($dep);
            $depJson = $depModule ? $this->getModuleJson($depModule) : null;

            return [
                'name' => $dep,
                'displayName' => $depJson['displayName'] ?? $dep,
                'enabled' => $depModule && $depModule->isEnabled(),
                'installed' => $depModule !== null,
            ];
        })->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $module,
                'displayName' => $moduleJson['displayName'] ?? $module,
                'description' => $moduleJson['description'] ?? __('No description available'),
                'version' => $moduleJson['version'] ?? '-',
                'category' => $moduleJson['category'] ?? __('Uncategorized'),
                'enabled' => $moduleInstance->isEnabled(),
                'isCoreModule' => $moduleJson['isCoreModule'] ?? false,
                'keywords' => $moduleJson['keywords'] ?? [],
                'documentationUrl' => $moduleJson['documentationUrl'] ?? null,
                'tutorialUrl' => $moduleJson['tutorialUrl'] ?? null,
                'changelogUrl' => $moduleJson['changelogUrl'] ?? null,
                'purchaseUrl' => $moduleJson['purchaseUrl'] ?? 'https://czappstudio.com',
                'dependencies' => $dependencies,
                'dependents' => $dependents,
            ],
        ]);
    }

    /**
     * Check if module can be enabled or disabled
     */
    public function checkDependencies(string $module)
    {
        $moduleInstance = Module::find($module);

        if (! $moduleInstance) {
            return response()->json([
                'success' => false,
                'message' => __('Module not found.'),
            ], 404);
        }

        $canEnable = $this->dependencyService->canEnable($module);
        $canDisable = $this->dependencyService->canDisable($module);
        $missing = $this->dependencyService->getMissingDependencies($module);
        $enabledDependents = $this->dependencyService->getEnabledDependents($module);

        $messages = [];

        if (! $canEnable && ! $moduleInstance->isEnabled()) {
            $missingNames = array_map(function ($dep) {
                return $dep['name'];
            }, $missing);
            $messages[] = __('Cannot enable :module. Missing dependencies: :dependencies', [
                'module' => $module,
                'dependencies' => implode(', ', $missingNames),
            ]);
        }

        if (! $canDisable && $moduleInstance->isEnabled()) {
            $messages[] = __('Cannot disable :module. The following modules depend on it: :dependents', [
                'module' => $module,
                'dependents' => implode(', ', $enabledDependents),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'canEnable' => $canEnable,
                'canDisable' => $canDisable,
                'missing' => $missing,
                'dependents' => $enabledDependents,
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * Get statistics for dashboard cards
     */
    public function statistics()
    {
        $allModules = collect(Module::all());
        $activeModules = $allModules->filter(fn ($module) => $module->isEnabled());
        $disabledModules = $allModules->filter(fn ($module) => ! $module->isEnabled());

        // Get category counts
        $categories = [];
        foreach ($allModules as $module) {
            $moduleJson = $this->getModuleJson($module);
            $category = $moduleJson['category'] ?? 'Uncategorized';
            if (! isset($categories[$category])) {
                $categories[$category] = 0;
            }
            $categories[$category]++;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_modules' => $allModules->count(),
                'active_modules' => $activeModules->count(),
                'disabled_modules' => $disabledModules->count(),
                'categories' => $categories,
            ],
        ]);
    }

    // Enable a module (addon)
    public function activate(Request $request)
    {
        if (config('app.demo')) {
            return response()->json([
                'success' => false,
                'message' => __('This feature is disabled in the demo.'),
            ], 403);
        }

        $moduleName = $request->input('module');
        $module = Module::find($moduleName);

        if (! $module) {
            return response()->json([
                'success' => false,
                'message' => __('Module not found.'),
            ], 404);
        }

        // Check if dependencies are met
        if (! $this->dependencyService->canEnable($moduleName)) {
            $missing = $this->dependencyService->getMissingDependencies($moduleName);
            $missingNames = array_map(fn ($dep) => $dep['name'], $missing);

            return response()->json([
                'success' => false,
                'message' => __('Cannot enable :module. Missing dependencies: :dependencies', [
                    'module' => $moduleName,
                    'dependencies' => implode(', ', $missingNames),
                ]),
                'missing' => $missing,
            ], 400);
        }

        // Enable the module using Artisan
        Artisan::call('module:enable', ['module' => $moduleName]);

        // Clear dependency cache
        $this->dependencyService->clearCache();

        // Get updated module data
        $moduleJson = $this->getModuleJson($module);

        return response()->json([
            'success' => true,
            'message' => __('Module :module enabled successfully.', ['module' => $moduleJson['displayName'] ?? $moduleName]),
            'data' => [
                'name' => $moduleName,
                'enabled' => true,
            ],
        ]);
    }

    // Disable a module (addon)
    public function deactivate(Request $request)
    {
        if (config('app.demo')) {
            return response()->json([
                'success' => false,
                'message' => __('This feature is disabled in the demo.'),
            ], 403);
        }

        $moduleName = $request->input('module');
        $module = Module::find($moduleName);

        if (! $module) {
            return response()->json([
                'success' => false,
                'message' => __('Module not found.'),
            ], 404);
        }

        // Check if any enabled modules depend on this module
        if (! $this->dependencyService->canDisable($moduleName)) {
            $dependents = $this->dependencyService->getEnabledDependents($moduleName);

            return response()->json([
                'success' => false,
                'message' => __('Cannot disable :module. The following modules depend on it: :dependents', [
                    'module' => $moduleName,
                    'dependents' => implode(', ', $dependents),
                ]),
                'dependents' => $dependents,
            ], 400);
        }

        // Disable the module using Artisan
        Artisan::call('module:disable', ['module' => $moduleName]);

        // Clear dependency cache
        $this->dependencyService->clearCache();

        // Get updated module data
        $moduleJson = $this->getModuleJson($module);

        return response()->json([
            'success' => true,
            'message' => __('Module :module disabled successfully.', ['module' => $moduleJson['displayName'] ?? $moduleName]),
            'data' => [
                'name' => $moduleName,
                'enabled' => false,
            ],
        ]);
    }

    // Upload and install a new module (addon)
    public function upload(Request $request)
    {
        if (config('app.demo')) {
            return redirect()->back()->with('error', 'This feature is disabled in the demo.');
        }

        // Validate the file input, ensuring it is a zip file
        $request->validate([
            'module' => 'required|file|mimes:zip|max:20480', // Limit file size to 20MB
        ]);

        // Enhanced validation for required module.json fields will be checked after extraction

        // Store the uploaded file temporarily
        $file = $request->file('module');
        $fileName = $file->getClientOriginalName();
        $tempPath = storage_path('modules/'.$fileName);
        $file->move(storage_path('modules'), $fileName);

        // Extract the zip file to a temporary location
        $zip = new ZipArchive;
        if ($zip->open($tempPath) === true) {
            // Get the base filename without any extension or extra path
            $moduleFolderName = pathinfo($fileName, PATHINFO_FILENAME);

            // Define the extraction path using just the module name
            $extractPath = storage_path('modules/extracted/');

            // Extract the zip to the extraction path
            $zip->extractTo($extractPath);
            $zip->close();
        } else {
            return redirect()->back()->with('error', 'Failed to extract the module.');
        }

        // Validate that the extracted directory contains module.json (and possibly other expected files)
        if (! File::exists($extractPath.$moduleFolderName.'/module.json')) {
            // If no module.json is found, delete extracted files and return an error
            File::deleteDirectory($extractPath);
            // Delete the zip file
            File::delete($tempPath);

            return redirect()->back()->with('error', 'Invalid addon: module.json not found.');
        }

        // Check if the same module is already installed
        if (Module::find($moduleFolderName)) {
            // If the module is already installed, delete extracted files and return an error
            File::deleteDirectory($extractPath);
            // Delete the zip file
            File::delete($tempPath);

            return redirect()->back()->with('error', 'Module already installed.');
        }

        // Move the extracted module to the Modules directory
        File::moveDirectory($extractPath.$moduleFolderName, base_path('Modules/'.pathinfo($fileName, PATHINFO_FILENAME)));

        // Clean up: delete the uploaded zip file
        File::delete($tempPath);

        // Run module-specific migrations and seeders to ensure the module works correctly
        try {
            // Run migrations for the newly installed module
            Artisan::call('module:migrate', [
                'module' => $moduleFolderName,
                '--force' => true,
            ]);
            Log::info("Migrations completed for module: {$moduleFolderName}");

            // Run seeders for the newly installed module (if any exist)
            try {
                Artisan::call('module:seed', [
                    'module' => $moduleFolderName,
                    '--force' => true,
                ]);
                Log::info("Seeders completed for module: {$moduleFolderName}");
            } catch (\Exception $e) {
                // Seeder might not exist for all modules, log but don't fail
                Log::info("No seeders found or seeder failed for module: {$moduleFolderName} - {$e->getMessage()}");
            }

            // Enable the module after successful migration
            Artisan::call('module:enable', ['module' => $moduleFolderName]);
            Log::info("Module enabled: {$moduleFolderName}");

            // Clear caches to ensure fresh state
            Artisan::call('optimize:clear');

            // Clear dependency cache
            $this->dependencyService->clearCache();

        } catch (\Exception $e) {
            Log::error("Failed to setup module {$moduleFolderName}: {$e->getMessage()}");

            return redirect()->back()->with('error', "Module uploaded but setup failed: {$e->getMessage()}");
        }

        return redirect()->back()->with('success', "Module {$moduleFolderName} installed and activated successfully.");

    }

    public function uninstall(Request $request)
    {

        try {

            if (config('app.demo')) {
                return redirect()->back()->with('error', 'This feature is disabled in the demo.');
            }

            $moduleName = $request->input('module');

            // Disable the module before uninstalling
            Artisan::call('module:disable', ['module' => $moduleName]);

            // Remove the module's directory
            $modulePath = base_path('Modules/'.$moduleName);
            if (File::exists($modulePath)) {
                File::deleteDirectory($modulePath);
            }

            // You might also want to clean up any module-specific database tables here.
            // Example: \Artisan::call('module:migrate-rollback', ['module' => $moduleName]);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

        return redirect()->back()->with('success', 'Module uninstalled successfully.');
    }

    public function update(Request $request)
    {
        if (config('app.demo')) {
            return redirect()->back()->with('error', 'This feature is disabled in the demo.');
        }

        $moduleName = $request->input('module');

        // Logic for updating the module
        // This could involve downloading the latest version of the module and replacing the old files

        return redirect()->back()->with('success', 'Module updated successfully.');
    }

    /**
     * Helper method to get module.json data
     */
    protected function getModuleJson($module): array
    {
        $jsonPath = $module->getPath().'/module.json';

        if (! File::exists($jsonPath)) {
            return [];
        }

        $content = File::get($jsonPath);
        $data = json_decode($content, true);

        return $data ?? [];
    }

    /**
     * Helper method to get dependency count
     */
    protected function getDependencyCount(string $moduleName): int
    {
        return count($this->dependencyService->getDependencies($moduleName));
    }

    /**
     * Helper method to get dependent count
     */
    protected function getDependentCount(string $moduleName): int
    {
        return count($this->dependencyService->getDependents($moduleName));
    }
}
