<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Modules\MultiTenancyCore\App\Models\SaasSetting;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Models\TenantDatabase;
use Modules\MultiTenancyCore\App\Services\TenantDatabaseService;
use Yajra\DataTables\Facades\DataTables;

class TenantProvisioningController extends Controller
{
    protected $databaseService;

    public function __construct(TenantDatabaseService $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    /**
     * Display provisioning requests
     */
    public function index()
    {
        return view('multitenancycore::admin.provisioning.index');
    }

    /**
     * Get provisioning requests for DataTable
     */
    public function getDataAjax(Request $request)
    {
        $query = Tenant::with(['subscription.plan'])
            ->whereIn('database_provisioning_status', ['pending', 'failed'])
            ->where('status', 'active');

        return DataTables::of($query)
            ->addColumn('tenant', function ($tenant) {
                return view('multitenancycore::admin.provisioning.partials.tenant-info', compact('tenant'))->render();
            })
            ->addColumn('plan', function ($tenant) {
                $subscription = $tenant->subscription;

                return $subscription ? $subscription->plan->name : '-';
            })
            ->addColumn('status', function ($tenant) {
                return view('multitenancycore::admin.provisioning.partials.status', compact('tenant'))->render();
            })
            ->addColumn('created_at', function ($tenant) {
                return $tenant->created_at->format('M d, Y H:i');
            })
            ->addColumn('actions', function ($tenant) {
                return view('multitenancycore::admin.provisioning.partials.actions', compact('tenant'))->render();
            })
            ->rawColumns(['tenant', 'status', 'actions'])
            ->make(true);
    }

    /**
     * Get provisioning history for DataTable
     */
    public function getHistoryAjax(Request $request)
    {
        $query = Tenant::with(['subscription.plan', 'database'])
            ->where('database_provisioning_status', 'provisioned');

        return DataTables::of($query)
            ->addColumn('tenant', function ($tenant) {
                return view('multitenancycore::admin.provisioning.partials.tenant-info', compact('tenant'))->render();
            })
            ->addColumn('plan', function ($tenant) {
                $subscription = $tenant->subscription;

                return $subscription ? $subscription->plan->name : '-';
            })
            ->addColumn('status', function ($tenant) {
                return view('multitenancycore::admin.provisioning.partials.status', compact('tenant'))->render();
            })
            ->addColumn('provisioned_at', function ($tenant) {
                return $tenant->database?->provisioned_at?->format('M d, Y H:i') ?? '-';
            })
            ->addColumn('actions', function ($tenant) {
                return view('multitenancycore::admin.provisioning.partials.history-actions', compact('tenant'))->render();
            })
            ->rawColumns(['tenant', 'status', 'actions'])
            ->make(true);
    }

    /**
     * Show provisioning form
     */
    public function show(Tenant $tenant)
    {
        // Check if auto-provisioning is enabled
        $autoProvisioningEnabled = SaasSetting::get('general_tenant_auto_provisioning', false);

        return view('multitenancycore::admin.provisioning.show', [
            'tenant' => $tenant,
            'autoProvisioningEnabled' => $autoProvisioningEnabled,
        ]);
    }

    /**
     * Auto provision database (for VPS)
     */
    public function autoProvision(Tenant $tenant)
    {
        if (! SaasSetting::get('general_tenant_auto_provisioning', false)) {
            return response()->json([
                'status' => 'error',
                'message' => __('Auto provisioning is not enabled'),
            ], 400);
        }

        try {
            // Update status to provisioning (no transaction - DDL statements auto-commit in MySQL)
            $tenant->database_provisioning_status = 'provisioning';
            $tenant->save();

            // Create database using service
            $result = $this->databaseService->createDatabase($tenant);

            if ($result['success']) {
                // Run migrations and seeders
                // Note: This is outside transaction because migrations use DDL which auto-commits,
                // and the method switches database connections which would break transaction state
                $this->databaseService->migrateAndSeed($tenant);

                $tenant->database_provisioning_status = 'provisioned';
                $tenant->status = 'active';
                $tenant->save();

                return response()->json([
                    'status' => 'success',
                    'message' => __('Database provisioned successfully'),
                ]);
            } else {
                throw new \Exception($result['message']);
            }

        } catch (\Exception $e) {
            $tenant->database_provisioning_status = 'failed';
            $tenant->save();

            Log::error('Tenant provisioning failed: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => __('Provisioning failed: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manual provision database (for shared hosting)
     */
    public function manualProvision(Request $request, Tenant $tenant)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|string',
            'database_name' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            // Create or update tenant database record (no transaction wrapper)
            $tenantDatabase = TenantDatabase::updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'host' => $request->host,
                    'port' => $request->port,
                    'database_name' => $request->database_name,
                    'username' => $request->username,
                    'encrypted_password' => Crypt::encryptString($request->password),
                    'provisioning_status' => 'manual',
                    'provisioned_at' => now(),
                ]
            );

            // Test connection
            $testResult = $this->databaseService->testConnection($tenantDatabase);

            if (! $testResult['success']) {
                throw new \Exception('Database connection failed: '.$testResult['message']);
            }

            // Run migrations and seeders
            // Note: This is outside transaction because migrations use DDL which auto-commits,
            // and the method switches database connections which would break transaction state
            $this->databaseService->migrateAndSeed($tenant);

            $tenant->database_provisioning_status = 'provisioned';
            $tenant->status = 'active';
            $tenant->save();

            return response()->json([
                'status' => 'success',
                'message' => __('Database configured and migrated successfully'),
            ]);

        } catch (\Exception $e) {
            Log::error('Manual provisioning failed: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => __('Configuration failed: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get provisioning statistics
     */
    public function statistics()
    {
        $stats = [
            'pending' => Tenant::where('database_provisioning_status', 'pending')
                ->whereHas('subscription', function ($q) {
                    $q->whereIn('status', ['trial', 'active']);
                })->count(),
            'failed' => Tenant::where('database_provisioning_status', 'failed')->count(),
            'provisioned_today' => Tenant::where('database_provisioning_status', 'provisioned')
                ->whereDate('updated_at', today())->count(),
            'total_active' => Tenant::where('status', 'active')->count(),
        ];

        return response()->json($stats);
    }
}
