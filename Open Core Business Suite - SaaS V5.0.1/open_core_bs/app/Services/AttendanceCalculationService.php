<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Centralized service for all attendance calculations
 *
 * Used by:
 * - Scheduled command (payroll:attendance-calculate) - runs daily at 23:30
 * - Real-time calendar display - calculates today's data on-the-fly
 * - Manual recalculation - admin-triggered recalculation
 * - Attendance model - delegates calculation logic
 */
class AttendanceCalculationService
{
    /**
     * Calculate attendance metrics for a single attendance record
     * Supports multiple check-in/check-out sessions
     *
     * @param  Attendance  $attendance  The attendance record to calculate
     * @param  bool  $save  Whether to save to database (false for real-time display)
     * @return Attendance The attendance record with calculated values
     */
    public function calculateAttendance(Attendance $attendance, bool $save = true): Attendance
    {
        // Load necessary relationships if not already loaded
        $attendance->loadMissing(['attendanceLogs', 'shift', 'user']);

        // Calculate working minutes (handles multiple sessions)
        $workingMinutes = $this->calculateWorkingMinutes($attendance);
        $attendance->working_hours = round($workingMinutes / 60, 2);

        // Calculate break minutes (if BreakSystem enabled)
        $breakMinutes = $this->calculateBreakMinutes($attendance);
        $attendance->break_hours = round($breakMinutes / 60, 2);

        // Calculate late hours (first check-in vs shift start)
        $lateMinutes = $this->calculateLateMinutes($attendance);
        $attendance->late_hours = round($lateMinutes / 60, 2);

        // Calculate early checkout hours (last check-out vs shift end)
        $earlyMinutes = $this->calculateEarlyMinutes($attendance);
        $attendance->early_hours = round($earlyMinutes / 60, 2);

        // Calculate overtime hours
        $overtimeMinutes = $this->calculateOvertimeMinutes($attendance);
        $attendance->overtime_hours = round($overtimeMinutes / 60, 2);

        // Update status if needed
        $this->updateAttendanceStatus($attendance);

        if ($save) {
            $attendance->save();
        }

        return $attendance;
    }

    /**
     * Calculate total working minutes from multiple check-in/check-out pairs
     * Handles scenarios like: 9am-12pm, 1pm-5pm = 7 hours
     *
     * @return int Total working minutes
     */
    protected function calculateWorkingMinutes(Attendance $attendance): int
    {
        $logs = $attendance->attendanceLogs->sortBy('created_at');
        $totalMinutes = 0;
        $lastCheckIn = null;

        foreach ($logs as $log) {
            if ($log->type === 'check_in') {
                $lastCheckIn = $log->created_at;
            } elseif ($log->type === 'check_out' && $lastCheckIn) {
                $minutes = $lastCheckIn->diffInMinutes($log->created_at);
                $totalMinutes += $minutes;
                $lastCheckIn = null; // Reset for next pair
            }
        }

        // Handle case where checked in but not checked out yet (ongoing session)
        if ($lastCheckIn && ! $attendance->check_out_time) {
            $now = Carbon::now();
            $minutes = $lastCheckIn->diffInMinutes($now);
            $totalMinutes += $minutes;
        }

        return $totalMinutes;
    }

    /**
     * Calculate total break minutes from BreakSystem module
     *
     * @return int Total break minutes
     */
    protected function calculateBreakMinutes(Attendance $attendance): int
    {
        $addonService = app(\App\Services\AddonService\IAddonService::class);

        if (! $addonService->isAddonEnabled('BreakSystem')) {
            return 0;
        }

        $breaks = \Modules\BreakSystem\App\Models\AttendanceBreak::where('attendance_id', $attendance->id)
            ->whereNotNull('end_time')
            ->get();

        $totalMinutes = 0;
        foreach ($breaks as $break) {
            $minutes = Carbon::parse($break->start_time)->diffInMinutes(Carbon::parse($break->end_time));
            $totalMinutes += $minutes;
        }

        return $totalMinutes;
    }

    /**
     * Calculate late minutes (first check-in vs shift start time)
     *
     * @return int Late minutes
     */
    protected function calculateLateMinutes(Attendance $attendance): int
    {
        if (! $attendance->shift || ! $attendance->check_in_time) {
            return 0;
        }

        // Extract time portion from shift start_time (could be "09:00:00" or "2025-11-19 09:00:00")
        $shiftStartTimeOnly = Carbon::parse($attendance->shift->start_time)->format('H:i:s');
        $shiftStartTime = Carbon::parse($attendance->date->format('Y-m-d').' '.$shiftStartTimeOnly);
        $checkInTime = Carbon::parse($attendance->check_in_time);

        if ($checkInTime->gt($shiftStartTime)) {
            return $shiftStartTime->diffInMinutes($checkInTime);
        }

        return 0;
    }

