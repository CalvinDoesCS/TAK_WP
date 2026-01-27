<?php

namespace App\Models;

use App\Config\Constants;
use App\Enums\SubscriptionStatus;
use App\Enums\UserAccountStatus;
use App\Traits\LogsLifecycleEvents;
use App\Traits\UserActionsTrait;
use App\Traits\UserTenantOptionsTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\MultiTenancyCore\App\Models\Plan;
use Modules\MultiTenancyCore\App\Models\Subscription;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements AuditableContract, JWTSubject
{
    use Auditable, HasApiTokens, HasFactory, HasRoles, LogsLifecycleEvents, Notifiable, SoftDeletes, UserActionsTrait, UserTenantOptionsTrait;

    /**
     * Get the database connection for the model.
     *
     * In SaaS mode, always use the current default connection (which is 'tenant' after switching).
     * This ensures queries use the correct tenant database even if the model was cached.
     */
    public function getConnectionName(): ?string
    {
        // In SaaS mode, always use the current default connection
        if (function_exists('isSaaSMode') && isSaaSMode()) {
            return config('database.default');
        }

        return parent::getConnectionName();
    }

    /**
     * Boot the model and register event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically sync the name column before saving
        static::saving(function ($user) {
            $user->syncNameColumn();
        });
    }

    /**
     * Sync the name column with first_name and last_name.
     */
    public function syncNameColumn(): void
    {
        $this->name = trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'name',
        'phone',
        'status',
        'dob',
        'gender',
        'blood_group',
        'profile_picture',
        'alternate_number',
        'cover_picture',
        'email',
        'email_verified_at',
        'phone_verified_at',
        'password',
        'remember_token',
        'language',
        'delete_request_at',
        'designation_id',
        'shift_id',
        'delete_request_reason',
        'team_id',
        'reporting_to_id',
        'code',
        'anniversary_date',
        'address',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'emergency_contact_address',
        'relieved_at',
        'relieved_reason',
        'retired_at',
        'retired_reason',
        'suspension_date',
        'suspension_reason',
        'suspension_duration_days',
        'exit_date',
        'exit_reason',
        'termination_type',
        'last_working_day',
        'is_eligible_for_rehire',
        'notice_period_days',
        'probation_period_months',
        'probation_end_date',
        'probation_confirmed_at',
        'is_probation_extended',
        'probation_remarks',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getUserForProfile()
    {
        return [
            'name' => $this->getFullName(),
            'code' => $this->code,
            'initials' => $this->getInitials(),
            'profile_picture' => $this->getProfilePicture(),
        ];
    }

    /**
     * Check if the user is currently under probation.
     */
    public function isUnderProbation(): bool
    {
        return $this->status === UserAccountStatus::ACTIVE && // Must be active
          ! is_null($this->probation_end_date) && // Must have an end date
          is_null($this->probation_confirmed_at) && // Must not be confirmed yet
          Carbon::parse($this->probation_end_date)->isFuture(); // End date must be in the future
    }

    /**
     * Get a display string for the user's probation status.
     */
    public function getProbationStatusDisplayAttribute(): string
    {
        if ($this->status !== UserAccountStatus::ACTIVE || is_null($this->probation_end_date)) {
            return 'Not Applicable';
        }
        if (! is_null($this->probation_confirmed_at)) {
            return 'Completed on '.Carbon::parse($this->probation_confirmed_at)->format('M d, Y');
        }
        if ($this->isUnderProbation()) {
            $statusText = 'Active until '.Carbon::parse($this->probation_end_date)->format('M d, Y');
            if ($this->is_probation_extended) {
                $statusText .= ' (Extended)';
            }

            return $statusText;
        }
        if (Carbon::parse($this->probation_end_date)->isPast()) {
            // Past due date but not confirmed - needs action
            return 'Pending Confirmation (Ended '.Carbon::parse($this->probation_end_date)->format('M d, Y').')';
        }

        return 'Unknown'; // Should not happen often
    }
    // --- End Probation Accessors ---

    /**
     * Scope to filter active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', UserAccountStatus::ACTIVE);
    }

    public function getFullName()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function getInitials(): string
    {
        return strtoupper(substr($this->first_name, 0, 1).substr($this->last_name, 0, 1));
    }

    public function getProfilePicture()
    {
        if (is_null($this->profile_picture)) {
            return null;
        } else {
            return asset('storage/'.Constants::BaseFolderEmployeeProfileWithSlash.$this->profile_picture);
        }
    }

    public function activePlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        $claims = [];

        // Include tenant subdomain in JWT when in SaaS mode
        // This allows authenticated API requests to identify the correct tenant
        // even if X-Tenant-ID header is not sent
        if (function_exists('isSaaSMode') && isSaaSMode()) {
            if (function_exists('current_tenant')) {
                $tenant = current_tenant();
                if ($tenant) {
                    $claims['tenant_subdomain'] = $tenant->subdomain;
                }
            }
        }

        return $claims;
    }

    /**
     * Specifies the user's FCM tokens
     *
     * @return string|array
     */
    public function fcmToken()
    {
        return $this->getDeviceToken();
    }

    public function getDeviceToken()
    {
        // Check if FieldManager module is available
        if (! class_exists('\\Modules\\FieldManager\\App\\Models\\UserDevice')) {
            return null;
        }

        $userDeviceClass = \Modules\FieldManager\App\Models\UserDevice::class;
        $userDevice = $userDeviceClass::where('user_id', $this->id)->first();

        return $userDevice?->token;
    }

    public function hasActivePlan(): bool
    {
        return $this->plan_id != null && $this->plan_expired_date >= now()->toDateString();
    }

    public function hasPendingOfflineRequest(): bool
    {
        // Offline requests are not available in single-tenant mode
        return false;
    }

    /**
     * Get the user's active subscription (relationship for eager loading).
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where('end_date', '>=', now()->toDateString())
            ->latest('start_date');
    }

    /**
     * Get the user's active subscription model directly.
     *
     * @deprecated Use activeSubscription relationship instead
     */
    public function getActiveSubscription(): ?Subscription
    {
        return $this->activeSubscription()->first();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getNameAttribute()
    {
        return $this->getFullNameAttribute();
    }

    public function resourceAllocations(): HasMany
    {
        if (class_exists('\\Modules\\PMCore\\App\\Models\\ResourceAllocation')) {
            return $this->hasMany(\Modules\PMCore\App\Models\ResourceAllocation::class, 'user_id');
        }

        return $this->hasMany(\App\Models\User::class, 'id')->whereRaw('1=0'); // Empty relation
    }

    /**
     * Get user's timesheets
     */
    public function timesheets(): HasMany
    {
        if (class_exists('\\Modules\\PMCore\\App\\Models\\Timesheet')) {
            return $this->hasMany(\Modules\PMCore\App\Models\Timesheet::class, 'user_id');
        }

        return $this->hasMany(\App\Models\User::class, 'id')->whereRaw('1=0'); // Empty relation
    }

    /**
     * Get user's payroll records
     */
    public function payrollRecords(): HasMany
    {
        if (class_exists('\\Modules\\Payroll\\App\\Models\\PayrollRecord')) {
            return $this->hasMany(\Modules\Payroll\App\Models\PayrollRecord::class, 'user_id');
        }

        return $this->hasMany(\App\Models\User::class, 'id')->whereRaw('1=0'); // Empty relation
    }

    /**
     * Get user's employee salary structures
     */
    public function employeeSalaryStructures(): HasMany
    {
        if (class_exists('\\Modules\\Payroll\\App\\Models\\EmployeeSalaryStructure')) {
            return $this->hasMany(\Modules\Payroll\App\Models\EmployeeSalaryStructure::class, 'user_id');
        }

        return $this->hasMany(\App\Models\User::class, 'id')->whereRaw('1=0'); // Empty relation
    }

    // Tenant Specific

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'phone_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'dob' => 'date',
            'date_of_joining' => 'date',
            'anniversary_date' => 'date',
            'probation_end_date' => 'date',
            'probation_confirmed_at' => 'datetime',
            'exit_date' => 'date',
            'last_working_day' => 'date',
            'suspension_date' => 'date',
            'relieved_at' => 'datetime',
            'retired_at' => 'datetime',
            'onboarding_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'status' => UserAccountStatus::class,
        ];
    }

    /**
     * Get leave balance for a specific leave type
     */
    public function getLeaveBalance($leaveTypeId)
    {
        $availableLeave = UserAvailableLeave::where('user_id', $this->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', date('Y'))
            ->first();

        if ($availableLeave) {
            return $availableLeave->available_leaves;
        }

        // Alternative: Calculate from accruals if using accrual system
        return LeaveAccrual::getCurrentBalance($this->id, $leaveTypeId);
    }

    /**
     * Get all leave balances
     */
    public function getLeaveBalances()
    {
        return UserAvailableLeave::where('user_id', $this->id)
            ->with('leaveType')
            ->get();
    }

    /**
     * Get user's available leaves
     */
    public function userAvailableLeaves(): HasMany
    {
        return $this->hasMany(UserAvailableLeave::class, 'user_id');
    }

    /**
     * Get leave requests
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'user_id');
    }

    /**
     * Get user's lifecycle events (timeline)
     */
    public function lifecycleEvents(): HasMany
    {
        return $this->hasMany(EmployeeLifecycleEvent::class, 'user_id');
    }

    /**
     * Get user's bank account
     */
    public function bankAccount(): HasOne
    {
        return $this->hasOne(BankAccount::class, 'user_id');
    }

    /**
     * Get user's notification preferences
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class, 'user_id');
    }

    /**
     * Get user's first/primary notification preference (singular alias)
     */
    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class, 'user_id');
    }

    /**
     * Get notification preference for a specific type
     */
    public function getNotificationPreference(string $notificationType)
    {
        return $this->notificationPreferences()
            ->where('notification_type', $notificationType)
            ->first();

    }

    /**
     * Get the team that the user belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Get the designation that the user belongs to.
     */
    public function designation(): BelongsTo
    {
        if (class_exists('\\App\\Models\\Designation')) {
            return $this->belongsTo(\App\Models\Designation::class, 'designation_id');
        }

        return $this->belongsTo(\App\Models\User::class, 'id')->whereRaw('1=0'); // Empty relation
    }

    /**
     * Get the shift that the user belongs to.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    /**
     * Get the manager/supervisor this user reports to.
     */
    public function reportingTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporting_to_id');
    }

    /**
     * Alias for team relationship to match "department" naming convention.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Get employee_id attribute (alias for code).
     */
    public function getEmployeeIdAttribute()
    {
        return $this->code;
    }

    /**
     * Get the expense requests approved by this user.
     */
    public function approvedExpenses(): HasMany
    {
        return $this->hasMany(ExpenseRequest::class, 'approved_by_id');
    }

    /**
     * Get the lifecycle timeline for this employee.
     *
     * @return array Timeline events sorted chronologically (newest first)
     */
    public function getLifecycleTimeline(): array
    {
        return app(\App\Services\EmployeeLifecycleService::class)->getTimeline($this);
    }

    /**
     * Get user's device (FieldManager module)
     */
    public function userDevice(): HasOne
    {
        if (class_exists('\\Modules\\FieldManager\\App\\Models\\UserDevice')) {
            return $this->hasOne(\Modules\FieldManager\App\Models\UserDevice::class, 'user_id');
        }

        return $this->hasOne(\App\Models\User::class, 'id')->whereRaw('1=0'); // Empty relation
    }
}
