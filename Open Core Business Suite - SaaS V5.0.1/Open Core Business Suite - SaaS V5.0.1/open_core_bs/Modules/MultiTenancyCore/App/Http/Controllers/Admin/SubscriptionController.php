<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\ApiClasses\Success;
use App\ApiClasses\Error;
use Illuminate\Http\Request;
use Modules\MultiTenancyCore\App\Models\Subscription;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Models\Plan;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    /**
     * Display subscription management page
     */
    public function index()
    {
        // Ensure user has proper role
        if (!auth()->user() || !auth()->user()->hasAnyRole(['super_admin', 'admin'])) {
            abort(403, 'Unauthorized access');
        }
        
        return view('multitenancycore::admin.subscriptions.index');
    }

    /**
     * Get subscriptions data for DataTables
     */
    public function indexAjax(Request $request)
    {
        $query = Subscription::with(['tenant', 'plan']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        if ($request->has('expiring_soon') && $request->expiring_soon == '1') {
            $query->expiringSoon();
        }

        return DataTables::of($query)
            ->addColumn('tenant_info', function ($subscription) {
                return view('multitenancycore::admin.subscriptions._tenant-info', compact('subscription'))->render();
            })
            ->addColumn('plan_info', function ($subscription) {
                return view('multitenancycore::admin.subscriptions._plan-info', compact('subscription'))->render();
            })
            ->addColumn('status_display', function ($subscription) {
                return view('multitenancycore::admin.subscriptions._status', compact('subscription'))->render();
            })
            ->addColumn('period', function ($subscription) {
                return view('multitenancycore::admin.subscriptions._period', compact('subscription'))->render();
            })
            ->addColumn('actions', function ($subscription) {
                return view('components.datatable-actions', [
                    'id' => $subscription->id,
                    'actions' => $this->getActions($subscription)
                ])->render();
            })
            ->rawColumns(['tenant_info', 'plan_info', 'status_display', 'period', 'actions'])
            ->make(true);
    }

    /**
     * Show subscription details
     */
    public function show(Request $request, Subscription $subscription)
    {
        $subscription->load(['tenant', 'plan', 'payments']);

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($subscription);
        }

        return view('multitenancycore::admin.subscriptions.show', compact('subscription'));
    }

    /**
     * Create new subscription
     */
    public function create()
    {
        $tenants = Tenant::where('status', 'active')
            ->doesntHave('activeSubscription')
            ->get();
        
        $plans = Plan::active()->get();
        
        // Get trial days from SaaS settings
        $trialDays = \Modules\MultiTenancyCore\App\Models\SaasSetting::get('general_trial_days', 14);
        
        return view('multitenancycore::admin.subscriptions.create', compact('tenants', 'plans', 'trialDays'));
    }

    /**
     * Store new subscription
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|exists:tenants,id',
            'plan_id' => 'required|exists:plans,id',
            'status' => 'required|in:trial,active',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after:starts_at'
        ]);

        if ($validator->fails()) {
            return Error::response([
                'message' => __('Validation failed'),
                'errors' => $validator->errors()
            ]);
        }

        try {
            DB::beginTransaction();

            $tenant = Tenant::findOrFail($request->tenant_id);
            $plan = Plan::findOrFail($request->plan_id);

            // Check if tenant already has active subscription
            if ($tenant->hasActiveSubscription()) {
                return Error::response(__('Tenant already has an active subscription'));
            }

            $subscription = new Subscription([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => $request->status,
                'starts_at' => $request->starts_at,
                'ends_at' => $request->ends_at,
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'payment_method' => 'manual'
            ]);
            $subscription->save();

            DB::commit();

            return Success::response([
                'message' => __('Subscription created successfully'),
                'data' => $subscription
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return Error::response(__('Failed to create subscription'));
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request, Subscription $subscription)
    {
        if (!$subscription->isActive()) {
            return Error::response(__('Only active subscriptions can be cancelled'));
        }

        $validator = Validator::make($request->all(), [
            'immediately' => 'boolean',
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return Error::response([
                'message' => __('Validation failed'),
                'errors' => $validator->errors()
            ]);
        }

        try {
            $immediately = $request->boolean('immediately', false);
            
            $subscription->cancel($immediately);

            if ($request->has('reason')) {
                $metadata = $subscription->metadata ?? [];
                $metadata['cancellation_reason'] = $request->reason;
                $metadata['cancelled_by_id'] = auth()->id();
                $subscription->update(['metadata' => $metadata]);
            }

            return Success::response([
                'message' => $immediately 
                    ? __('Subscription cancelled immediately')
                    : __('Subscription will be cancelled at the end of the billing period')
            ]);
        } catch (\Exception $e) {
            return Error::response(__('Failed to cancel subscription'));
        }
    }

    /**
     * Renew subscription
     */
    public function renew(Request $request, Subscription $subscription)
    {
        if ($subscription->status === 'cancelled') {
            return Error::response(__('Cannot renew cancelled subscription'));
        }

        try {
            $subscription->renew();

            return Success::response([
                'message' => __('Subscription renewed successfully')
            ]);
        } catch (\Exception $e) {
            return Error::response(__('Failed to renew subscription'));
        }
    }

    /**
     * Change subscription plan
     */
    public function changePlan(Request $request, Subscription $subscription)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
            'immediate' => 'boolean'
        ]);

        if ($validator->fails()) {
            return Error::response([
                'message' => __('Validation failed'),
                'errors' => $validator->errors()
            ]);
        }

        if (!$subscription->isActive()) {
            return Error::response(__('Only active subscriptions can change plans'));
        }

        try {
            DB::beginTransaction();

            $newPlan = Plan::findOrFail($request->plan_id);
            $immediate = $request->boolean('immediate', false);

            if ($immediate) {
                // Change plan immediately - keep existing currency as plans don't have currency field
                $subscription->update([
                    'plan_id' => $newPlan->id,
                    'amount' => $newPlan->price,
                ]);
            } else {
                // Schedule plan change at end of billing period
                $metadata = $subscription->metadata ?? [];
                $metadata['scheduled_plan_change'] = [
                    'plan_id' => $newPlan->id,
                    'scheduled_for' => $subscription->ends_at
                ];
                $subscription->update(['metadata' => $metadata]);
            }

            DB::commit();

            return Success::response([
                'message' => $immediate 
                    ? __('Plan changed successfully')
                    : __('Plan change scheduled for end of billing period')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to change subscription plan', [
                'subscription_id' => $subscription->id,
                'plan_id' => $request->plan_id,
                'error' => $e->getMessage(),
            ]);

            return Error::response(__('Failed to change plan'));
        }
    }

    /**
     * Get actions for subscription
     */
    private function getActions($subscription)
    {
        $actions = [
            [
                'label' => __('View'),
                'icon' => 'bx bx-show',
                'url' => route('multitenancycore.admin.subscriptions.show', $subscription->id)
            ]
        ];

        if ($subscription->isActive()) {
            $actions[] = [
                'label' => __('Renew'),
                'icon' => 'bx bx-refresh',
                'onclick' => "renewSubscription({$subscription->id})",
                'class' => 'text-success'
            ];
            
            $actions[] = [
                'label' => __('Change Plan'),
                'icon' => 'bx bx-transfer',
                'onclick' => "changePlan({$subscription->id})"
            ];
            
            $actions[] = [
                'label' => __('Cancel'),
                'icon' => 'bx bx-x-circle',
                'onclick' => "cancelSubscription({$subscription->id})",
                'class' => 'text-danger'
            ];
        }

        return $actions;
    }

    /**
     * Get expiring subscriptions
     */
    public function getExpiring()
    {
        $subscriptions = Subscription::with(['tenant', 'plan'])
            ->expiringSoon()
            ->get();

        return response()->json([
            'count' => $subscriptions->count(),
            'subscriptions' => $subscriptions
        ]);
    }
}