    /**
     * Calculate early checkout minutes (last check-out vs shift end time)
     *
     * @return int Early checkout minutes
     */
    protected function calculateEarlyMinutes(Attendance $attendance): int
    {
        if (! $attendance->shift || ! $attendance->check_out_time) {
            return 0;
        }

        // Extract time portion from shift end_time (could be "17:00:00" or "2025-11-19 17:00:00")
        $shiftEndTimeOnly = Carbon::parse($attendance->shift->end_time)->format('H:i:s');
        $shiftEndTime = Carbon::parse($attendance->date->format('Y-m-d').' '.$shiftEndTimeOnly);
        $checkOutTime = Carbon::parse($attendance->check_out_time);

        if ($checkOutTime->lt($shiftEndTime)) {
            return $checkOutTime->diffInMinutes($shiftEndTime);
        }

        return 0;
    }

    /**
     * Calculate overtime minutes (total working time beyond shift hours)
     *
     * @return int Overtime minutes
     */
    protected function calculateOvertimeMinutes(Attendance $attendance): int
    {
        if (! $attendance->shift) {
            return 0;
        }

        $workingMinutes = $this->calculateWorkingMinutes($attendance);
        $breakMinutes = $this->calculateBreakMinutes($attendance);
        $netWorkingMinutes = $workingMinutes - $breakMinutes;

        // Calculate expected shift duration in minutes
        $shiftStart = Carbon::parse($attendance->shift->start_time);
        $shiftEnd = Carbon::parse($attendance->shift->end_time);
        $expectedMinutes = $shiftStart->diffInMinutes($shiftEnd);

        $overtimeMinutes = $netWorkingMinutes - $expectedMinutes;

        return $overtimeMinutes > 0 ? $overtimeMinutes : 0;
    }

    /**
     * Update attendance status based on calculated values
     */
    protected function updateAttendanceStatus(Attendance $attendance): void
    {
        // Don't override if explicitly set to leave, holiday, etc.
        if (in_array($attendance->status, [
            Attendance::STATUS_LEAVE,
            Attendance::STATUS_HOLIDAY,
            Attendance::STATUS_WEEKEND,
        ])) {
            return;
        }

        if ($attendance->check_out_time) {
            $attendance->status = Attendance::STATUS_CHECKED_OUT;
        } elseif ($attendance->check_in_time) {
            $attendance->status = Attendance::STATUS_CHECKED_IN;
        }
    }

    /**
     * Calculate attendance for a date range
     * Used by scheduled command and manual recalculation
     *
     * @return array Statistics about the calculation
     */
    public function calculateForDateRange(Carbon $startDate, Carbon $endDate): array
    {
        $stats = [
            'processed' => 0,
            'absents_created' => 0,
            'errors' => 0,
            'dates' => [],
        ];

        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            try {
                $dateStats = $this->calculateForDate($currentDate);
                $stats['processed'] += $dateStats['processed'];
                $stats['absents_created'] += $dateStats['absents_created'];
                $stats['dates'][] = $currentDate->format('Y-m-d');
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Attendance calculation error for '.$currentDate->format('Y-m-d').': '.$e->getMessage());
            }

            $currentDate->addDay();
        }

        return $stats;
    }

    /**
     * Calculate attendance for a specific date
     * Creates absence records for users without attendance
     *
     * @return array Statistics
     */
    public function calculateForDate(Carbon $date): array
    {
        $stats = ['processed' => 0, 'absents_created' => 0];

        // Get all active users
        $allUsers = User::where('status', \App\Enums\UserAccountStatus::ACTIVE)->pluck('id');

        // Get users who have attendance records
        $attendances = Attendance::whereDate('date', $date)
            ->with(['attendanceLogs', 'shift', 'user'])
            ->get();

        $usersWithAttendance = $attendances->pluck('user_id');

        // Find users without attendance (absent users)
        $absentUserIds = $allUsers->diff($usersWithAttendance);

        // Create absence records (skip weekends)
        if (! $date->isWeekend()) {
            foreach ($absentUserIds as $userId) {
                $user = User::find($userId);

                Attendance::create([
                    'user_id' => $userId,
                    'date' => $date,
                    'status' => Attendance::STATUS_ABSENT,
                    'shift_id' => $user->shift_id,
                ]);

                $stats['absents_created']++;
            }
        }

        // Calculate metrics for existing attendances
        foreach ($attendances as $attendance) {
            $this->calculateAttendance($attendance, true);
            $stats['processed']++;
        }

        return $stats;
    }
}
