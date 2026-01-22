<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\MultiTenancyCore\App\Models\Payment;
use Modules\MultiTenancyCore\App\Models\Plan;
use Modules\MultiTenancyCore\App\Models\Subscription;
use Modules\MultiTenancyCore\App\Models\Tenant;

class SubscriptionController extends Controller
{
    /**
     * Display subscription management page
     */
    public function index()
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        if (! $tenant) {
            return redirect()->route('login')->with('error', 'Tenant record not found.');
        }

        // Get current subscription
        $currentSubscription = $tenant->activeSubscription;

        // Get all available plans
        $plans = Plan::active()
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        // Get subscription history
        $subscriptionHistory = $tenant->subscriptions()
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Get recent payments
        $recentPayments = Payment::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('multitenancycore::tenant.subscription.index', compact(
            'tenant',
            'currentSubscription',
            'plans',
            'subscriptionHistory',
            'recentPayments'
        ));
    }

    /**
     * Show plan selection/confirmation page (GET route)
     */
    public function selectPlan(Plan $plan)
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        if (! $tenant) {
            return redirect()->route('login')->with('error', __('Tenant record not found.'));
        }

        $currentSubscription = $tenant->activeSubscription;

        // Check if already on this plan
        if ($currentSubscription && $currentSubscription->plan_id == $plan->id) {
            return redirect()->route('multitenancycore.tenant.subscription')
                ->with('error', __('You are already subscribed to this plan.'));
        }

        // If no current subscription - new subscription flow
        if (! $currentSubscription) {
            // Free plan - handle immediately
            if ($plan->isFree()) {
                try {
                    DB::beginTransaction();

                    $subscription = new Subscription;
                    $subscription->tenant_id = $tenant->id;
                    $subscription->plan_id = $plan->id;
                    $subscription->status = 'active';
                    $subscription->starts_at = now();
                    $subscription->save();

                    DB::commit();

                    return redirect()->route('multitenancycore.tenant.subscription')
                        ->with('success', __('You have successfully subscribed to the :plan plan.', ['plan' => $plan->name]));
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Failed to create free subscription: '.$e->getMessage());

                    return redirect()->back()->with('error', __('Failed to create subscription. Please try again.'));
                }
            }

            // Paid plan - show new subscription form with trial info
            $newPlan = $plan;
            $trialDays = 0;
            if ($plan->trial_days > 0 && ! $tenant->has_used_trial) {
                $trialDays = $plan->trial_days;
            }

            return view('multitenancycore::tenant.subscription.new-subscription', compact(
                'tenant',
                'newPlan',
                'trialDays'
            ));
        }

        // Has current subscription - show change plan form
        $newPlan = $plan;

        return view('multitenancycore::tenant.subscription.change-plan', compact(
            'tenant',
            'currentSubscription',
            'newPlan'
        ));
    }

    /**
     * Show plan change confirmation (POST route - legacy)
     */
    public function changePlan(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();
        $newPlan = Plan::findOrFail($request->plan_id);
        $currentSubscription = $tenant->activeSubscription;

        // If no current subscription, this is a new subscription
        if (! $currentSubscription) {
            // Check if this is a free plan
            if ($newPlan->isFree()) {
                // Create free subscription immediately
                try {
                    DB::beginTransaction();

                    $subscription = new Subscription;
                    $subscription->tenant_id = $tenant->id;
                    $subscription->plan_id = $newPlan->id;
                    $subscription->status = 'active';
                    $subscription->starts_at = now();
                    $subscription->save();

                    DB::commit();

                    return redirect()->route('multitenancycore.tenant.subscription')
                        ->with('success', 'You have successfully subscribed to the '.$newPlan->name.' plan.');

                } catch (\Exception $e) {
                    DB::rollBack();

                    return redirect()->back()->with('error', 'Failed to create subscription. Please try again.');
                }
            }

            // For paid plans, show the subscription form
            return view('multitenancycore::tenant.subscription.new-subscription', compact(
                'tenant',
                'newPlan'
            ));
        }

        // If there's a current subscription and it's the same plan
        if ($currentSubscription->plan_id == $newPlan->id) {
            return redirect()->back()->with('error', 'You are already on this plan.');
        }

        // Otherwise show the change plan form
        return view('multitenancycore::tenant.subscription.change-plan', compact(
            'tenant',
            'currentSubscription',
            'newPlan'
        ));
    }

    /**
     * Process plan change
     */
    public function processChangePlan(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'payment_method' => 'nullable|in:bank_transfer,online,stripe,paypal,razorpay',
        ]);

        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();
        $newPlan = Plan::findOrFail($request->plan_id);
        $currentSubscription = $tenant->activeSubscription;

        try {
            DB::beginTransaction();

            // Handle new subscription
            if (! $currentSubscription) {
                // Create new subscription
                $subscription = new Subscription;
                $subscription->tenant_id = $tenant->id;
                $subscription->plan_id = $newPlan->id;

                // If plan has trial period and tenant hasn't used trial yet
                if ($newPlan->trial_days > 0 && ! $tenant->has_used_trial) {
                    $subscription->status = 'trial';
                    $subscription->trial_ends_at = now()->addDays($newPlan->trial_days);
                    $subscription->starts_at = now();
                    $subscription->save();

                    // Mark that tenant has used trial
                    $tenant->has_used_trial = true;
                    $tenant->save();

                    DB::commit();

                    return redirect()->route('multitenancycore.tenant.subscription')
                        ->with('success', "Your {$newPlan->trial_days}-day free trial has started! You'll be charged after the trial ends.");
                } else {
                    // No trial, need payment
                    $subscription->status = 'pending';
                    $subscription->starts_at = now();
                    $subscription->save();

                    $amount = $newPlan->price;
                    $description = "Subscription to {$newPlan->name} plan";
                }

                $currentSubscription = $subscription;
            } else {
                // Calculate amount based on plan change type
                $description = "Change to {$newPlan->name} plan";

                if ($currentSubscription->plan->price < $newPlan->price) {
                    // Upgrading - payment required
                    $amount = $newPlan->price;

                    if ($currentSubscription->ends_at) {
                        // Calculate prorated amount
                        $daysRemaining = now()->diffInDays($currentSubscription->ends_at);
                        $dailyRate = $currentSubscription->plan->price / 30;
                        $credit = $dailyRate * $daysRemaining;
                        $amount = $newPlan->price - $credit;
                        $description .= ' (Prorated)';
                    }
                } else {
                    // Downgrading - no payment required
                    $amount = 0;
                    $description .= ' (Downgrade)';
                }
            }

            // For upgrades/new subscriptions that require payment, validate payment_method
            if ($amount > 0 && ! $request->payment_method) {
                DB::rollBack();

                return redirect()->back()
                    ->with('error', __('Please select a payment method.'))
                    ->withInput();
            }

            // Create payment record
            $payment = new Payment;
            $payment->tenant_id = $tenant->id;
            $payment->subscription_id = $currentSubscription->id;
            $payment->new_plan_id = $newPlan->id; // Store the plan being purchased
            $payment->amount = max($amount, 0); // Ensure non-negative
            $payment->currency = $newPlan->currency ?? 'USD';
            // For downgrades (amount <= 0), payment_method can be null - use 'bank_transfer' as placeholder
            $payment->payment_method = $request->payment_method ?? 'bank_transfer';
            $payment->description = $description;
            $payment->status = 'pending';
            $payment->save();

            // If immediate downgrade or free plan
            if ($amount <= 0) {
                // Update subscription immediately
                $currentSubscription->plan_id = $newPlan->id;
                $currentSubscription->save();

                $payment->status = 'completed';
                $payment->save();

                DB::commit();

                return redirect()->route('multitenancycore.tenant.subscription')
                    ->with('success', 'Your plan has been changed successfully.');
            }

            DB::commit();

            // Redirect to payment processing
            switch ($request->payment_method) {
                case 'stripe':
                case 'paypal':
                case 'razorpay':
                    // Show payment processing page which will redirect to gateway
                    return view('multitenancycore::tenant.payment.process', [
                        'payment' => $payment,
                        'paymentMethod' => $request->payment_method,
                    ]);

                case 'bank_transfer':
                    // Bank transfer instructions
                    return redirect()->route('multitenancycore.tenant.payment.instructions', $payment->id);

                default:
                    return redirect()->route('multitenancycore.tenant.subscription')
                        ->with('error', __('Invalid payment method selected.'));
            }

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to process plan change. Please try again.');
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        if (! $tenant) {
            return redirect()->back()->with('error', 'Tenant not found.');
        }

        $subscription = $tenant->activeSubscription;

        if (! $subscription) {
            return redirect()->back()->with('error', 'No active subscription found.');
        }

        try {
            // Set subscription to cancel at period end
            $subscription->cancelled_at = now();
            $subscription->cancellation_reason = $request->reason;
            $subscription->cancel_at_period_end = true;
            $subscription->save();

            $message = 'Your subscription has been scheduled for cancellation.';
            if ($subscription->ends_at) {
                $message .= ' You can continue using the service until '.$subscription->ends_at->format('M d, Y').'.';
            }

            return redirect()->route('multitenancycore.tenant.subscription')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to cancel subscription: '.$e->getMessage());

            return redirect()->back()->with('error', 'Failed to cancel subscription. Please try again.');
        }
    }

    /**
     * Resume cancelled subscription
     */
    public function resume(Request $request)
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();
        $subscription = $tenant->activeSubscription;

        if (! $subscription || ! $subscription->cancel_at_period_end) {
            return redirect()->back()->with('error', 'No cancelled subscription found.');
        }

        try {
            // Remove cancellation
            $subscription->cancelled_at = null;
            $subscription->cancellation_reason = null;
            $subscription->cancel_at_period_end = false;
            $subscription->save();

            return redirect()->route('multitenancycore.tenant.subscription')
                ->with('success', 'Your subscription has been resumed successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to resume subscription. Please try again.');
        }
    }

    /**
     * Download invoice
     */
    public function downloadInvoice($paymentId)
    {
        // Redirect to the invoice download route
        return redirect()->route('multitenancycore.tenant.invoices.download', $paymentId);
    }
}
