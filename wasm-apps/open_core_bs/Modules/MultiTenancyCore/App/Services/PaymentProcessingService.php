<?php

namespace Modules\MultiTenancyCore\App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\MultiTenancyCore\App\Models\Payment;
use Modules\MultiTenancyCore\App\Models\Plan;
use Modules\MultiTenancyCore\App\Models\Subscription;
use Modules\MultiTenancyCore\App\Models\Tenant;

class PaymentProcessingService
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected SaasNotificationService $notificationService
    ) {}

    /**
     * Process successful payment - unified path for ALL payment types
     * Sets status to 'completed' (not 'approved')
     * Generates invoice number
     * Activates subscription
     *
     * @param  Payment  $payment  The payment to process
     * @param  string|null  $gatewayTransactionId  Transaction ID from payment gateway
     * @param  array<string, mixed>|null  $gatewayResponse  Full response from payment gateway
     * @param  int|null  $approvedById  User ID of admin who approved (for offline payments)
     * @return array{success: bool, message: string}
     */
    public function processPaymentSuccess(
        Payment $payment,
        ?string $gatewayTransactionId = null,
        ?array $gatewayResponse = null,
        ?int $approvedById = null
    ): array {
        // Skip if already completed
        if ($payment->status === 'completed') {
            Log::info('Payment already completed', ['payment_id' => $payment->id]);

            return [
                'success' => true,
                'message' => 'Payment already completed',
            ];
        }

        DB::beginTransaction();

        try {
            // Build update data
            $updateData = [
                'status' => 'completed',
                'paid_at' => now(),
            ];

            // Add gateway transaction ID if provided
            if ($gatewayTransactionId) {
                $updateData['gateway_transaction_id'] = $gatewayTransactionId;
            }

            // Add gateway response if provided
            if ($gatewayResponse) {
                $updateData['gateway_response'] = $gatewayResponse;
            }

            // For offline payments approved by admin
            if ($approvedById) {
                $updateData['approved_by_id'] = $approvedById;
                $updateData['approved_at'] = now();
            }

            // Generate invoice number
            if (! $payment->invoice_number) {
                $updateData['invoice_number'] = $this->invoiceService->generateInvoiceNumber($payment);
            }

            // Update payment
            $payment->update($updateData);

            // Activate subscription
            $this->activateSubscription($payment->subscription, $payment->new_plan_id);

            DB::commit();

            // Send payment received notification
            try {
                $this->notificationService->sendPaymentReceivedEmail($payment);
            } catch (\Exception $e) {
                // Log notification error but don't fail the payment process
                Log::warning('Failed to send payment notification', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Payment processed successfully', [
                'payment_id' => $payment->id,
                'subscription_id' => $payment->subscription_id,
                'invoice_number' => $payment->invoice_number,
                'amount' => $payment->amount,
            ]);

            return [
                'success' => true,
                'message' => 'Payment processed successfully',
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to process payment success', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process payment: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Process payment failure
     *
     * @param  Payment  $payment  The payment that failed
     * @param  string|null  $reason  Reason for failure
     * @param  array<string, mixed>|null  $gatewayResponse  Full response from payment gateway
     * @return array{success: bool, message: string}
     */
    public function processPaymentFailure(
        Payment $payment,
        ?string $reason = null,
        ?array $gatewayResponse = null
    ): array {
        try {
            $updateData = [
                'status' => 'failed',
            ];

            if ($reason) {
                $updateData['rejection_reason'] = $reason;
            }

            if ($gatewayResponse) {
                $updateData['gateway_response'] = $gatewayResponse;
            }

            $payment->update($updateData);

            Log::info('Payment marked as failed', [
                'payment_id' => $payment->id,
                'payment_method' => $payment->payment_method,
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'message' => 'Payment failure recorded',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to process payment failure', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to record payment failure: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Reject payment (admin action for offline payments)
     *
     * @param  Payment  $payment  The payment to reject
     * @param  int  $rejectedById  User ID of admin who rejected
     * @param  string  $reason  Reason for rejection
     * @return array{success: bool, message: string}
     */
    public function rejectPayment(
        Payment $payment,
        int $rejectedById,
        string $reason
    ): array {
        try {
            $payment->update([
                'status' => 'rejected',
                'approved_by_id' => $rejectedById,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            Log::info('Payment rejected', [
                'payment_id' => $payment->id,
                'rejected_by' => $rejectedById,
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'message' => 'Payment rejected successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to reject payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to reject payment: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Create a new payment record
     *
     * @param  Tenant  $tenant  The tenant making the payment
     * @param  Subscription  $subscription  The subscription being paid for
     * @param  float  $amount  Payment amount
     * @param  string  $paymentMethod  Payment method (razorpay, stripe, bank_transfer, offline)
     * @param  int|null  $newPlanId  New plan ID if changing plans
     * @param  string|null  $description  Payment description
     */
    public function createPayment(
        Tenant $tenant,
        Subscription $subscription,
        float $amount,
        string $paymentMethod,
        ?int $newPlanId = null,
        ?string $description = null
    ): Payment {
        $plan = $newPlanId ? Plan::find($newPlanId) : $subscription->plan;

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'new_plan_id' => $newPlanId,
            'amount' => $amount,
            'currency' => $subscription->currency ?? 'USD',
            'payment_method' => $paymentMethod,
            'status' => 'pending',
            'description' => $description ?? ($plan ? $plan->name.' subscription payment' : 'Subscription payment'),
        ]);

        Log::info('Payment created', [
            'payment_id' => $payment->id,
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
        ]);

        return $payment;
    }

    /**
     * Mark payment as processing (when redirecting to gateway)
     */
    public function markAsProcessing(Payment $payment): void
    {
        $payment->update([
            'status' => 'processing',
        ]);

        Log::info('Payment marked as processing', [
            'payment_id' => $payment->id,
            'payment_method' => $payment->payment_method,
        ]);
    }

    /**
     * Calculate subscription end date based on plan billing period
     */
    protected function calculateEndDate(int $planId): Carbon
    {
        $plan = Plan::find($planId);

        if (! $plan) {
            return now()->addDays(30);
        }

        return match ($plan->billing_period) {
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            'lifetime' => now()->addYears(100),
            default => now()->addDays(30),
        };
    }

    /**
     * Activate subscription after payment
     *
     * @param  Subscription|null  $subscription  The subscription to activate
     * @param  int|null  $newPlanId  New plan ID if changing plans
     */
    protected function activateSubscription(?Subscription $subscription, ?int $newPlanId = null): void
    {
        if (! $subscription) {
            Log::warning('No subscription to activate');

            return;
        }

        $updateData = [
            'status' => 'active',
            'starts_at' => now(),
        ];

        // If there's a new plan, update the plan and calculate new end date
        if ($newPlanId) {
            $updateData['plan_id'] = $newPlanId;
            $updateData['ends_at'] = $this->calculateEndDate($newPlanId);
        } else {
            // Calculate end date based on current plan
            $updateData['ends_at'] = $this->calculateEndDate($subscription->plan_id);
        }

        $subscription->update($updateData);

        Log::info('Subscription activated', [
            'subscription_id' => $subscription->id,
            'plan_id' => $newPlanId ?? $subscription->plan_id,
            'ends_at' => $updateData['ends_at'],
        ]);
    }

    /**
     * Refund a payment
     *
     * @param  Payment  $payment  The payment to refund
     * @param  float|null  $amount  Amount to refund (full refund if null)
     * @param  string|null  $reason  Reason for refund
     * @return array{success: bool, message: string}
     */
    public function refundPayment(
        Payment $payment,
        ?float $amount = null,
        ?string $reason = null
    ): array {
        if ($payment->status !== 'completed') {
            return [
                'success' => false,
                'message' => 'Only completed payments can be refunded',
            ];
        }

        try {
            $refundAmount = $amount ?? $payment->amount;

            $payment->update([
                'status' => 'refunded',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'refund_amount' => $refundAmount,
                    'refund_reason' => $reason,
                    'refunded_at' => now()->toIso8601String(),
                ]),
            ]);

            Log::info('Payment refunded', [
                'payment_id' => $payment->id,
                'refund_amount' => $refundAmount,
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'message' => 'Payment refunded successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to refund payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to refund payment: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get payment statistics for a tenant
     *
     * @return array{total_paid: float, total_pending: float, payment_count: int}
     */
    public function getTenantPaymentStats(Tenant $tenant): array
    {
        $payments = $tenant->payments();

        return [
            'total_paid' => (float) $payments->where('status', 'completed')->sum('amount'),
            'total_pending' => (float) $payments->where('status', 'pending')->sum('amount'),
            'payment_count' => $payments->count(),
        ];
    }
}
