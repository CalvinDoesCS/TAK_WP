<?php

use App\Models\Attendance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

if (! function_exists('formatHours')) {
    /**
     * Format decimal hours to human-readable format
     *
     * @param  float  $decimalHours
     * @return string
     */
    function formatHours($decimalHours)
    {
        if ($decimalHours == 0) {
            return '0m';
        }

        $hours = floor($decimalHours);
        $minutes = round(($decimalHours - $hours) * 60);

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}m";
        }
    }
}

if (! function_exists('calculateWorkingHours')) {
    /**
     * Calculate total working hours from attendance records
     * Handles multi-session attendance (multiple check-in/check-out per day)
     * Returns hours in decimal format (e.g., 8.5 for 8 hours 30 minutes)
     *
     * @param  \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection|array  $attendances
     * @return float Total working hours (always >= 0)
     */
    function calculateWorkingHours($attendances): float
    {
        if (empty($attendances)) {
            return 0.0;
        }

        // Convert to collection if array
        if (is_array($attendances)) {
            $attendances = collect($attendances);
        }

        $totalMinutes = 0;

        foreach ($attendances as $attendance) {
            // Skip if no check-in time
            if (! isset($attendance->check_in_time) || ! $attendance->check_in_time) {
                continue;
            }

            // If has check-out time, calculate duration
            if (isset($attendance->check_out_time) && $attendance->check_out_time) {
                try {
                    $checkIn = \Carbon\Carbon::parse($attendance->check_in_time);
                    $checkOut = \Carbon\Carbon::parse($attendance->check_out_time);

                    // Only count if check-out is after check-in
                    if ($checkOut->greaterThan($checkIn)) {
                        $totalMinutes += $checkOut->diffInMinutes($checkIn);
                    }
                } catch (\Exception $e) {
                    Log::warning('Error calculating working hours: '.$e->getMessage());

                    continue;
                }
            }
            // If no check-out, it's an ongoing session - don't count it
        }

        // Convert minutes to hours (decimal)
        $hours = $totalMinutes / 60;

        // Ensure non-negative
        return max(0.0, round($hours, 2));
    }
}

if (! function_exists('getTodayWorkingHours')) {
    /**
     * Get today's working hours for a user or all users
     *
     * @param  int|null  $userId  Optional user ID. If null, calculates for all users
     * @return float Total working hours for today
     */
    function getTodayWorkingHours(?int $userId = null): float
    {
        $query = Attendance::whereDate('created_at', now())
            ->where('check_out_time', '!=', null);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $attendances = $query->get();

        return calculateWorkingHours($attendances);
    }
}

if (! function_exists('getWeeklyWorkingHours')) {
    /**
     * Get weekly working hours for a user or all users
     *
     * @param  int|null  $userId  Optional user ID. If null, calculates for all users
     * @return float Total working hours for current week
     */
    function getWeeklyWorkingHours(?int $userId = null): float
    {
        $query = Attendance::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('check_out_time', '!=', null);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $attendances = $query->get();

        return calculateWorkingHours($attendances);
    }
}

if (! function_exists('getMonthlyWorkingHours')) {
    /**
     * Get monthly working hours for a user or all users
     *
     * @param  int|null  $userId  Optional user ID. If null, calculates for all users
     * @return float Total working hours for current month
     */
    function getMonthlyWorkingHours(?int $userId = null): float
    {
        $query = Attendance::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('check_out_time', '!=', null);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $attendances = $query->get();

        return calculateWorkingHours($attendances);
    }
}

if (! function_exists('getWorkingHoursBetween')) {
    /**
     * Get working hours between two dates for a user or all users
     *
     * @param  \Carbon\Carbon|string  $startDate
     * @param  \Carbon\Carbon|string  $endDate
     * @param  int|null  $userId  Optional user ID. If null, calculates for all users
     * @return float Total working hours between dates
     */
    function getWorkingHoursBetween($startDate, $endDate, ?int $userId = null): float
    {
        $startDate = \Carbon\Carbon::parse($startDate);
        $endDate = \Carbon\Carbon::parse($endDate);

        $query = Attendance::whereBetween('created_at', [$startDate, $endDate])
            ->where('check_out_time', '!=', null);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $attendances = $query->get();

        return calculateWorkingHours($attendances);
    }
}
