<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\ApiClasses\Success;
use App\ApiClasses\Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\MultiTenancyCore\App\Models\Plan;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Models\Subscription;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class TenantController extends Controller
{
    /**
     * Display tenant management page
     */
    public function index()
    {
        return view('multitenancycore::admin.tenants.index');
    }

    /**
     * Get tenants data for DataTables
     */
    public function indexAjax(Request $request)
    {
        $query = Tenant::with(['activeSubscription.plan', 'database']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('plan_id')) {
            $query->whereHas('activeSubscription', function ($q) use ($request) {
                $q->where('plan_id', $request->plan_id);
            });
        }

        return DataTables::of($query)
            ->addColumn('company_info', function ($tenant) {
                return view('multitenancycore::admin.tenants._company-info', compact('tenant'))->render();
            })
            ->addColumn('plan', function ($tenant) {
                $subscription = $tenant->activeSubscription;
                if ($subscription) {
                    return view('multitenancycore::admin.tenants._plan-info', compact('subscription'))->render();
                }
                return '<span class="badge bg-label-secondary">' . __('No Plan') . '</span>';
            })
            ->addColumn('status_display', function ($tenant) {
                return view('multitenancycore::admin.tenants._status', compact('tenant'))->render();
            })
            ->addColumn('database_status', function ($tenant) {
                return view('multitenancycore::admin.tenants._database-status', compact('tenant'))->render();
            })
            ->addColumn('created_at_formatted', function ($tenant) {
                return $tenant->created_at->format('Y-m-d H:i');
            })
            ->addColumn('actions', function ($tenant) {
                return view('components.datatable-actions', [
                    'id' => $tenant->id,
                    'actions' => $this->getActions($tenant)
                ])->render();
            })
            ->rawColumns(['company_info', 'plan', 'status_display', 'database_status', 'actions'])
            ->make(true);
    }

    /**
     * Show tenant approval queue
     */
    public function approvalQueue()
    {
        return view('multitenancycore::admin.tenants.approval-queue');
    }

    /**
     * Get pending tenants for approval queue
     */
    public function approvalQueueAjax(Request $request)
    {
        $query = Tenant::where('status', 'pending')
            ->with('database')
            ->latest();

        return DataTables::of($query)
            ->addColumn('company_info', function ($tenant) {
                return view('multitenancycore::admin.tenants._company-info', compact('tenant'))->render();
            })
            ->addColumn('requested_plan', function ($tenant) {
                // Get the plan from metadata or first subscription
                $planId = $tenant->metadata['requested_plan_id'] ?? null;
                if ($planId) {
                    $plan = Plan::find($planId);
                    return $plan ? $plan->name : '-';
                }
                return '-';
            })
            ->addColumn('submitted_at', function ($tenant) {
                return $tenant->created_at->format('Y-m-d H:i');
            })
            ->addColumn('actions', function ($tenant) {
                return view('multitenancycore::admin.tenants._approval-actions', compact('tenant'))->render();
            })
            ->rawColumns(['company_info', 'actions'])
            ->make(true);
    }

    /**
     * Show tenant details
     */
    public function show(Tenant $tenant)
    {
        $tenant->load(['subscriptions.plan', 'payments', 'database']);
        return view('multitenancycore::admin.tenants.show', compact('tenant'));
    }

    /**
     * Show edit form
     */
    public function edit(Tenant $tenant)
    {
        return response()->json($tenant);
    }

    /**
     * Update tenant
     */
    public function update(Request $request, Tenant $tenant)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tenants,email,' . $tenant->id,
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:pending,approved,active,suspended,cancelled',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return Error::response([
                'message' => __('Validation failed'),
                'errors' => $validator->errors()
            ]);
        }

        try {
            $tenant->update($validator->validated());

            return Success::response([
                'message' => __('Tenant updated successfully')
            ]);
        } catch (\Exception $e) {
            return Error::response(__('Failed to update tenant'));
        }
    }

    /**
     * Approve a pending tenant.
     *
     * This method only activates the tenant. Subscription creation is handled separately
     * when the tenant selects a plan via the plan selection page.
     */
    public function approve(Request $request, Tenant $tenant)
    {
        if ($tenant->status !== 'pending') {
            return Error::response(__('Only pending tenants can be approved'));
        }

        try {
            // Update tenant status to active
            $tenant->update([
                'status' => 'active',
                'approved_at' => now(),
                'approved_by_id' => auth()->id()
            ]);

            // Send welcome email notifying the tenant they can now select a plan
            try {
                app(\Modules\MultiTenancyCore\App\Services\SaasNotificationService::class)
                    ->sendWelcomeEmail($tenant);
            } catch (\Exception $e) {
                Log::error('Failed to send welcome email: ' . $e->getMessage());
            }

            return Success::response([
                'message' => __('Tenant approved successfully. They can now select a subscription plan.'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to approve tenant '.$tenant->id.': '.$e->getMessage());

            return Error::response(__('Failed to approve tenant'));
        }
    }

    /**
     * Reject tenant
     */
    public function reject(Request $request, Tenant $tenant)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return Error::response([
                'message' => __('Validation failed'),
                'errors' => $validator->errors()
            ]);
        }

        try {
            $metadata = $tenant->metadata ?? [];
            $metadata['rejection_reason'] = $request->reason;
            $metadata['rejected_at'] = now();
            $metadata['rejected_by_id'] = auth()->id();

            $tenant->update([
                'status' => 'cancelled',
                'metadata' => $metadata
            ]);

            return Success::response([
                'message' => __('Tenant rejected successfully')
            ]);
        } catch (\Exception $e) {
            return Error::response(__('Failed to reject tenant'));
        }
    }

    /**
     * Suspend tenant
     */
    public function suspend(Request $request, Tenant $tenant)
    {
        if ($tenant->status === 'suspended') {
            return Error::response(__('Tenant is already suspended'));
        }

        try {
            $tenant->update(['status' => 'suspended']);

            return Success::response([
                'message' => __('Tenant suspended successfully')
            ]);
        } catch (\Exception $e) {
            return Error::response(__('Failed to suspend tenant'));
        }
    }

    /**
     * Activate tenant
     */
    public function activate(Request $request, Tenant $tenant)
    {
        if ($tenant->status === 'active') {
            return Error::response(__('Tenant is already active'));
        }

        // Check if tenant has active subscription
        if (!$tenant->hasActiveSubscription()) {
            return Error::response(__('Tenant must have an active subscription to be activated'));
        }

        // Check if database is provisioned
        if (!$tenant->database || !$tenant->database->isProvisioned()) {
            return Error::response(__('Tenant database must be provisioned before activation'));
        }

        try {
            $tenant->update(['status' => 'active']);

            return Success::response([
                'message' => __('Tenant activated successfully')
            ]);
        } catch (\Exception $e) {
            return Error::response(__('Failed to activate tenant'));
        }
    }

    /**
     * Get actions for tenant
     */
    private function getActions($tenant)
    {
        $actions = [
            [
                'label' => __('View'),
                'icon' => 'bx bx-show',
                'url' => route('multitenancycore.admin.tenants.show', $tenant->id)
            ],
            [
                'label' => __('Edit'),
                'icon' => 'bx bx-edit',
                'onclick' => "editTenant({$tenant->id})"
            ]
        ];

        // Add status-specific actions
        switch ($tenant->status) {
            case 'pending':
                $actions[] = [
                    'label' => __('Approve'),
                    'icon' => 'bx bx-check',
                    'onclick' => "approveTenant({$tenant->id})",
                    'class' => 'text-success'
                ];
                $actions[] = [
                    'label' => __('Reject'),
                    'icon' => 'bx bx-x',
                    'onclick' => "rejectTenant({$tenant->id})",
                    'class' => 'text-danger'
                ];
                break;

            case 'approved':
            case 'active':
                $actions[] = [
                    'label' => __('Suspend'),
                    'icon' => 'bx bx-pause',
                    'onclick' => "suspendTenant({$tenant->id})",
                    'class' => 'text-warning'
                ];
                break;

            case 'suspended':
                $actions[] = [
                    'label' => __('Activate'),
                    'icon' => 'bx bx-play',
                    'onclick' => "activateTenant({$tenant->id})",
                    'class' => 'text-success'
                ];
                break;
        }

        return $actions;
    }
}
