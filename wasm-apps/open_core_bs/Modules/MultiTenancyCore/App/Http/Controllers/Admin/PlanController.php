<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Admin;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\MultiTenancyCore\App\Models\Plan;
use Yajra\DataTables\Facades\DataTables;

class PlanController extends Controller
{
    /**
     * Display plan management page
     */
    public function index()
    {
        $modules = $this->getAvailableModules();

        return view('multitenancycore::admin.plans.index', compact('modules'));
    }

    /**
     * Get plans data for DataTables
     */
    public function indexAjax(Request $request)
    {
        $query = Plan::withCount(['subscriptions' => function ($q) {
            $q->whereIn('status', ['trial', 'active']);
        }]);

        return DataTables::of($query)
            ->addColumn('name_display', function ($plan) {
                return view('multitenancycore::admin.plans._name', compact('plan'))->render();
            })
            ->addColumn('price_display', function ($plan) {
                return view('multitenancycore::admin.plans._price', compact('plan'))->render();
            })
            ->addColumn('restrictions_summary', function ($plan) {
                return view('multitenancycore::admin.plans._features-summary', compact('plan'))->render();
            })
            ->addColumn('status_display', function ($plan) {
                $badge = $plan->is_active
                    ? '<span class="badge bg-label-success">'.__('Active').'</span>'
                    : '<span class="badge bg-label-secondary">'.__('Inactive').'</span>';

                return $badge;
            })
            ->addColumn('subscribers', function ($plan) {
                return '<span class="badge bg-label-primary">'.$plan->subscriptions_count.'</span>';
            })
            ->addColumn('actions', function ($plan) {
                return view('components.datatable-actions', [
                    'id' => $plan->id,
                    'actions' => [
                        [
                            'label' => __('Edit'),
                            'icon' => 'bx bx-edit',
                            'onclick' => "editPlan({$plan->id})",
                        ],
                        [
                            'label' => __('Delete'),
                            'icon' => 'bx bx-trash',
                            'onclick' => "deletePlan({$plan->id})",
                            'class' => 'text-danger',
                        ],
                    ],
                ])->render();
            })
            ->rawColumns(['name_display', 'price_display', 'restrictions_summary', 'status_display', 'subscribers', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new plan
     */
    public function create()
    {
        $modules = $this->getAvailableModules();
        $coreModules = $modules['core'] ?? [];
        // Flatten categorized modules into a simple array
        $categorized = $modules['categorized'] ?? [];
        $addonModules = ! empty($categorized) ? array_merge(...array_values($categorized)) : [];

        return view('multitenancycore::admin.plans.create', compact('modules', 'coreModules', 'addonModules'));
    }

    /**
     * Show the form for editing the specified plan
     */
    public function edit(Plan $plan)
    {
        $plan->loadCount('subscriptions');
        $modules = $this->getAvailableModules();
        $coreModules = $modules['core'] ?? [];
        // Flatten categorized modules into a simple array
        $categorized = $modules['categorized'] ?? [];
        $addonModules = ! empty($categorized) ? array_merge(...array_values($categorized)) : [];

        return view('multitenancycore::admin.plans.edit', compact('plan', 'modules', 'coreModules', 'addonModules'));
    }

    /**
     * Show plan details for editing
     */
    public function show(Plan $plan)
    {
        return response()->json($plan);
    }

    /**
     * Store new plan
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:plans,slug',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_period' => 'required|in:monthly,yearly,lifetime',
            'trial_days' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'restrictions' => 'array',
            'restrictions.max_users' => 'required|integer',
            'restrictions.max_employees' => 'required|integer',
            'restrictions.max_storage_gb' => 'required|numeric',
            'restrictions.modules' => 'array',
        ]);

        if ($validator->fails()) {
            return Error::response([
                'message' => __('Validation failed'),
                'errors' => $validator->errors(),
            ]);
        }

        try {
            DB::beginTransaction();

            $data = $validator->validated();

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
                // Ensure uniqueness
                $originalSlug = $data['slug'];
                $counter = 1;
                while (Plan::where('slug', $data['slug'])->exists()) {
                    $data['slug'] = $originalSlug.'-'.$counter;
                    $counter++;
                }
            }

            // Set boolean values
            $data['is_active'] = $request->boolean('is_active', true);
            $data['is_featured'] = $request->boolean('is_featured', false);

            // Handle module access logic
            $restrictions = $data['restrictions'] ?? [];
            if ($request->boolean('allow_all_modules')) {
                // Unlimited plan - set modules to null for all modules access
                $restrictions['modules'] = null;
            } else {
                // Ensure empty array when no modules selected (core only)
                $restrictions['modules'] = $restrictions['modules'] ?? [];
            }
            $data['restrictions'] = $restrictions;

            $plan = Plan::create($data);

            DB::commit();

            return Success::response([
                'message' => __('Plan created successfully'),
                'data' => $plan,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return Error::response(__('Failed to create plan'));
        }
    }

    /**
     * Update plan
     */
    public function update(Request $request, Plan $plan)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:plans,slug,'.$plan->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_period' => 'required|in:monthly,yearly,lifetime',
            'trial_days' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'restrictions' => 'array',
            'restrictions.max_users' => 'required|integer',
            'restrictions.max_employees' => 'required|integer',
            'restrictions.max_storage_gb' => 'required|numeric',
            'restrictions.modules' => 'array',
        ]);

        if ($validator->fails()) {
            return Error::response([
                'message' => __('Validation failed'),
                'errors' => $validator->errors(),
            ]);
        }

        try {
            DB::beginTransaction();

            $data = $validator->validated();

            // Set boolean values
            $data['is_active'] = $request->boolean('is_active', true);
            $data['is_featured'] = $request->boolean('is_featured', false);

            // Handle module access logic
            $restrictions = $data['restrictions'] ?? [];
            if ($request->boolean('allow_all_modules')) {
                // Unlimited plan - set modules to null for all modules access
                $restrictions['modules'] = null;
            } else {
                // Ensure empty array when no modules selected (core only)
                $restrictions['modules'] = $restrictions['modules'] ?? [];
            }
            $data['restrictions'] = $restrictions;

            $plan->update($data);

            DB::commit();

            return Success::response([
                'message' => __('Plan updated successfully'),
                'data' => $plan,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return Error::response(__('Failed to update plan'));
        }
    }

    /**
     * Delete plan
     */
    public function destroy(Plan $plan)
    {
        // Check if plan has active subscriptions
        if ($plan->activeSubscriptionsCount() > 0) {
            return Error::response(__('Cannot delete plan with active subscriptions'));
        }

        try {
            $plan->delete();

            return Success::response([
                'message' => __('Plan deleted successfully'),
            ]);
        } catch (\Exception $e) {
            return Error::response(__('Failed to delete plan'));
        }
    }

    /**
     * Get available modules
     */
    private function getAvailableModules()
    {
        $modulesStatusPath = base_path('modules_statuses.json');
        if (file_exists($modulesStatusPath)) {
            $modulesStatus = json_decode(file_get_contents($modulesStatusPath), true);
            $enabledModules = array_keys(array_filter($modulesStatus, function ($status) {
                return $status === true;
            }));

            // Separate core modules from other modules
            $coreModules = [];
            $categorizedModules = [];

            // Define module categories
            $categoryMap = [
                'Human Resources' => ['Payroll', 'LoanManagement', 'Recruitment', 'DisciplinaryActions', 'HRPolicies', 'BreakSystem', 'ShiftPlus'],
                'Attendance & Time Management' => ['QrAttendance', 'DynamicQrAttendance', 'FaceAttendance', 'IpAddressAttendance', 'GeofenceSystem', 'SiteAttendance'],
                'Field Operations' => ['FieldManager', 'TaskSystem', 'OfflineTracking', 'DigitalIdCard', 'FieldTask'],
                'Finance & Accounting' => ['Billing', 'MultiCurrency', 'PaymentCollection', 'SubscriptionManagement', 'Invoice', 'AccountingCore'],
                'Sales & CRM' => ['SalesTarget', 'ProductOrder'],
                'Communication & Collaboration' => ['AgoraCall', 'AiChat', 'CommunicationCenter', 'NoticeBoard', 'Notes', 'Calendar', 'ChatSystem'],
                'Document & Data Management' => ['DocumentManagement', 'DataImportExport', 'FormBuilder'],
                'System & Administration' => ['Approvals', 'AuditLog', 'GoogleReCAPTCHA', 'UidLogin', 'SOS', 'SystemBackup'],
                'Asset Management' => ['Assets'],
                'Learning & Development' => ['LMS'],
                'Employee Monitoring' => ['DesktopTracker'],
                'Productivity & Tools' => ['SearchPlus'],
            ];

            // Initialize categories
            foreach (array_keys($categoryMap) as $category) {
                $categorizedModules[$category] = [];
            }

            foreach ($enabledModules as $module) {
                // Check module.json for isCoreModule flag
                $moduleJsonPath = base_path("Modules/{$module}/module.json");
                $isCoreModule = false;

                if (file_exists($moduleJsonPath)) {
                    $moduleData = json_decode(file_get_contents($moduleJsonPath), true);
                    $isCoreModule = isset($moduleData['isCoreModule']) && $moduleData['isCoreModule'] === true;
                }

                if ($isCoreModule) {
                    $coreModules[] = $module;
                } else {
                    // Find module category
                    $moduleFound = false;

                    foreach ($categoryMap as $category => $moduleList) {
                        if (in_array($module, $moduleList)) {
                            $categorizedModules[$category][] = $module;
                            $moduleFound = true;
                            break;
                        }
                    }

                    // If module not found in any category, add to System & Administration
                    if (! $moduleFound) {
                        $categorizedModules['System & Administration'][] = $module;
                    }
                }
            }

            // Sort modules within each category
            sort($coreModules);
            foreach ($categorizedModules as &$modules) {
                sort($modules);
            }

            // Remove empty categories
            $categorizedModules = array_filter($categorizedModules, function ($modules) {
                return ! empty($modules);
            });

            return [
                'core' => $coreModules,
                'categorized' => $categorizedModules,
                'categories' => array_keys($categoryMap),
            ];
        }

        return ['core' => [], 'categorized' => [], 'categories' => []];
    }
}
