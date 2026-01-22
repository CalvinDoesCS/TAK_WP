<?php

namespace Modules\MultiTenancyCore\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\MultiTenancyCore\App\Helpers\SaasCurrencyHelper;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'cancellation_reason',
        'cancel_at_period_end',
        'payment_method',
        'amount',
        'currency',
        'metadata',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    protected $appends = ['formatted_amount'];

    /**
     * Get formatted amount with currency symbol from SaaS settings
     */
    public function getFormattedAmountAttribute(): string
    {
        return SaasCurrencyHelper::format((float) $this->amount);
    }

    /**
     * Get the tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the plan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get payments for this subscription
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive()
    {
        return in_array($this->status, ['trial', 'active']) &&
               ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /**
     * Check if on trial
     */
    public function onTrial()
    {
        return $this->status === 'trial' && $this->ends_at->isFuture();
    }

    /**
     * Check if subscription has ended
     */
    public function hasEnded()
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    /**
     * Check if subscription is past due
     */
    public function pastDue()
    {
        return $this->status === 'past_due';
    }

    /**
     * Cancel the subscription
     */
    public function cancel($immediately = false)
    {
        $this->cancelled_at = now();

        if ($immediately) {
            $this->status = 'cancelled';
            $this->ends_at = now();
        } else {
            // Cancel at period end
            $this->status = 'active';
        }

        $this->save();
    }

    /**
     * Resume a cancelled subscription
     */
    public function resume()
    {
        if ($this->cancelled_at && $this->ends_at->isFuture()) {
            $this->cancelled_at = null;
            $this->save();
        }
    }

    /**
     * Renew the subscription
     */
    public function renew()
    {
        $period = $this->plan->billing_period;

        $newEndsAt = $this->ends_at ?? now();

        switch ($period) {
            case 'monthly':
                $newEndsAt = $newEndsAt->addMonth();
                break;
            case 'yearly':
                $newEndsAt = $newEndsAt->addYear();
                break;
            case 'lifetime':
                $newEndsAt = null;
                break;
        }

        $this->ends_at = $newEndsAt;
        $this->status = 'active';
        $this->save();
    }

    /**
     * Get days until expiration
     */
    public function daysUntilExpiration()
    {
        if (! $this->ends_at) {
            return null; // Lifetime subscription
        }

        return $this->ends_at->diffInDays(now(), false);
    }

    /**
     * Scope for active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['trial', 'active'])
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    /**
     * Scope for expiring soon (within 7 days)
     */
    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->whereNotNull('ends_at')
            ->where('ends_at', '<=', now()->addDays($days))
            ->where('ends_at', '>', now());
    }

    /**
     * Check if subscription is expiring soon
     */
    public function isExpiringSoon($days = 7)
    {
        if (! $this->ends_at) {
            return false;
        }

        return $this->ends_at->isAfter(now()) &&
               $this->ends_at->isBefore(now()->addDays($days));
    }

    /**
     * Get grace period end date
     */
    public function getGracePeriodEndAttribute()
    {
        if (! $this->ends_at) {
            return null;
        }

        return $this->ends_at->addDays(config('multitenancy.grace_period_days', 3));
    }

    /**
     * Get the next payment date (when subscription renews)
     * For trial subscriptions, this is when trial ends
     * For active subscriptions, this is the ends_at date
     */
    public function getNextPaymentDateAttribute(): ?\Carbon\Carbon
    {
        // No next payment for cancelled or inactive subscriptions
        if (in_array($this->status, ['cancelled', 'inactive', 'pending'])) {
            return null;
        }

        // For trial, next payment is when trial ends
        if ($this->status === 'trial' && $this->trial_ends_at) {
            return $this->trial_ends_at;
        }

        // For lifetime plans, no next payment
        if ($this->plan && $this->plan->billing_period === 'lifetime') {
            return null;
        }

        // For free plans, no next payment
        if ($this->plan && $this->plan->isFree()) {
            return null;
        }

        // For active subscriptions, next payment is at ends_at
        return $this->ends_at;
    }
}
