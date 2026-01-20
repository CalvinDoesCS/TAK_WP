<?php

namespace Modules\MultiTenancyCore\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_period',
        'trial_days',
        'is_active',
        'is_featured',
        'sort_order',
        'restrictions',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'restrictions' => 'array',
    ];

    protected $appends = ['formatted_price'];

    /**
     * Get subscriptions for this plan
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get active subscriptions count
     */
    public function activeSubscriptionsCount()
    {
        return $this->subscriptions()
            ->whereIn('status', ['trial', 'active'])
            ->count();
    }

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured plans
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Get the price formatted with currency from SaaS settings
     */
    public function getFormattedPriceAttribute()
    {
        $symbol = SaasSetting::get('general_currency_symbol', '$');

        return $symbol.number_format($this->price, 2);
    }

    /**
     * Get billing period label
     */
    public function getBillingPeriodLabelAttribute()
    {
        $labels = [
            'monthly' => __('per month'),
            'yearly' => __('per year'),
            'lifetime' => __('one time'),
        ];

        return $labels[$this->billing_period] ?? $this->billing_period;
    }

    /**
     * Check if a module is allowed in this plan
     */
    public function hasModule($module): bool
    {
        $allowedModules = $this->restrictions['modules'] ?? null;

        // null means all modules allowed (unlimited plan)
        if ($allowedModules === null) {
            return true;
        }

        // empty array means no addon modules (core only)
        if (empty($allowedModules)) {
            return false;
        }

        return in_array($module, $allowedModules);
    }

    /**
     * Get allowed modules from restrictions
     * Returns null for unlimited access, empty array for core only, or array of specific modules
     */
    public function getAllowedModules(): ?array
    {
        return $this->restrictions['modules'] ?? null;
    }

    /**
     * Check if this plan has access to all modules (unlimited)
     */
    public function hasAllModulesAccess(): bool
    {
        return ($this->restrictions['modules'] ?? null) === null;
    }

    /**
     * Get all modules accessible with this plan (core modules + plan modules).
     * Useful for displaying what the tenant can access.
     */
    public function getAllAccessibleModules(): array
    {
        $coreModules = getCoreModules();
        $planModules = $this->getAllowedModules();

        // If unlimited access
        if ($planModules === null) {
            return array_unique(array_merge($coreModules, getAllEnabledModules()));
        }

        // Core + specific plan modules
        return array_unique(array_merge($coreModules, $planModules));
    }

    /**
     * Get max users from restrictions
     */
    public function getMaxUsers(): ?int
    {
        return $this->restrictions['max_users'] ?? null;
    }

    /**
     * Check if plan has unlimited users
     */
    public function hasUnlimitedUsers(): bool
    {
        $maxUsers = $this->getMaxUsers();

        return $maxUsers === null || $maxUsers === -1;
    }

    /**
     * Get max employees from restrictions
     */
    public function getMaxEmployees(): ?int
    {
        return $this->restrictions['max_employees'] ?? null;
    }

    /**
     * Get max storage in GB from restrictions
     */
    public function getMaxStorageGb(): ?int
    {
        return $this->restrictions['max_storage_gb'] ?? null;
    }

    /**
     * Check if this is a free plan
     */
    public function isFree()
    {
        return $this->price == 0;
    }

    /**
     * Get displayable features list based on restrictions
     */
    public function getDisplayFeaturesAttribute(): array
    {
        $displayFeatures = [];

        // Users limit
        $maxUsers = $this->getMaxUsers();
        if ($maxUsers !== null) {
            if ($maxUsers == -1) {
                $displayFeatures[] = __('Unlimited Users');
            } else {
                $displayFeatures[] = __('Up to :count users', ['count' => $maxUsers]);
            }
        }

        // Employees limit
        $maxEmployees = $this->getMaxEmployees();
        if ($maxEmployees !== null) {
            if ($maxEmployees == -1) {
                $displayFeatures[] = __('Unlimited Employees');
            } else {
                $displayFeatures[] = __('Up to :count employees', ['count' => $maxEmployees]);
            }
        }

        // Storage limit
        $maxStorage = $this->getMaxStorageGb();
        if ($maxStorage !== null) {
            if ($maxStorage == -1) {
                $displayFeatures[] = __('Unlimited Storage');
            } else {
                $displayFeatures[] = __(':size GB Storage', ['size' => $maxStorage]);
            }
        }

        // Modules access
        $modules = $this->getAllowedModules();
        if ($modules === null) {
            $displayFeatures[] = __('All Modules Included');
        } elseif (empty($modules)) {
            $displayFeatures[] = __('Core Modules Only');
        } else {
            $displayFeatures[] = __(':count Modules Included', ['count' => count($modules)]);
        }

        return $displayFeatures;
    }
}
