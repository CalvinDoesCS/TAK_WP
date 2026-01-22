<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MultiTenancyCore\App\Models\Payment;
use Modules\MultiTenancyCore\App\Models\Plan;
use Modules\MultiTenancyCore\App\Models\SaasSetting;
use Modules\MultiTenancyCore\App\Models\Tenant;
use Modules\MultiTenancyCore\App\Services\PlanSelectionService;

class PlanSelectionController extends Controller
{
    public function __construct(
        protected PlanSelectionService $planSelectionService
    ) {}

    /**
     * Show plan selection page
     */
    public function index()
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        if (! $tenant) {
            return redirect()->route('login')->with('error', __('Tenant record not found.'));
        }

        // If tenant already has active subscription, redirect to subscription page
        if ($tenant->hasActiveSubscription()) {
            return redirect()->route('multitenancycore.tenant.subscription')
                ->with('info', __('You already have an active subscription.'));
        }

        // Get all active plans ordered by price
        $plans = Plan::active()
            ->orderBy('price')
            ->get();

        // Check trial eligibility
        $isTrialEligible = $this->planSelectionService->isTrialEligible($tenant);
        $trialDays = SaasSetting::get('general_trial_days', 14);
        $trialEnabled = SaasSetting::get('general_enable_trial', true);

        // Get available payment methods
        $paymentMethods = $this->planSelectionService->getAvailablePaymentMethods();

        // Check if payment method is required for trial
        $requirePaymentForTrial = SaasSetting::get('general_require_payment_for_trial', false);

        // Check for pending payment
        $pendingPayment = Payment::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->with('newPlan')
            ->latest()
            ->first();

        return view('multitenancycore::tenant.plan-selection', compact(
            'tenant',
            'plans',
            'isTrialEligible',
            'trialDays',
            'trialEnabled',
            'paymentMethods',
            'pendingPayment',
            'requirePaymentForTrial'
        ));
    }

    /**
     * Handle plan selection
     */
    public function selectPlan(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'payment_method' => 'nullable|string',
        ]);

        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        if (! $tenant) {
            return redirect()->route('login')->with('error', __('Tenant record not found.'));
        }

        $plan = Plan::findOrFail($request->plan_id);

        // Call plan selection service
        $result = $this->planSelectionService->selectPlan(
            $tenant,
            $plan,
            $request->payment_method
        );

        // Handle failure
        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        // Check if subscription is active or trial (immediate success)
        if (isset($result['subscription'])) {
            $subscription = $result['subscription'];
            if (in_array($subscription->status, ['active', 'trial'])) {
                return redirect()->route('multitenancycore.tenant.subscription')
                    ->with('success', $result['message']);
            }
        }

        // Check if payment needs processing (redirect to payment gateway or instructions)
        if (isset($result['payment']) && $result['payment']) {
            $payment = $result['payment'];
            $paymentMethod = $request->payment_method ?? SaasSetting::get('default_payment_gateway', 'bank_transfer');

            // Handle based on payment method
            switch ($paymentMethod) {
                case 'stripe':
                case 'paypal':
                case 'razorpay':
                    // Show payment processing page which will redirect to gateway
                    return view('multitenancycore::tenant.payment.process', [
                        'payment' => $payment,
                        'paymentMethod' => $paymentMethod,
                    ]);

                case 'bank_transfer':
                    // Redirect to payment instructions
                    return redirect()->route('multitenancycore.tenant.payment.instructions', $payment->id);

                default:
                    // Fallback to payment instructions
                    return redirect()->route('multitenancycore.tenant.payment.instructions', $payment->id);
            }
        }

        // Handle explicit redirect
        if (isset($result['redirect_to']) && $result['redirect_to']) {
            return redirect($result['redirect_to']);
        }

        // Default fallback to subscription page
        return redirect()->route('multitenancycore.tenant.subscription')
            ->with('success', $result['message']);
    }

    /**
     * Show offline payment instructions
     */
    public function showPaymentInstructions(Payment $payment)
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        if (! $tenant) {
            return redirect()->route('login')->with('error', __('Tenant record not found.'));
        }

        // Verify payment belongs to current tenant
        if ($payment->tenant_id !== $tenant->id) {
            abort(403, __('You do not have permission to view this payment.'));
        }

        // Load plan details
        $plan = $payment->newPlan ?? ($payment->subscription ? $payment->subscription->plan : null);

        // Get offline payment settings
        $offlinePaymentSettings = SaasSetting::getOfflinePaymentSettings();

        return view('multitenancycore::tenant.payment.instructions', compact(
            'payment',
            'plan',
            'tenant',
            'offlinePaymentSettings'
        ));
    }
}
