<?php

namespace App\Models;

use App\Services\AddonService\IAddonService;
use App\Traits\UserActionsTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    // TEST
    use HasFactory, SoftDeletes, UserActionsTrait;

    protected $table = 'attendances';

    protected $fillable = [
        'user_id',
        'date',
        'check_in_time',
        'check_out_time',
        'late_reason',
        'shift_id',
        'early_checkout_reason',
        'working_hours',
        'break_hours',
        'overtime_hours',
        'late_hours',
        'early_hours',
        'status',
        'site_id',
        'is_holiday',
        'is_weekend',
        'is_half_day',
        'notes',
        'approved_by_id',
        'approved_at',
        'created_by_id',
        'updated_by_id',
        'tenant_id',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'approved_at' => 'datetime',
        'working_hours' => 'decimal:2',
        'break_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'late_hours' => 'decimal:2',
        'early_hours' => 'decimal:2',
        'is_holiday' => 'boolean',
        'is_weekend' => 'boolean',
        'is_half_day' => 'boolean',
    ];

    // Status constants
    public const STATUS_CHECKED_IN = 'checked_in';

    public const STATUS_CHECKED_OUT = 'checked_out';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_LEAVE = 'leave';

    public const STATUS_HOLIDAY = 'holiday';

    public const STATUS_WEEKEND = 'weekend';

    public const STATUS_HALF_DAY = 'half_day';

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    /**
     * Get attendance breaks relationship.
     * Returns empty relation if BreakSystem module is not enabled.
     */
    public function breaks(): HasMany
    {
        if (! $this->isBreakSystemEnabled()) {
            // Return an empty relation that won't query the non-existent table
            return $this->hasMany(AttendanceLog::class)->whereRaw('1 = 0');
        }

        return $this->hasMany(\Modules\BreakSystem\App\Models\AttendanceBreak::class);
    }

    public function regularization(): HasOne
    {
        return $this->hasOne(AttendanceRegularization::class);
    }

    /**
     * Get activities relationship.
     * Returns empty relation if FieldManager module is not enabled.
     */
    public function activities(): HasMany
    {
        if (! $this->isFieldManagerEnabled()) {
            return $this->hasMany(AttendanceLog::class)->whereRaw('1 = 0');
        }

        return $this->hasMany(\Modules\FieldManager\App\Models\Activity::class);
    }

    /**
     * Get visits relationship.
     * Returns empty relation if FieldManager module is not enabled.
     */
    public function visits(): HasMany
    {
        if (! $this->isFieldManagerEnabled()) {
            return $this->hasMany(AttendanceLog::class)->whereRaw('1 = 0');
        }

        return $this->hasMany(\Modules\FieldManager\App\Models\Visit::class);
    }

    /**
     * Scopes
     */
    public function scopeForUser(Builder $query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDate(Builder $query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForDateRange(Builder $query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopePresent(Builder $query)
    {
        return $query->whereIn('status', [
            self::STATUS_CHECKED_IN,
            self::STATUS_CHECKED_OUT,
            self::STATUS_HALF_DAY,
        ]);
    }

    public function scopeAbsent(Builder $query)
    {
        return $query->where('status', self::STATUS_ABSENT);
    }

    public function scopeLate(Builder $query)
    {
        return $query->whereHas('shift', function ($q) {
            $q->whereRaw('TIME(check_in_time) > TIME(shifts.start_time)');
        });
    }

    public function scopeEarlyCheckout(Builder $query)
    {
        return $query->whereHas('shift', function ($q) {
            $q->whereRaw('TIME(check_out_time) < TIME(shifts.end_time)');
        });
    }

    /**
     * Accessors & Mutators
     */
    public function getIsCheckedInAttribute()
    {
        return $this->status === self::STATUS_CHECKED_IN;
    }

    public function getIsCheckedOutAttribute()
    {
        return $this->status === self::STATUS_CHECKED_OUT;
    }

    public function getIsPresentAttribute()
    {
        return in_array($this->status, [
            self::STATUS_CHECKED_IN,
            self::STATUS_CHECKED_OUT,
            self::STATUS_HALF_DAY,
        ]);
    }

    public function getIsAbsentAttribute()
    {
        return $this->status === self::STATUS_ABSENT;
    }

    public function getIsOnLeaveAttribute()
    {
        return $this->status === self::STATUS_LEAVE;
    }

    public function getFormattedWorkingHoursAttribute()
    {
        $hours = floor($this->working_hours);
        $minutes = round(($this->working_hours - $hours) * 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function getFormattedBreakHoursAttribute()
    {
        $hours = floor($this->break_hours);
        $minutes = round(($this->break_hours - $hours) * 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function getLateMinutesAttribute()
    {
        if (! $this->shift || ! $this->check_in_time) {
            return 0;
        }

        // Get the date string
        $dateStr = $this->date instanceof Carbon ? $this->date->format('Y-m-d') : (string) $this->date;
        $dateStr = trim(explode(' ', $dateStr)[0]);

        // Get shift start time (extract time part only if it includes date)
        $shiftStartTime = $this->shift->start_time;
        if (strpos($shiftStartTime, ' ') !== false) {
            $shiftStartTime = explode(' ', $shiftStartTime)[1];
        }

        $shiftStart = Carbon::parse($dateStr.' '.$shiftStartTime);
        $checkIn = $this->check_in_time;

        if ($checkIn->gt($shiftStart)) {
            return $checkIn->diffInMinutes($shiftStart);
        }

        return 0;
    }

    public function getEarlyCheckoutMinutesAttribute()
    {
        if (! $this->shift || ! $this->check_out_time) {
            return 0;
        }

        // Get the date string
        $dateStr = $this->date instanceof Carbon ? $this->date->format('Y-m-d') : (string) $this->date;
        $dateStr = trim(explode(' ', $dateStr)[0]);

        // Get shift end time (extract time part only if it includes date)
        $shiftEndTime = $this->shift->end_time;
        if (strpos($shiftEndTime, ' ') !== false) {
            $shiftEndTime = explode(' ', $shiftEndTime)[1];
        }

        $shiftEnd = Carbon::parse($dateStr.' '.$shiftEndTime);
        $checkOut = $this->check_out_time;

        if ($checkOut->lt($shiftEnd)) {
            return $shiftEnd->diffInMinutes($checkOut);
        }

        return 0;
    }

    /**
     * Helper Methods
     */
    public function isCheckedIn(): bool
    {
        return $this->status === self::STATUS_CHECKED_IN;
    }

    public function isCheckedOut(): bool
    {
        return $this->status === self::STATUS_CHECKED_OUT;
    }

    public function canCheckIn(): bool
    {
        return in_array($this->status, [self::STATUS_ABSENT, null]);
    }

    public function canCheckOut(): bool
    {
        return $this->status === self::STATUS_CHECKED_IN;
    }

    public function checkIn($time = null, $logData = [])
    {
        $this->check_in_time = $time ?: now();
        $this->status = self::STATUS_CHECKED_IN;

        // Calculate late hours if shift is defined
        if ($this->shift) {
            $lateMinutes = $this->getLateMinutesAttribute();
            $this->late_hours = round($lateMinutes / 60, 2);
        }

        $this->save();

        // Create attendance log
        $this->attendanceLogs()->create(array_merge([
            'user_id' => $this->user_id,
            'date' => $this->date,
            'time' => $this->check_in_time->format('H:i:s'),
            'logged_at' => $this->check_in_time,
            'type' => 'check_in',
            'shift_id' => $this->shift_id,
        ], $logData));

        return $this;
    }

    public function checkOut($time = null, $logData = [])
    {
        $this->check_out_time = $time ?: now();
        $this->status = self::STATUS_CHECKED_OUT;
        $this->calculateHours();
        $this->save();

        // Create attendance log
        $this->attendanceLogs()->create(array_merge([
            'user_id' => $this->user_id,
            'date' => $this->date,
            'time' => $this->check_out_time->format('H:i:s'),
            'logged_at' => $this->check_out_time,
            'type' => 'check_out',
            'shift_id' => $this->shift_id,
        ], $logData));

        return $this;
    }

    /**
     * Calculate all attendance metrics
     * Delegates to AttendanceCalculationService for consistent calculations
     * Note: This method does NOT save the attendance record
     */
    public function calculateHours()
    {
        $service = app(\App\Services\AttendanceCalculationService::class);
        $service->calculateAttendance($this, false); // false = don't save, just calculate
    }

    /**
     * Recalculate and save attendance metrics
     * Use this when you want to recalculate and persist to database
     */
    public function recalculateAndSave(): void
    {
        $service = app(\App\Services\AttendanceCalculationService::class);
        $service->calculateAttendance($this, true); // true = save to database
    }

    /**
     * Start a break for this attendance record.
     * Returns null if BreakSystem module is not enabled.
     *
     * @param  string  $type  Break type
     * @param  array  $logData  Additional log data
     * @return mixed The created break or null if module disabled
     */
    public function startBreak($type = 'other', $logData = [])
    {
        if (! $this->isBreakSystemEnabled()) {
            return null;
        }

        $break = $this->breaks()->create([
            'user_id' => $this->user_id,
            'date' => $this->date,
            'start_time' => now(),
            'break_type' => $type,
            'status' => 'ongoing',
        ]);

        // Create attendance log
        $this->attendanceLogs()->create(array_merge([
            'user_id' => $this->user_id,
            'date' => $this->date,
            'time' => now()->format('H:i:s'),
            'logged_at' => now(),
            'type' => 'break_start',
            'break_type' => $type,
            'shift_id' => $this->shift_id,
        ], $logData));

        return $break;
    }

    /**
     * End a break for this attendance record.
     * Returns null if BreakSystem module is not enabled.
     *
     * @param  int|null  $breakId  Specific break ID to end, or null for any ongoing break
     * @param  array  $logData  Additional log data
     * @return mixed The ended break or null if not found or module disabled
     */
    public function endBreak($breakId = null, $logData = [])
    {
        if (! $this->isBreakSystemEnabled()) {
            return null;
        }

        $query = $this->breaks()->where('status', 'ongoing');

        if ($breakId) {
            $query->where('id', $breakId);
        }

        $break = $query->first();

        if ($break) {
            $break->end_time = now();
            $break->status = 'completed';
            $break->duration = $break->start_time->diffInMinutes($break->end_time);
            $break->save();

            // Create attendance log
            $this->attendanceLogs()->create(array_merge([
                'user_id' => $this->user_id,
                'date' => $this->date,
                'time' => now()->format('H:i:s'),
                'logged_at' => now(),
                'type' => 'break_end',
                'break_type' => $break->break_type,
                'break_duration' => $break->duration,
                'shift_id' => $this->shift_id,
            ], $logData));

            // Recalculate hours
            $this->calculateHours();
            $this->save();
        }

        return $break;
    }

    public function getLatestLog($type = null)
    {
        $query = $this->attendanceLogs()->latest('logged_at');

        if ($type) {
            $query->where('type', $type);
        }

        return $query->first();
    }

    public function getCheckInLog()
    {
        return $this->attendanceLogs()
            ->where('type', 'check_in')
            ->latest('logged_at')
            ->first();
    }

    public function getCheckOutLog()
    {
        return $this->attendanceLogs()
            ->where('type', 'check_out')
            ->latest('logged_at')
            ->first();
    }

    /**
     * Check if attendance has an ongoing break.
     * Returns false if BreakSystem module is not enabled.
     */
    public function hasOngoingBreak(): bool
    {
        if (! $this->isBreakSystemEnabled()) {
            return false;
        }

        return $this->breaks()
            ->where('status', 'ongoing')
            ->exists();
    }

    /**
     * Get the ongoing break for this attendance.
     * Returns null if BreakSystem module is not enabled or no ongoing break.
     */
    public function getOngoingBreak()
    {
        if (! $this->isBreakSystemEnabled()) {
            return null;
        }

        return $this->breaks()
            ->where('status', 'ongoing')
            ->first();
    }

    /**
     * Static Methods
     */
    public static function getOrCreateForToday($userId)
    {
        return self::firstOrCreate(
            [
                'user_id' => $userId,
                'date' => Carbon::today(),
            ],
            [
                'status' => self::STATUS_ABSENT,
                'shift_id' => User::find($userId)->shift_id ?? null,
            ]
        );
    }

    public static function getTodayAttendance($userId)
    {
        return self::where('user_id', $userId)
            ->whereDate('date', Carbon::today())
            ->first();
    }

    /**
     * Check if the BreakSystem module is enabled.
     */
    protected function isBreakSystemEnabled(): bool
    {
        return app(IAddonService::class)->isAddonEnabled('BreakSystem');
    }

    /**
     * Check if the FieldManager module is enabled.
     */
    protected function isFieldManagerEnabled(): bool
    {
        return app(IAddonService::class)->isAddonEnabled('FieldManager');
    }
}
