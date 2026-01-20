<?php

namespace Modules\MultiTenancyCore\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'new_plan_id',
        'amount',
        'currency',
        'payment_method',
        'status',
        'reference_number',
        'invoice_number',
        'description',
        'proof_document_path',
        'proof_of_payment',
        'gateway_payment_id',
        'gateway_transaction_id',
        'paid_at',
        'approved_by_id',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'gateway_response',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'gateway_response' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = ['formatted_amount'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->reference_number)) {
                $payment->reference_number = 'PAY-'.strtoupper(Str::random(10));
            }
        });
    }

    /**
     * Get the tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the subscription
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the user who approved this payment
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by_id');
    }

    /**
     * Get the new plan (for plan changes)
     */
    public function newPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'new_plan_id');
    }

    /**
     * Check if payment is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is approved
     *
     * @deprecated Use isSuccessful() instead for checking both 'approved' and 'completed' statuses
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if payment is successful (approved or completed)
     */
    public function isSuccessful(): bool
    {
        return in_array($this->status, ['approved', 'completed']);
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if payment is processing (redirected to gateway)
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if payment was rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if payment was cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if payment requires action (pending offline payment)
     */
    public function requiresAction(): bool
    {
        return $this->isPending() && $this->isOffline();
    }

    /**
     * Check if payment is offline
     */
    public function isOffline()
    {
        return in_array($this->payment_method, ['offline', 'bank_transfer']);
    }

    /**
     * Approve the payment
     *
     * @deprecated Use PaymentProcessingService::processPaymentSuccess() instead
     */
    public function approve($userId, $activateSubscription = true)
    {
        $this->status = 'approved';
        $this->approved_by_id = $userId;
        $this->approved_at = now();

        // Generate invoice number if not present
        if (! $this->invoice_number) {
            $invoiceService = app(\Modules\MultiTenancyCore\App\Services\InvoiceService::class);
            $this->invoice_number = $invoiceService->generateInvoiceNumber($this);
        }

        $this->save();

        // Activate the subscription if needed
        if ($activateSubscription && $this->subscription) {
            $this->subscription->status = 'active';
            $this->subscription->save();
        }
    }

    /**
     * Reject the payment
     *
     * @deprecated Use PaymentProcessingService::rejectPayment() instead
     */
    public function reject($userId, $reason = null)
    {
        $this->status = 'rejected';
        $this->approved_by_id = $userId;
        $this->approved_at = now();
        $this->rejection_reason = $reason;
        $this->save();
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute()
    {
        $symbol = $this->getCurrencySymbol();

        return $symbol.number_format($this->amount, 2);
    }

    /**
     * Get currency symbol
     */
    protected function getCurrencySymbol()
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹',
            'AED' => 'AED',
        ];

        return $symbols[$this->currency] ?? $this->currency;
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for offline payments
     */
    public function scopeOffline($query)
    {
        return $query->whereIn('payment_method', ['offline', 'bank_transfer']);
    }

    /**
     * Scope for successful payments (approved or completed)
     */
    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', ['approved', 'completed']);
    }

    /**
     * Get the proof document URL
     */
    public function getProofDocumentUrlAttribute()
    {
        if (! $this->proof_document_path) {
            return null;
        }

        return asset('storage/'.$this->proof_document_path);
    }
}
