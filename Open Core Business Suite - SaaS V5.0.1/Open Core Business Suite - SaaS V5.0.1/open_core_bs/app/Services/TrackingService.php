<?php

namespace App\Services;

use App\Models\Attendance;
use App\Services\AddonService\IAddonService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service for calculating tracking metrics like distance and hours
 * Used for field sales and attendance tracking
 */
class TrackingService
{
    protected IAddonService $addonService;

    public function __construct(IAddonService $addonService)
    {
        $this->addonService = $addonService;
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula
     *
     * @param  float  $lat1  Starting latitude
     * @param  float  $lon1  Starting longitude
     * @param  float  $lat2  Ending latitude
     * @param  float  $lon2  Ending longitude
     * @return float Distance in kilometers
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // Earth's radius in kilometers
        $earthRadius = 6371;

        // Convert degrees to radians
        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);

        // Haversine formula
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        // Distance in kilometers
        return $earthRadius * $c;
    }

    /**
     * Calculate total distance travelled for a user on a specific date
     * Uses GPS tracking data from activities table
     *
     * @param  int  $userId  User ID
     * @param  Carbon  $date  Date to calculate for
     * @return float Total distance in kilometers
     */
    public function calculateDailyDistance(int $userId, Carbon $date): float
    {
        // FieldManager module is required for GPS tracking
        if (! $this->addonService->isAddonEnabled('FieldManager')) {
            return 0.0;
        }

        try {
            // Get all tracking points for the day with GPS data
            // Join with attendance_logs to get the user's tracking data
            $trackingPoints = \Modules\FieldManager\App\Models\Activity::select([
                'activities.latitude',
                'activities.longitude',
                'activities.speed',
                'activities.accuracy',
                'activities.activity',
                'activities.type',
                'activities.created_at',
                'activities.is_mock',
            ])
                ->join('attendance_logs', 'activities.attendance_log_id', '=', 'attendance_logs.id')
                ->where('attendance_logs.created_by_id', $userId)
                ->whereDate('activities.created_at', $date)
                ->whereNotNull('activities.latitude')
                ->whereNotNull('activities.longitude')
                ->where('activities.is_mock', false) // Exclude mock locations
                ->where('activities.accuracy', '<', 100) // Only use accurate GPS points
                ->orderBy('activities.created_at', 'asc')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            // Column may not exist if migrations haven't been run on tenant database
            return 0.0;
        }

        if ($trackingPoints->count() < 2) {
            return 0.0;
        }

        $totalDistance = 0;
        $previousPoint = null;

        foreach ($trackingPoints as $point) {
            if ($previousPoint !== null) {
                // Calculate distance between consecutive points
                $distance = $this->calculateDistance(
                    $previousPoint->latitude,
                    $previousPoint->longitude,
                    $point->latitude,
                    $point->longitude
                );

                // Time difference in minutes
                $timeDiff = Carbon::parse($point->created_at)
                    ->diffInMinutes(Carbon::parse($previousPoint->created_at));

                // Filtering criteria to avoid GPS errors:
                // 1. Distance > 0.01 km (10 meters) to avoid GPS drift when stationary
                // 2. Distance < 1 km to avoid GPS jumps/errors between points
                // 3. Time difference < 5 minutes to avoid long gaps
                // 4. Only count 'travelling' type or with movement activity
                $isValidDistance = $distance > 0.01 && $distance < 1.0;
                $isValidTime = $timeDiff > 0 && $timeDiff < 5;
                $isMoving = $point->type === 'travelling' ||
                           in_array($point->activity, ['walking', 'running', 'on_bicycle', 'in_vehicle']);

                if ($isValidDistance && $isValidTime && $isMoving) {
                    $totalDistance += $distance;
                }
            }

            $previousPoint = $point;
        }

        // Round to 2 decimal places
        return round($totalDistance, 2);
    }

    /**
     * Calculate tracked hours for a user on a specific date
     * From check-in time to checkout time (or current time if still checked in)
     * Excludes break time
     *
     * @param  int  $userId  User ID
     * @param  Carbon  $date  Date to calculate for
     * @return float Hours worked (with 1 decimal precision)
     */
    public function calculateTrackedHours(int $userId, Carbon $date): float
    {
        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('date', $date)
            ->first();

        if (! $attendance || ! $attendance->check_in_time) {
            return 0.0;
        }

        $checkInTime = Carbon::parse($attendance->check_in_time);

        // If still working (not checked out), use current time
        // Otherwise use check-out time
        if ($attendance->check_out_time) {
            $checkOutTime = Carbon::parse($attendance->check_out_time);
        } else {
            // Still checked in, use current time
            $checkOutTime = Carbon::now();
        }

        // Calculate total minutes worked
        $totalMinutes = $checkInTime->diffInMinutes($checkOutTime);

        // Calculate break duration (only if BreakSystem module is enabled)
        $breakMinutes = 0;
        if ($this->addonService->isAddonEnabled('BreakSystem')) {
            $breakMinutes = DB::table('attendance_breaks')
                ->where('attendance_log_id', function ($query) use ($attendance) {
                    $query->select('id')
                        ->from('attendance_logs')
                        ->where('attendance_id', $attendance->id)
                        ->where('type', 'check_in')
                        ->orderBy('created_at', 'desc')
                        ->limit(1);
                })
                ->whereNotNull('end_time')
                ->get()
                ->sum(function ($break) {
                    return Carbon::parse($break->start_time)
                        ->diffInMinutes(Carbon::parse($break->end_time));
                });
        }

        // Net working minutes (excluding breaks)
        $netWorkingMinutes = $totalMinutes - $breakMinutes;

        // Convert to hours with 1 decimal precision
        $workedHours = $netWorkingMinutes / 60;

        return round($workedHours, 1);
    }

    /**
     * Get today's tracking metrics for a user
     * Returns both distance and hours with caching
     *
     * @param  int  $userId  User ID
     * @return array ['distance' => float, 'hours' => float]
     */
    public function getTodayMetrics(int $userId): array
    {
        $cacheKey = "tracking_metrics_{$userId}_".Carbon::today()->format('Y-m-d');

        // Cache for 2 minutes to reduce calculation load
        return Cache::remember($cacheKey, 120, function () use ($userId) {
            $today = Carbon::today();

            return [
                'distance' => $this->calculateDailyDistance($userId, $today),
                'hours' => $this->calculateTrackedHours($userId, $today),
            ];
        });
    }

    /**
     * Clear cached metrics for a user (call after check-in/check-out/break)
     *
     * @param  int  $userId  User ID
     */
    public function clearMetricsCache(int $userId): void
    {
        $cacheKey = "tracking_metrics_{$userId}_".Carbon::today()->format('Y-m-d');
        Cache::forget($cacheKey);
    }

    /**
     * Get tracking statistics for a date range
     *
     * @param  int  $userId  User ID
     * @param  Carbon  $startDate  Start date
     * @param  Carbon  $endDate  End date
     * @return array Statistics array
     */
    public function getRangeStatistics(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        $stats = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $stats[] = [
                'date' => $currentDate->format('Y-m-d'),
                'distance' => $this->calculateDailyDistance($userId, $currentDate),
                'hours' => $this->calculateTrackedHours($userId, $currentDate),
            ];

            $currentDate->addDay();
        }

        return [
            'data' => $stats,
            'total_distance' => array_sum(array_column($stats, 'distance')),
            'total_hours' => array_sum(array_column($stats, 'hours')),
            'avg_distance' => count($stats) > 0 ? array_sum(array_column($stats, 'distance')) / count($stats) : 0,
            'avg_hours' => count($stats) > 0 ? array_sum(array_column($stats, 'hours')) / count($stats) : 0,
        ];
    }
}
