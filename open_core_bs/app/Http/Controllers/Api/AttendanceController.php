<?php

namespace App\Http\Controllers\Api;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use App\Models\Settings;
use App\Models\Shift;
use App\Notifications\Attendance\CheckInOut;
use App\Services\AddonService\IAddonService;
use App\Services\TrackingService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    protected TrackingService $trackingService;

    protected IAddonService $addonService;

    public function __construct(TrackingService $trackingService, IAddonService $addonService)
    {
        $this->trackingService = $trackingService;
        $this->addonService = $addonService;
    }

    public function getHistory(Request $request)
    {
        $skip = $request->skip ?? 0;
        $take = $request->take ?? 10;

        // Build relationships array based on enabled modules
        $relationships = ['shift', 'attendanceLogs'];

        if ($this->addonService->isAddonEnabled('BreakSystem')) {
            $relationships[] = 'breaks';
        }

        if ($this->addonService->isAddonEnabled('FieldManager')) {
            $relationships[] = 'visits';
        }

        $query = Attendance::query()
            ->where('user_id', auth()->id())
            ->with($relationships)
            ->orderBy('date', 'desc');

        // Apply date filters
        if ($request->has('startDate')) {
            try {
                $fromDate = Carbon::createFromFormat('d-m-Y', $request->startDate)->format('Y-m-d');
                $query->whereDate('date', '>=', $fromDate);
            } catch (Exception $e) {
                return Error::response('Invalid startDate format. Expected dd-MM-yyyy');
            }
        }

        if ($request->has('endDate')) {
            try {
                $toDate = Carbon::createFromFormat('d-m-Y', $request->endDate)->format('Y-m-d');
                $query->whereDate('date', '<=', $toDate);
            } catch (Exception $e) {
                return Error::response('Invalid endDate format. Expected dd-MM-yyyy');
            }
        }

        // Apply status filter
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $totalCount = $query->count();
        $attendances = $query->skip($skip)->take($take)->get();

        $isMultipleCheckInEnabled = Settings::first()->is_multiple_check_in_enabled ?? false;

        $finalAttendances = [];
        foreach ($attendances as $attendance) {
            // Get all check-in and check-out logs for the day
            $checkInLogs = $attendance->attendanceLogs()->where('type', 'check_in')->orderBy('created_at')->get();
            $checkOutLogs = $attendance->attendanceLogs()->where('type', 'check_out')->orderBy('created_at')->get();

            // Build check-in/out pairs for multiple check-ins
            $checkInOutPairs = [];
            if ($isMultipleCheckInEnabled) {
                $maxCount = max($checkInLogs->count(), $checkOutLogs->count());
                for ($i = 0; $i < $maxCount; $i++) {
                    $checkIn = $checkInLogs->get($i);
                    $checkOut = $checkOutLogs->get($i);

                    $checkInOutPairs[] = [
                        'checkIn' => $checkIn ? Carbon::parse($checkIn->created_at)->format('h:i A') : null,
                        'checkOut' => $checkOut ? Carbon::parse($checkOut->created_at)->format('h:i A') : null,
                        'checkInLocation' => $checkIn ? [
                            'latitude' => $checkIn->latitude,
                            'longitude' => $checkIn->longitude,
                            'address' => $checkIn->address,
                        ] : null,
                        'checkOutLocation' => $checkOut ? [
                            'latitude' => $checkOut->latitude,
                            'longitude' => $checkOut->longitude,
                            'address' => $checkOut->address,
                        ] : null,
                    ];
                }
            }

            // Calculate total working hours from all check-in/out pairs
            $totalWorkingMinutes = 0;

            if ($isMultipleCheckInEnabled && ! empty($checkInOutPairs)) {
                // Calculate from each check-in/out pair
                foreach ($checkInOutPairs as $pair) {
                    if ($pair['checkIn']) {
                        $checkInTime = Carbon::parse($attendance->date->format('Y-m-d').' '.$pair['checkIn']);

                        // If checked out, use checkout time; otherwise use current time for ongoing sessions
                        if ($pair['checkOut']) {
                            $checkOutTime = Carbon::parse($attendance->date->format('Y-m-d').' '.$pair['checkOut']);
                        } else {
                            // User is still checked in - use current time for calculation
                            $checkOutTime = now();
                        }

                        $totalWorkingMinutes += $checkInTime->diffInMinutes($checkOutTime);
                    }
                }
            } else {
                // Single check-in/out (legacy mode)
                if ($attendance->check_in_time && $attendance->check_out_time) {
                    $totalWorkingMinutes = $attendance->check_in_time->diffInMinutes($attendance->check_out_time);
                } elseif ($attendance->check_in_time && $attendance->status == 'checked_in') {
                    // User is still checked in - use current time for calculation
                    $totalWorkingMinutes = $attendance->check_in_time->diffInMinutes(now());
                }
            }

            // Calculate break hours
            $totalBreakMinutes = 0;
            if ($this->addonService->isAddonEnabled('BreakSystem')) {
                $totalBreakMinutes = \Modules\BreakSystem\App\Models\AttendanceBreak::where(function ($query) use ($attendance) {
                    $query->where('attendance_id', $attendance->id)
                        ->orWhereIn('attendance_log_id', $attendance->attendanceLogs->pluck('id'));
                })
                    ->whereNotNull('end_time')
                    ->get()
                    ->sum(function ($break) {
                        return Carbon::parse($break->start_time)->diffInMinutes(Carbon::parse($break->end_time));
                    });
            }

            $netWorkingMinutes = $totalWorkingMinutes - $totalBreakMinutes;
            $workingHours = floor($netWorkingMinutes / 60);
            $workingMinutes = $netWorkingMinutes % 60;

            // Get regularization request if any
            $regularization = \App\Models\AttendanceRegularization::where('attendance_id', $attendance->id)->first();

            $finalAttendances[] = [
                'id' => $attendance->id,
                'date' => $attendance->date->format('d-m-Y'),
                'dayName' => $attendance->date->format('l'),

                // Primary check-in/out (first of the day or only one)
                'checkInTime' => $attendance->check_in_time ? $attendance->check_in_time->format('h:i A') : null,
                'checkOutTime' => $attendance->check_out_time ? $attendance->check_out_time->format('h:i A') : null,

                // Multiple check-ins support
                'isMultipleCheckIn' => $isMultipleCheckInEnabled,
                'checkInOutPairs' => $isMultipleCheckInEnabled ? $checkInOutPairs : [],
                'totalCheckIns' => $checkInLogs->count(),
                'totalCheckOuts' => $checkOutLogs->count(),

                // Hours calculation
                'totalHours' => round($totalWorkingMinutes / 60, 2),
                'workingHours' => sprintf('%dh %dm', $workingHours, $workingMinutes),
                'workingHoursDecimal' => round($netWorkingMinutes / 60, 2),
                'breakHours' => round($totalBreakMinutes / 60, 2),
                'breakHoursFormatted' => sprintf('%dh %dm', floor($totalBreakMinutes / 60), $totalBreakMinutes % 60),

                // Overtime and late hours
                'overtime' => $attendance->overtime_hours ?? 0,
                'lateHours' => $attendance->late_hours ?? 0,
                'lateMinutes' => $attendance->getLateMinutesAttribute(),
                'earlyCheckoutMinutes' => $attendance->getEarlyCheckoutMinutesAttribute(),

                // Shift information
                'shift' => $attendance->shift ? [
                    'id' => $attendance->shift->id,
                    'name' => $attendance->shift->name,
                    'startTime' => date('h:i A', strtotime($attendance->shift->start_time)),
                    'endTime' => date('h:i A', strtotime($attendance->shift->end_time)),
                    'workingHours' => $attendance->shift->working_hours ?? 8,
                ] : null,

                // Status and reasons
                'status' => $attendance->status,
                'statusLabel' => ucfirst(str_replace('_', ' ', $attendance->status)),
                'lateReason' => $attendance->late_reason,
                'earlyCheckoutReason' => $attendance->early_checkout_reason,
                'isHoliday' => $attendance->is_holiday ?? false,
                'isWeekend' => $attendance->is_weekend ?? false,
                'isHalfDay' => $attendance->is_half_day ?? false,

                // Breaks information
                // Note: Fetch breaks using both attendance_id and attendance_log_id for backward compatibility
                'breaks' => $this->addonService->isAddonEnabled('BreakSystem')
                    ? \Modules\BreakSystem\App\Models\AttendanceBreak::where(function ($query) use ($attendance) {
                        $query->where('attendance_id', $attendance->id)
                            ->orWhereIn('attendance_log_id', $attendance->attendanceLogs->pluck('id'));
                    })
                        ->whereNotNull('start_time')
                        ->orderBy('start_time')
                        ->get()
                        ->map(function ($break) {
                            $duration = 0;
                            if ($break->end_time) {
                                $duration = Carbon::parse($break->start_time)->diffInMinutes(Carbon::parse($break->end_time));
                            }

                            return [
                                'id' => $break->id,
                                'type' => $break->reason ?? 'break',
                                'startTime' => Carbon::parse($break->start_time)->format('h:i A'),
                                'endTime' => $break->end_time ? Carbon::parse($break->end_time)->format('h:i A') : null,
                                'duration' => $duration,
                                'status' => $break->end_time ? 'completed' : 'ongoing',
                            ];
                        })
                    : [],

                // Activity counts
                'visitsCount' => $this->addonService->isAddonEnabled('FieldManager')
                    ? $attendance->visits->count()
                    : 0,
                'ordersCount' => $this->addonService->isAddonEnabled('ProductOrder')
                    ? \Modules\ProductOrder\App\Models\ProductOrder::where('user_id', auth()->id())
                        ->whereDate('created_at', $attendance->date)
                        ->count()
                    : 0,

                // Regularization
                'hasRegularization' => $regularization !== null,
                'regularizationStatus' => $regularization ? $regularization->status : null,

                // Location (first check-in)
                'checkInLocation' => $checkInLogs->first() ? [
                    'latitude' => $checkInLogs->first()->latitude,
                    'longitude' => $checkInLogs->first()->longitude,
                    'address' => $checkInLogs->first()->address,
                ] : null,

                'checkOutLocation' => $checkOutLogs->last() ? [
                    'latitude' => $checkOutLogs->last()->latitude,
                    'longitude' => $checkOutLogs->last()->longitude,
                    'address' => $checkOutLogs->last()->address,
                ] : null,

                // Distance travelled (calculated from GPS tracking data)
                'distanceTravelled' => $this->trackingService->calculateDailyDistance(auth()->id(), $attendance->date),
            ];
        }

        return Success::response([
            'totalCount' => $totalCount,
            'isMultipleCheckInEnabled' => $isMultipleCheckInEnabled,
            'values' => $finalAttendances,
        ]);
    }

    public function setEarlyCheckoutReason(Request $request)
    {
        $reason = $request->reason;

        if ($reason == null || $reason == '') {
            return Error::response('Reason is required');
        }

        $attendance = Attendance::where('user_id', auth()->user()->id)
            ->whereDate('date', Carbon::today())
            ->first();

        if ($attendance == null) {
            return Error::response('Not checked in');
        }

        $attendance->early_checkout_reason = $reason;
        $attendance->save();

        return Success::response('Reason updated successfully');
    }

    public function canCheckOut()
    {
        $attendance = Attendance::where('user_id', auth()->user()->id)
            ->whereDate('date', Carbon::today())
            ->first();

        if ($attendance == null) {
            return Error::response('Not checked in');
        }

        $shift = Shift::find(auth()->user()->shift_id);

        if ($shift == null) {
            return Error::response('Shift not found');
        }

        if ($shift->end_time < now()) {
            return Success::response('You can check out');
        } else {
            return Error::response('You can not check out before shift end time');
        }
    }

    public function checkStatus()
    {
        $user = auth()->user();

        // Check device status only if FieldManager module is enabled
        $device = null;
        if ($this->addonService->isAddonEnabled('FieldManager')) {
            $device = \Modules\FieldManager\App\Models\UserDevice::where('user_id', $user->id)
                ->first();
        }

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->with('attendanceLogs')
            ->first();

        // Try to get user's assigned shift, fallback to default shift
        $shift = $user->shift_id
            ? Shift::find($user->shift_id)
            : Shift::where('is_default', true)->first();

        if ($shift === null) {
            if ($user->shift_id === null) {
                return Error::response('No shift assigned to user. Please contact administrator.');
            }

            return Error::response('Assigned shift not found');
        }

        $status = 'new';
        $checkInTime = null;
        $checkOutTime = null;

        if ($attendance) {
            if ($attendance->status == 'checked_in') {
                $status = 'checkedin';
                // Check in time only
                $date = strtotime($attendance->check_in_time);
                $checkInTime = date('h:i A', $date);
            } elseif ($attendance->status == 'checked_out' && Settings::first()->is_multiple_check_in_enabled) {
                $status = 'new';
                $checkInTime = null;
                $checkOutTime = null;
            } else {
                $status = 'checkedout';
                // Check in and check out time
                $date = strtotime($attendance->check_in_time);
                $checkInTime = date('h:i A', $date);

                $date = strtotime($attendance->check_out_time);
                $checkOutTime = date('h:i A', $date);
            }
        }

        // Calculate real tracking metrics using TrackingService
        $trackingMetrics = $this->trackingService->getTodayMetrics($user->id);
        $trackedHours = $trackingMetrics['hours'];
        $travelledDistance = $trackingMetrics['distance'];

        $isLate = false;

        // Late check
        if ($status == 'new' && now() > $shift->start_time) {
            $isLate = true;
        }

        // Leave Check
        $isOnLeave = false;
        $leave = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('from_date', '<=', Carbon::today())
            ->where('to_date', '>=', Carbon::today())
            ->first();

        if ($leave != null) {
            $isOnLeave = true;
        }

        // Break checking
        $isOnBreak = false;
        $breakStartedAt = '';
        if ($this->addonService->isAddonEnabled('BreakSystem') && $attendance && $attendance->status == 'checked_in') {
            $latestLog = $attendance->attendanceLogs()
                ->where('type', 'check_in')
                ->latest('created_at')
                ->first();

            if ($latestLog) {
                $break = \Modules\BreakSystem\App\Models\AttendanceBreak::where('attendance_log_id', $latestLog->id)
                    ->whereNull('end_time')
                    ->first();

                if ($break != null) {
                    $isOnBreak = true;
                    $date = strtotime($break->start_time);
                    $breakStartedAt = date('h:i:s A', $date);
                }
            }
        }

        $attendanceType = $this->getAttendanceTypeString($user->attendance_type);

        $shiftStartTime = date('h:i A', strtotime($shift->start_time));
        $shiftEndTime = date('h:i A', strtotime($shift->end_time));

        return Success::response([
            'attendanceType' => $attendanceType == 'site' ? $this->getAttendanceTypeString($user->site->attendance_type) : $attendanceType,
            'userStatus' => $user->status,
            'status' => $status, // 'new', 'present', 'checkedout
            'checkInAt' => $checkInTime,
            'checkOutAt' => $checkOutTime,
            'shiftStartTime' => $shiftStartTime,
            'shiftEndTime' => $shiftEndTime,
            'isLate' => $isLate,
            'isOnBreak' => $isOnBreak,
            'breakStartedAt' => $breakStartedAt,
            'isOnLeave' => $isOnLeave,
            'travelledDistance' => $travelledDistance,
            'trackedHours' => $trackedHours,
            'isSiteEmployee' => $attendanceType == 'site',
            'siteName' => $attendanceType == 'site' ? $user->site->name : '',
            'siteAttendanceType' => $attendanceType == 'site' ? $this->getAttendanceTypeString($user->site->attendance_type) : '',
            'deviceStatus' => $this->addonService->isAddonEnabled('FieldManager') ? ($device ? 'active' : 'kill') : 'active',
        ]);
    }

    private function getAttendanceTypeString($type)
    {
        $attendanceType = 'none';
        if ($type == 'geofence') {
            $attendanceType = 'geofence';
        } elseif ($type == 'site') {
            $attendanceType = 'site';
        } elseif ($type == 'ip_address') {
            $attendanceType = 'ip';
        } elseif ($type == 'qr_code') {
            $attendanceType = 'staticqrcode';
        } elseif ($type == 'dynamic_qr') {
            $attendanceType = 'dynamicqrcode';
        } elseif ($type == 'face_recognition') {
            $attendanceType = 'face';
        }

        return $attendanceType;
    }

    public function checkInOut(Request $request)
    {
        $status = $request->status;
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        if ($status == null) {
            return Error::response('Status is required');
        }

        if ($status != 'checkin' && $status != 'checkout') {
            return Error::response('Invalid status');
        }

        if ($latitude == null || $longitude == null) {
            return Error::response('Location is required');
        }

        if ($status == 'checkin') {
            return $this->checkIn($request);
        } else {
            return $this->checkOut($request);
        }
    }

    private function checkIn($request)
    {
        $user = auth()->user();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        if (! $attendance) {
            $attendanceData = [
                'user_id' => $user->id,
                'date' => Carbon::today(),
                'check_in_time' => now(),
                'status' => 'checked_in',
                'late_reason' => $request->lateReason,
                'shift_id' => $user->shift_id,
                'created_by_id' => $user->id,
            ];

            // Only include site_id if SiteAttendance addon is enabled
            if ($this->addonService->isAddonEnabled('SiteAttendance') && $user->attendance_type == 'site') {
                $attendanceData['site_id'] = $user->site_id;
            }

            $attendance = Attendance::create($attendanceData);
        } elseif ($attendance->status == 'checked_out' && Settings::first()->is_multiple_check_in_enabled) {
            // Only update status, preserve original check_in_time for accurate late calculation
            $attendance->status = 'checked_in';
            $attendance->save();
        } else {
            return Error::response('Already done for the day');
        }

        $log = new AttendanceLog;
        $log->attendance_id = $attendance->id;
        $log->shift_id = $user->shift_id;
        $log->type = 'check_in';
        $log->latitude = $request->latitude;
        $log->longitude = $request->longitude;
        $log->created_by_id = $user->id;
        $log->save();

        // Only create activity record if FieldManager module is enabled
        if ($this->addonService->isAddonEnabled('FieldManager')) {
            \Modules\FieldManager\App\Models\Activity::create([
                'attendance_log_id' => $log->id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'is_mock' => $request->isMock,
                'battery_percentage' => $request->batteryPercentage,
                'is_gps_on' => $request->isGpsOn ?? true,
                'is_wifi_on' => $request->isWifiOn,
                'signal_strength' => $request->signalStrength,
                'type' => 'checked_in',
                'created_by_id' => $user->id,
                'accuracy' => 100,
                'bearing' => $request->bearing,
                'horizontalAccuracy' => $request->horizontalAccuracy,
                'altitude' => $request->altitude,
                'verticalAccuracy' => $request->verticalAccuracy,
                'course' => $request->course,
                'courseAccuracy' => $request->courseAccuracy,
                'speed' => $request->speed,
                'speedAccuracy' => $request->speedAccuracy,
            ]);
        }

        NotificationHelper::notifyAdminHR(new CheckInOut('Attendance Check In', $user->getFullName().' has checked in'));

        // Clear tracking metrics cache to force recalculation
        $this->trackingService->clearMetricsCache($user->id);

        return Success::response('Checked in successfully');
    }

    private function checkOut($request)
    {
        $user = auth()->user();

        $attendance = Attendance::whereDate('date', Carbon::today())
            ->where('user_id', $user->id)
            ->first();

        if (! $attendance) {
            return Error::response('Not checked in');
        }

        if ($attendance->status == 'checked_out') {
            return Error::response('Already checked out');
        }

        // Check Out
        $attendance->status = 'checked_out';
        $attendance->check_out_time = now();
        $attendance->save();

        $log = new AttendanceLog;
        $log->attendance_id = $attendance->id;
        $log->shift_id = $user->shift_id;
        $log->type = 'check_out';
        $log->latitude = $request->latitude;
        $log->longitude = $request->longitude;
        $log->created_by_id = $user->id;
        $log->save();

        // Only create activity record if FieldManager module is enabled
        if ($this->addonService->isAddonEnabled('FieldManager')) {
            \Modules\FieldManager\App\Models\Activity::create([
                'attendance_log_id' => $log->id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'is_mock' => $request->isMock,
                'battery_percentage' => $request->batteryPercentage,
                'is_gps_on' => $request->isGpsOn ?? true,
                'is_wifi_on' => $request->isWifiOn,
                'signal_strength' => $request->signalStrength,
                'type' => 'checked_out',
                'accuracy' => 100,
                'bearing' => $request->bearing,
                'horizontalAccuracy' => $request->horizontalAccuracy,
                'altitude' => $request->altitude,
                'verticalAccuracy' => $request->verticalAccuracy,
                'course' => $request->course,
                'courseAccuracy' => $request->courseAccuracy,
                'speed' => $request->speed,
                'speedAccuracy' => $request->speedAccuracy,
            ]);
        }

        NotificationHelper::notifyAdminHR(new CheckInOut('Attendance Check Out', $user->getFullName().' has checked out'));

        // Clear tracking metrics cache to force final calculation
        $this->trackingService->clearMetricsCache($user->id);

        return Success::response('Checked out successfully');
    }

    public function statusUpdate(Request $request)
    {
        // This endpoint is only for FieldManager GPS tracking
        if (! $this->addonService->isAddonEnabled('FieldManager')) {
            return Success::response('Status updated successfully');
        }

        $status = $request->status;
        $accuracy = $request->accuracy;
        $activity = $request->activity;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $isMock = $request->isMock;
        $batteryPercentage = $request->batteryPercentage;
        $isGpsOn = $request->isGPSOn;
        $isWifiOn = $request->isWifiOn;
        $signalStrength = $request->signalStrength;

        if ($status == null) {
            return Error::response('Status is required');
        }

        if (! $latitude || ! $longitude) {
            return Error::response('Location is required');
        }

        $attendanceLog = AttendanceLog::where('created_by_id', auth()->id())
            ->whereDate('created_at', Carbon::today())
            ->latest()
            ->first();

        if (! $attendanceLog) {
            return Error::response('Attendance not found');
        }

        if ($attendanceLog->type != 'check_in') {
            return Error::response('You are not checked in');
        }

        \Modules\FieldManager\App\Models\Activity::create([
            'uid' => $request->uid,
            'attendance_log_id' => $attendanceLog->id,
            'is_mock' => $isMock,
            'battery_percentage' => $batteryPercentage,
            'is_gps_on' => $isGpsOn,
            'is_wifi_on' => $isWifiOn,
            'signal_strength' => $signalStrength,
            'type' => $status == 'still' ? 'still' : 'travelling',
            'activity' => $activity,
            'accuracy' => $accuracy,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'address' => $request->address,
            'bearing' => $request->bearing,
            'horizontalAccuracy' => $request->horizontalAccuracy,
            'altitude' => $request->altitude,
            'verticalAccuracy' => $request->verticalAccuracy,
            'course' => $request->course,
            'courseAccuracy' => $request->courseAccuracy,
            'speed' => $request->speed,
            'speedAccuracy' => $request->speedAccuracy,
            'created_by_id' => auth()->id(),
        ]);

        return Success::response('Status updated successfully');
    }
}
