<?php

namespace Modules\MultiTenancyCore\App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\MultiTenancyCore\App\Models\Payment;
use Modules\MultiTenancyCore\App\Models\Plan;
use Modules\MultiTenancyCore\App\Models\SaasSetting;
use Modules\MultiTenancyCore\App\Models\Subscription;
use Modules\MultiTenancyCore\App\Models\Tenant;

class PlanSelectionService
{
    /**
     * Select plan for tenant - creates subscription and possibly payment
     *
     * This is the main entry point for plan selection. It determines the
     * appropriate flow based on plan price and trial eligibility.
     *
     * @param  Tenant  $tenant  The tenant selecting the plan
     * @param  Plan  $plan  The plan being selected
     * @param  string|null  $paymentMethod  Payment method (required for paid plans without trial)
     * @return array{success: bool, subscription: ?Subscription, payment: ?Payment, redirect_to: ?string, message: string}
     */
    public function selectPlan(Tenant $tenant, Plan $plan, ?string $paymentMethod = null): array
    {
        try {
            // Check if tenant already has an active subscription with this plan
            $existingSubscription = $tenant->activeSubscription;
            if ($existingSubscription && $existingSubscription->plan_id === $plan->id) {
                return [
                    'success' => false,
                    'subscription' => $existingSubscription,
                    'payment' => null,
                    'redirect_to' => null,
                    'message' => __('You are already subscribed to this plan.'),
                ];
            }

            // Route to appropriate handler based on plan type and trial eligibility
            if ($plan->isFree()) {
                return $this->handleFreePlan($tenant, $plan);
            }

            if ($this->isTrialEligible($tenant) && $this->isTrialEnabled()) {
                // Check if payment method is required for trial
                if ($this->isPaymentRequiredForTrial() && empty($paymentMethod)) {
                    return [
                        'success' => false,
                        'subscription' => null,
                        'payment' => null,
                        'redirect_to' => null,
                        'message' => __('Payment method is required to start your trial.'),
                    ];
                }

                return $this->handleTrialEligible($tenant, $plan, $paymentMethod);
            }

            // Paid plan without trial - payment method is required
            if (empty($paymentMethod)) {
                return [
                    'success' => false,
                    'subscription' => null,
                    'payment' => null,
                    'redirect_to' => null,
                    'message' => __('Payment method is required for this plan.'),
                ];
            }

            return $this->handlePaidPlan($tenant, $plan, $paymentMethod);

        } catch (\Exception $e) {
            Log::error('Plan selection failed for tenant '.$tenant->id.': '.$e->getMessage(), [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'payment_method' => $paymentMethod,
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'subscription' => null,
                'payment' => null,
                'redirect_to' => null,
                'message' => __('Failed to process plan selection. Please try again.'),
            ];
        }
    }

    /**
     * Handle free plan selection - immediate activation, no payment
     *
     * @param  Tenant  $tenant  The tenant selecting the plan
     * @param  Plan  $plan  The free plan being selected
     * @return array{success: bool, subscription: ?Subscription, payment: ?Payment, redirect_to: ?string, message: string}
     */
    protected function handleFreePlan(Tenant $tenant, Plan $plan): array
    {
        try {
            DB::beginTransaction();

            // Cancel any existing active subscription
            $this->cancelExistingSubscription($tenant);

            // Create new active subscription immediately
            $subscription = new Subscription;
            $subscription->tenant_id = $tenant->id;
            $subscription->plan_id = $plan->id;
            $subscription->status = 'active';
            $subscription->starts_at = now();
            $subscription->ends_at = $this->calculateEndDate($plan->id);
            $subscription->amount = 0;
            $subscription->currency = $this->getCurrency();
            $subscription->save();

            DB::commit();

            Log::info('Free plan subscription created', [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'subscription_id' => $subscription->id,
            ]);

            return [
                'success' => true,
                'subscription' => $subscription,
                'payment' => null,
                'redirect_to' => null,
                'message' => __('You have successfully subscribed to the :plan plan.', ['plan' => $plan->name]),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Handle trial-eligible plan - creates trial subscription, no payment yet
     *
     * @param  Tenant  $tenant  The tenant selecting the plan
     * @param  Plan  $plan  The plan being selected with trial
     * @param  string|null  $paymentMethod  Payment method if required for trial
     * @return array{success: bool, subscription: ?Subscription, payment: ?Payment, redirect_to: ?string, message: string}
     */
    protected function handleTrialEligible(Tenant $tenant, Plan $plan, ?string $paymentMethod = null): array
    {
        try {
            DB::beginTransaction();

            // Cancel any existing active subscription
            $this->cancelExistingSubscription($tenant);

            $trialDays = $this->getTrialDays();

            // Create trial subscription
            $subscription = new Subscription;
            $subscription->tenant_id = $tenant->id;
            $subscription->plan_id = $plan->id;
            $subscription->status = 'trial';
            $subscription->starts_at = now();
            $subscription->trial_ends_at = now()->addDays($trialDays);
            $subscription->ends_at = now()->addDays($trialDays); // Trial ends when subscription ends
            $subscription->amount = $plan->price;
            $subscription->currency = $this->getCurrency();
            $subscription->payment_method = $paymentMethod; // Store payment method if provided
            $subscription->save();

            // Mark tenant as having used trial
            $tenant->has_used_trial = true;
            $tenant->trial_ends_at = now()->addDays($trialDays);
            $tenant->save();

            DB::commit();

            Log::info('Trial subscription created', [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'subscription_id' => $subscription->id,
                'trial_days' => $trialDays,
            ]);

            return [
                'success' => true,
                'subscription' => $subscription,
                'payment' => null,
                'redirect_to' => null,
                'message' => __('Your :days-day free trial has started! You\'ll be charged after the trial ends.', ['days' => $trialDays]),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Handle paid plan (no trial) - creates pending subscription + payment
     *
     * @param  Tenant  $tenant  The tenant selecting the plan
     * @param  Plan  $plan  The paid plan being selected
     * @param  string  $paymentMethod  The payment method to use
     * @return array{success: bool, subscription: ?Subscription, payment: ?Payment, redirect_to: ?string, message: string}
     */
    protected function handlePaidPlan(Tenant $tenant, Plan $plan, string $paymentMethod): array
    {
        try {
            DB::beginTransaction();

            // Create pending subscription
            $subscription = new Subscription;
            $subscription->tenant_id = $tenant->id;
            $subscription->plan_id = $plan->id;
            $subscription->status = 'pending';
            $subscription->starts_at = now();
            $subscription->ends_at = $this->calculateEndDate($plan->id);
            $subscription->payment_method = $paymentMethod;
            $subscription->amount = $plan->price;
            $subscription->currency = $this->getCurrency();
            $subscription->save();

            // Create pending payment
            $payment = new Payment;
            $payment->tenant_id = $tenant->id;
            $payment->subscription_id = $subscription->id;
            $payment->new_plan_id = $plan->id;
            $payment->amount = $plan->price;
            $payment->currency = $this->getCurrency();
            $payment->payment_method = $paymentMethod;
            $payment->status = 'pending';
            $payment->description = __('Subscription to :plan plan', ['plan' => $plan->name]);
            $payment->save();

            DB::commit();

            Log::info('Pending subscription and payment created', [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'subscription_id' => $subscription->id,
                'payment_id' => $payment->id,
                'payment_method' => $paymentMethod,
            ]);

            // Determine redirect URL based on payment method
            $redirectTo = $this->getPaymentRedirectUrl($payment, $paymentMethod);

            return [
                'success' => true,
                'subscription' => $subscription,
                'payment' => $payment,
                'redirect_to' => $redirectTo,
                'message' => __('Please complete your payment to activate your subscription.'),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check if tenant is eligible for trial
     *
     * A tenant is eligible for trial if they have never used a trial before.
     *
     * @param  Tenant  $tenant  The tenant to check
     * @return bool True if tenant can use trial, false otherwise
     */
    public function isTrialEligible(Tenant $tenant): bool
    {
        return ! $tenant->has_used_trial;
    }

    /**
     * Get trial days from global SaaS settings
     *
     * @return int Number of trial days
     */
    protected function getTrialDays(): int
    {
        return (int) SaasSetting::get('general_trial_days', 14);
    }

    /**
     * Check if trial is enabled globally
     *
     * @return bool True if trial is enabled
     */
    protected function isTrialEnabled(): bool
    {
        return (bool) SaasSetting::get('general_enable_trial', true);
    }

    /**
     * Check if payment method is required for trial
     *
     * @return bool True if payment method is required for trial
     */
    protected function isPaymentRequiredForTrial(): bool
    {
        return (bool) SaasSetting::get('general_require_payment_for_trial', false);
    }

    /**
     * Get currency from SaaS settings
     *
     * @return string Currency code
     */
    protected function getCurrency(): string
    {
        return SaasSetting::get('general_currency', 'USD');
    }

    /**
     * Calculate subscription end date based on plan billing period
     *
     * @param  int  $planId  The plan ID
     * @return Carbon|null End date or null for lifetime plans
     */
    protected function calculateEndDate(int $planId): ?Carbon
    {
        $plan = Plan::find($planId);

        if (! $plan) {
            return now()->addMonth(); // Default to monthly if plan not found
        }

        return match ($plan->billing_period) {
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            'lifetime' => null, // Lifetime plans never expire
            default => now()->addMonth(),
        };
    }

    /**
     * Get payment redirect URL based on payment method
     *
     * For online gateways (stripe, paypal, razorpay), returns null to indicate
     * the controller should show the payment process view directly.
     * For bank_transfer, returns the redirect URL to payment instructions.
     *
     * @param  Payment  $payment  The payment record
     * @param  string  $paymentMethod  The payment method
     * @return string|null Redirect URL or null for gateway processing
     */
    protected function getPaymentRedirectUrl(Payment $payment, string $paymentMethod): ?string
    {
        return match ($paymentMethod) {
            // Online gateways need to show the process view, not redirect
            'stripe', 'paypal', 'razorpay' => null,
            // Bank transfer redirects to payment instructions
            'bank_transfer' => route('multitenancycore.tenant.payment.instructions', $payment->id),
            default => null,
        };
    }

    /**
     * Cancel existing active subscription for tenant
     *
     * When a tenant selects a new plan (not changing within existing subscription),
     * we may need to cancel their current subscription.
     *
     * @param  Tenant  $tenant  The tenant
     */
    protected function cancelExistingSubscription(Tenant $tenant): void
    {
        $existingSubscription = $tenant->activeSubscription;

        if ($existingSubscription) {
            $existingSubscription->status = 'cancelled';
            $existingSubscription->cancelled_at = now();
            $existingSubscription->cancellation_reason = __('Switched to a different plan');
            $existingSubscription->save();

            Log::info('Existing subscription cancelled for plan change', [
                'tenant_id' => $tenant->id,
                'subscription_id' => $existingSubscription->id,
            ]);
        }
    }

    /**
     * Get available payment methods from SaaS settings
     *
     * @return array<string> List of enabled payment methods
     */
    public function getAvailablePaymentMethods(): array
    {
        $methods = [];

        // Check which payment gateways are enabled
        if (SaasSetting::get('payment_gateway_stripe_enabled', false)) {
            $methods[] = 'stripe';
        }

        if (SaasSetting::get('payment_gateway_paypal_enabled', false)) {
            $methods[] = 'paypal';
        }

        if (SaasSetting::get('payment_gateway_razorpay_enabled', false)) {
            $methods[] = 'razorpay';
        }

        if (SaasSetting::get('payment_gateway_offline_enabled', true)) {
            $methods[] = 'bank_transfer';
        }

        return $methods;
    }

    /**
     * Check if a specific payment method is available
     *
     * @param  string  $method  The payment method to check
     * @return bool True if available
     */
    public function isPaymentMethodAvailable(string $method): bool
    {
        return in_array($method, $this->getAvailablePaymentMethods());
    }

    /**
     * Get plan selection summary for display
     *
     * @param  Tenant  $tenant  The tenant
     * @param  Plan  $plan  The plan being considered
     * @return array{requires_payment: bool, trial_eligible: bool, trial_days: int, amount: float, currency: string}
     */
    public function getPlanSelectionSummary(Tenant $tenant, Plan $plan): array
    {
        $trialEligible = $this->isTrialEligible($tenant) && $this->isTrialEnabled() && ! $plan->isFree();

        return [
            'requires_payment' => ! $plan->isFree() && ! $trialEligible,
            'trial_eligible' => $trialEligible,
            'trial_days' => $trialEligible ? $this->getTrialDays() : 0,
            'amount' => $plan->isFree() ? 0 : (float) $plan->price,
            'currency' => $this->getCurrency(),
        ];
    }
}
