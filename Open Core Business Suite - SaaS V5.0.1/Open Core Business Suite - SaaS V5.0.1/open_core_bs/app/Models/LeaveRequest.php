<?php

namespace App\Models;

use App\Enums\LeaveRequestStatus;
use App\Traits\UserActionsTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class LeaveRequest extends Model implements AuditableContract
{
    use Auditable, SoftDeletes, UserActionsTrait;

    protected $table = 'leave_requests';

    protected $fillable = [
        'from_date',
        'to_date',
        'user_id',
        'leave_type_id',
        'is_half_day',
        'half_day_type',
        'total_days',
        'use_comp_off',
        'comp_off_days_used',
        'comp_off_ids',
        'document',
        'user_notes',
        'emergency_contact',
        'emergency_phone',
        'is_abroad',
        'abroad_location',
        'approved_by_id',
        'rejected_by_id',
        'approved_at',
        'rejected_at',
        'status',
        'approval_notes',
        'notes',
        'created_by_id',
        'updated_by_id',
        'cancel_reason',
        'cancelled_at',
        'cancelled_by_id',
    ];

    protected $casts = [
        'status' => LeaveRequestStatus::class,
        'from_date' => 'date:d-m-Y',
        'to_date' => 'date:d-m-Y',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_half_day' => 'boolean',
        'is_abroad' => 'boolean',
        'use_comp_off' => 'boolean',
        'total_days' => 'decimal:2',
        'comp_off_days_used' => 'decimal:2',
        'comp_off_ids' => 'array',
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Calculate total days before saving
        static::saving(function ($leaveRequest) {
            $leaveRequest->calculateTotalDays();
        });
    }

    /**
     * Calculate total days based on dates and half-day settings
     */
    public function calculateTotalDays()
    {
        if ($this->is_half_day) {
            $this->total_days = 0.5;
            // For half day, from_date and to_date should be same
            $this->to_date = $this->from_date;
        } else {
            $fromDate = Carbon::parse($this->from_date);
            $toDate = Carbon::parse($this->to_date);

            // Calculate working days (excluding weekends)
            $totalDays = 0;
            $currentDate = $fromDate->copy();

            while ($currentDate->lte($toDate)) {
                // Check if it's not a weekend (customize based on your business rules)
                if (! $currentDate->isWeekend()) {
                    $totalDays++;
                }
                $currentDate->addDay();
            }

            $this->total_days = $totalDays;
        }
    }

    /**
     * Get the half day display text
     */
    public function getHalfDayDisplayAttribute()
    {
        if (! $this->is_half_day) {
            return null;
        }

        return $this->half_day_type === 'first_half' ? __('First Half') : __('Second Half');
    }

    /**
     * Get the date range display
     */
    public function getDateRangeDisplayAttribute()
    {
        if ($this->is_half_day) {
            return $this->from_date->format('d M Y').' ('.$this->half_day_display.')';
        }

        if ($this->from_date->eq($this->to_date)) {
            return $this->from_date->format('d M Y');
        }

        return $this->from_date->format('d M Y').' - '.$this->to_date->format('d M Y');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    /**
     * Submit the leave request for approval.
     *
     * @return $this
     */
    public function submitForApproval()
    {
        $this->status = LeaveRequestStatus::PENDING;
        $this->save();

        // Create an approval request
        $this->requestApproval('leave', auth()->user());

        return $this;
    }

    /**
     * Process the approval based on the multilevel approval status.
     *
     * @return bool
     */
    public function processApprovalResult()
    {
        if ($this->isApproved()) {
            $this->status = LeaveRequestStatus::APPROVED;
            $this->approved_by_id = auth()->id();
            $this->approved_at = now();
            $this->save();

            // Update user's leave balance
            $this->updateUserLeaveBalance('approve');

            // Mark comp offs as used if this leave request uses comp off
            $this->markCompOffsAsUsed();

            return true;
        } elseif ($this->isRejected()) {
            $this->status = LeaveRequestStatus::REJECTED;
            $this->rejected_by_id = auth()->id();
            $this->rejected_at = now();
            $this->save();

            // Release comp offs if this leave request was using comp off
            $this->releaseCompOffs();

            return true;
        }

        return false;
    }

    /**
     * Check if the leave request can be processed.
     *
     * @return bool
     */
    public function canBeProcessed()
    {
        return $this->isApproved() || $this->isRejected();
    }

    /**
     * Cancel the leave request
     */
    public function cancel($reason, $byAdmin = false)
    {
        // Store original status before changing
        $wasApproved = $this->status === LeaveRequestStatus::APPROVED;

        $this->status = $byAdmin ? LeaveRequestStatus::CANCELLED_BY_ADMIN : LeaveRequestStatus::CANCELLED;
        $this->cancel_reason = $reason;
        $this->cancelled_at = now();
        $this->cancelled_by_id = auth()->id();
        $this->save();

        // If the leave was approved, restore the leave balance
        if ($wasApproved) {
            $this->updateUserLeaveBalance('cancel');
        }

        // Release comp offs if this leave request was using comp off
        $this->releaseCompOffs();
    }

    /**
     * Check if leave can be cancelled
     */
    public function canBeCancelled()
    {
        return in_array($this->status, [LeaveRequestStatus::PENDING, LeaveRequestStatus::APPROVED])
          && $this->from_date->isFuture();
    }

    /**
     * Update user's leave balance when leave status changes
     *
     * @param  string  $action  'approve' or 'cancel'
     * @return void
     */
    public function updateUserLeaveBalance(string $action)
    {
        // Skip if leave type doesn't exist
        if (! $this->leave_type_id) {
            return;
        }

        // Calculate days that affect regular leave balance (excluding comp off days)
        // This prevents incorrect balance updates when leave uses compensatory off
        $regularLeaveDays = $this->total_days - ($this->comp_off_days_used ?? 0);

        // If all days were covered by comp off, no regular balance adjustment needed
        if ($regularLeaveDays <= 0) {
            return;
        }

        $currentYear = Carbon::parse($this->from_date)->year;

        // Get or create user's leave balance record
        $leaveBalance = UserAvailableLeave::firstOrCreate(
            [
                'user_id' => $this->user_id,
                'leave_type_id' => $this->leave_type_id,
                'year' => $currentYear,
            ],
            [
                'entitled_leaves' => $this->leaveType->default_days ?? 0,
                'carried_forward_leaves' => 0,
                'additional_leaves' => 0,
                'used_leaves' => 0,
                'available_leaves' => $this->leaveType->default_days ?? 0,
                'created_by_id' => auth()->id() ?? null,
            ]
        );

        if ($action === 'approve') {
            // Increment used leaves, decrement available leaves (only for regular leave days)
            $leaveBalance->used_leaves += $regularLeaveDays;
            $leaveBalance->available_leaves = max(0, $leaveBalance->available_leaves - $regularLeaveDays);
        } elseif ($action === 'cancel') {
            // Decrement used leaves, increment available leaves (only for regular leave days)
            $leaveBalance->used_leaves = max(0, $leaveBalance->used_leaves - $regularLeaveDays);
            $leaveBalance->available_leaves += $regularLeaveDays;
        }

        $leaveBalance->updated_by_id = auth()->id() ?? null;
        $leaveBalance->save();
    }

    /**
     * Check if leave overlaps with existing leaves
     */
    public function hasOverlappingLeave()
    {
        return self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->whereIn('status', [LeaveRequestStatus::PENDING, LeaveRequestStatus::APPROVED])
            ->where(function ($query) {
                $query->whereBetween('from_date', [$this->from_date, $this->to_date])
                    ->orWhereBetween('to_date', [$this->from_date, $this->to_date])
                    ->orWhere(function ($q) {
                        $q->where('from_date', '<=', $this->from_date)
                            ->where('to_date', '>=', $this->to_date);
                    });
            })
            ->exists();
    }

    /**
     * Get the leave document file
     */
    public function getLeaveDocumentFile()
    {
        // Fallback to legacy document field
        if ($this->document) {
            return Storage::disk('public')->path($this->document);
        }

        return null;
    }

    /**
     * Get the leave document URL
     */
    public function getLeaveDocumentUrl()
    {

        // Fallback to legacy document field
        if ($this->document) {
            return Storage::disk('public')->url($this->document);
        }

        return null;
    }

    /**
     * Get compensatory offs used for this leave
     */
    public function compensatoryOffs()
    {
        if (! $this->use_comp_off || empty($this->comp_off_ids)) {
            return collect();
        }

        // Ensure comp_off_ids is an array (defensive programming)
        $compOffIds = is_array($this->comp_off_ids) ? $this->comp_off_ids : json_decode($this->comp_off_ids, true);

        if (empty($compOffIds) || ! is_array($compOffIds)) {
            return collect();
        }

        return CompensatoryOff::whereIn('id', $compOffIds)->get();
    }

    /**
     * Mark compensatory offs as used when leave is approved
     */
    public function markCompOffsAsUsed()
    {
        if (! $this->use_comp_off || empty($this->comp_off_ids)) {
            return;
        }

        // Ensure comp_off_ids is an array (defensive programming)
        $compOffIds = is_array($this->comp_off_ids) ? $this->comp_off_ids : json_decode($this->comp_off_ids, true);

        if (empty($compOffIds) || ! is_array($compOffIds)) {
            return;
        }

        foreach ($compOffIds as $compOffId) {
            $compOff = CompensatoryOff::find($compOffId);
            if ($compOff && $compOff->canBeUsed()) {
                $compOff->markAsUsed($this->id);
            }
        }
    }

    /**
     * Release compensatory offs when leave is cancelled or rejected
     */
    public function releaseCompOffs()
    {
        if (! $this->use_comp_off || empty($this->comp_off_ids)) {
            return;
        }

        // Ensure comp_off_ids is an array (defensive programming)
        $compOffIds = is_array($this->comp_off_ids) ? $this->comp_off_ids : json_decode($this->comp_off_ids, true);

        if (empty($compOffIds) || ! is_array($compOffIds)) {
            return;
        }

        // Release comp offs by their IDs
        // We don't need to check leave_request_id as we already have the exact comp off IDs
        // and we're setting leave_request_id to null anyway
        CompensatoryOff::whereIn('id', $compOffIds)
            ->update([
                'is_used' => false,
                'used_date' => null,
                'leave_request_id' => null,
            ]);
    }
}
