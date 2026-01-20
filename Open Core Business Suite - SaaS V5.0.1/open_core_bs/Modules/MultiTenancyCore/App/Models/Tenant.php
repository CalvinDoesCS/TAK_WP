<?php

namespace Modules\MultiTenancyCore\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'subdomain',
        'custom_domain',
        'status',
        'approved_at',
        'approved_by_id',
        'database_provisioning_status',
        'trial_ends_at',
        'has_used_trial',
        'metadata',
        'notes',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'website',
        'tax_id',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'has_used_trial' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tenant) {
            if (empty($tenant->uuid)) {
                $tenant->uuid = (string) Str::uuid();
            }

            // Generate subdomain from name if not provided
            if (empty($tenant->subdomain)) {
                $tenant->subdomain = Str::slug($tenant->name);

                // Ensure subdomain is unique
                $originalSubdomain = $tenant->subdomain;
                $counter = 1;
                while (static::where('subdomain', $tenant->subdomain)->exists()) {
                    $tenant->subdomain = $originalSubdomain.'-'.$counter;
                    $counter++;
                }
            }
        });
    }

    /**
     * Get the current active subscription
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['trial', 'active'])
            ->latest();
    }

    /**
     * Get all subscriptions
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the current subscription (relationship method)
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['trial', 'active'])
            ->latest();
    }

    /**
     * Get the database connection info
     */
    public function database(): HasOne
    {
        return $this->hasOne(TenantDatabase::class);
    }

    /**
     * Get all payments
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the user who approved this tenant
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by_id');
    }

    /**
     * Check if tenant is on trial
     */
    public function onTrial()
    {
        if (! $this->trial_ends_at) {
            return false;
        }

        return $this->trial_ends_at->isFuture();
    }

    /**
     * Check if tenant has active subscription
     */
    public function hasActiveSubscription()
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * Check if tenant is active
     */
    public function isActive()
    {
        return $this->status === 'active' && $this->hasActiveSubscription();
    }

    /**
     * Scope for active tenants
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for pending approval
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get the database name for this tenant
     */
    public function getDatabaseName()
    {
        return 'tenant_'.$this->id;
    }

    /**
     * Get the full subdomain URL
     */
    public function getSubdomainUrl()
    {
        $appUrl = config('app.url');
        $parsedUrl = parse_url($appUrl);
        $scheme = $parsedUrl['scheme'] ?? 'https';
        $host = $parsedUrl['host'] ?? 'localhost';
        $port = isset($parsedUrl['port']) ? ':'.$parsedUrl['port'] : '';

        return $scheme.'://'.$this->subdomain.'.'.$host.$port;
    }

    /**
     * Get the current subscription (alias for activeSubscription)
     */
    public function getSubscriptionAttribute()
    {
        return $this->activeSubscription()->first();
    }

    /**
     * Get max employees allowed by the current plan
     */
    public function getMaxEmployees(): ?int
    {
        $subscription = $this->activeSubscription()->with('plan')->first();

        if (! $subscription || ! $subscription->plan) {
            return null;
        }

        return $subscription->plan->getMaxEmployees();
    }

    /**
     * Check if tenant can add another employee
     */
    public function canAddEmployee(): bool
    {
        $maxEmployees = $this->getMaxEmployees();

        // Null or -1 means unlimited
        if ($maxEmployees === null || $maxEmployees === -1) {
            return true;
        }

        // Count current employees (exclude super_admin, client, tenant roles)
        $currentCount = \App\Models\User::whereDoesntHave('roles', function ($query) {
            $query->whereIn('name', ['super_admin', 'client', 'tenant']);
        })->count();

        return $currentCount < $maxEmployees;
    }
}
