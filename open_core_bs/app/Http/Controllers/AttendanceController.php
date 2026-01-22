<?php

namespace App\Http\Controllers;

use App\Enums\UserAccountStatus;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\User;
use App\Services\Settings\ModuleSettingsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class AttendanceController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct()
    {
        // PERMISSIONS TEMPORARILY DISABLED
        // $this->middleware('permission:hrcore.view-attendance|hrcore.view-own-attendance')->only(['index', 'indexAjax']);
        // $this->middleware('permission:hrcore.view-attendance')->only(['show', 'dailyReport', 'dailyReportAjax', 'departmentComparison', 'departmentComparisonAjax', 'departmentComparisonStats']);
        // $this->middleware('permission:hrcore.create-attendance')->only(['store']);
        // $this->middleware('permission:hrcore.edit-attendance')->only(['edit', 'update']);
        // $this->middleware('permission:hrcore.delete-attendance')->only(['destroy']);
        // $this->middleware('permission:hrcore.web-check-in')->only(['webCheckIn']);
        // $this->middleware('permission:hrcore.view-attendance-statistics')->only(['statistics']);
        // $this->middleware('permission:hrcore.export-attendance')->only(['export']);
    }

    public function index()
    {
        $users = User::where('status', UserAccountStatus::ACTIVE)
            ->get();

        $attendances = Attendance::whereDate('date', Carbon::today())
            ->first();

        $logs = AttendanceLog::get();

        return view('attendance.index', [
            'users' => $users,
            'attendances' => $attendances ?? [],
            'attendanceLogs' => $logs ?? [],
        ]);
    }

    public function indexAjax(Request $request)
    {
        $query = Attendance::query()
            ->with(['user', 'shift', 'attendanceLogs']);

        // PERMISSION-BASED FILTERING TEMPORARILY DISABLED
        // Apply permission-based filtering
        // if (auth()->user()->can('hrcore.view-own-attendance') && ! auth()->user()->can('hrcore.view-attendance')) {
        //     // User can only see their own attendance
        //     $query->where('user_id', auth()->id());
        // } elseif (auth()->user()->hasRole('team-leader')) {
        //     // Team leader can see their team's attendance
        //     // This would need to be implemented based on your team structure
        //     // For now, we'll let them see all (you can customize this)
        // }

        // User filter
        if ($request->has('userId') && $request->input('userId')) {
            Log::info('User ID: '.$request->input('userId'));
            $query->where('user_id', $request->input('userId'));
        }

        if ($request->has('date') && $request->input('date')) {
            Log::info('Date: '.$request->input('date'));
            $query->whereDate('date', $request->input('date'));
        } else {
            $query->whereDate('date', Carbon::today());
        }

        // Attendance type filter
        if ($request->has('attendanceType') && $request->input('attendanceType')) {
            $type = $request->input('attendanceType');

            switch ($type) {
                case 'late':
                    $query->where('late_hours', '>', 0);
                    break;
                case 'early':
                    $query->where('early_hours', '>', 0);
                    break;
                case 'overtime':
                    $query->where('overtime_hours', '>', 0);
                    break;
                case 'ontime':
                    $query->where('late_hours', '=', 0)
                        ->where('early_hours', '=', 0);
                    break;
            }
        }

        return DataTables::of($query)
            ->addColumn('id', function ($attendance) {
                return $attendance->id;
            })
            ->editColumn('check_in_time', function ($attendance) {
                if ($attendance->check_in_time) {
                    return Carbon::parse($attendance->check_in_time)->format('h:i A');
                }
                // Fallback to logs if not in attendance table
                $checkInAt = $attendance->attendanceLogs->where('type', 'check_in')->first();

                return $checkInAt ? $checkInAt->created_at->format('h:i A') : 'N/A';
            })
            ->editColumn('check_out_time', function ($attendance) {
                if ($attendance->check_out_time) {
                    return Carbon::parse($attendance->check_out_time)->format('h:i A');
                }
                // Fallback to logs if not in attendance table
                $checkOutAt = $attendance->attendanceLogs->where('type', 'check_out')->last();

                return $checkOutAt ? $checkOutAt->created_at->format('h:i A') : 'N/A';
            })
            ->addColumn('shift', function ($attendance) {
                return $attendance->shift ? $attendance->shift->name : 'N/A';
            })
            ->addColumn('status', function ($attendance) {
                return $attendance->status ?? 'present';
            })
            ->addColumn('late_indicator', function ($attendance) {
                if ($attendance->late_hours > 0) {
                    $formatted = formatHours($attendance->late_hours);

                    return '<span class="badge bg-label-warning"><i class="bx bx-time-five"></i> '.$formatted.'</span>';
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('early_indicator', function ($attendance) {
                if ($attendance->early_hours > 0) {
                    $formatted = formatHours($attendance->early_hours);

                    return '<span class="badge bg-label-danger"><i class="bx bx-log-out"></i> '.$formatted.'</span>';
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('overtime_indicator', function ($attendance) {
                if ($attendance->overtime_hours > 0) {
                    $formatted = formatHours($attendance->overtime_hours);

                    return '<span class="badge bg-label-success"><i class="bx bx-plus-circle"></i> '.$formatted.'</span>';
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('user', function ($attendance) {
                return view('components.datatable-user', [
                    'user' => $attendance->user,
                    'showCode' => true,
                    'linkRoute' => 'employees.show',
                ])->render();
            })
            ->addColumn('actions', function ($attendance) {
                $actions = [];

                // PERMISSIONS TEMPORARILY DISABLED
                // View details - if user has general view permission or can view own attendance
                // if (auth()->user()->can('hrcore.view-attendance') ||
                //    (auth()->user()->can('hrcore.view-own-attendance') && $attendance->user_id === auth()->id())) {
                $actions[] = [
                    'label' => __('View Details'),
                    'icon' => 'bx bx-show',
                    'url' => route('hrcore.attendance.show', $attendance->id),
                ];
                // }

                return view('components.datatable-actions', [
                    'id' => $attendance->id,
                    'actions' => $actions,
                ])->render();
            })
            ->filterColumn('user', function ($query, $keyword) {
                $query->whereHas('user', function ($q) use ($keyword) {
                    $q->where('first_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%")
                        ->orWhere('code', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('shift', function ($query, $keyword) {
                $query->whereHas('shift', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['user', 'actions', 'late_indicator', 'early_indicator', 'overtime_indicator'])
            ->make(true);
    }

    /**
     * Display the specified attendance record
     */
    public function show($id)
    {
        $attendance = Attendance::with([
            'user.designation.department',
            'shift',
            'attendanceLogs',
        ])->findOrFail($id);

        // PERMISSIONS TEMPORARILY DISABLED
        // Check permissions - users can view own attendance or need general view permission
        // if (! auth()->user()->can('hrcore.view-attendance') &&
        //     $attendance->user_id !== auth()->id() &&
        //     ! auth()->user()->can('hrcore.view-own-attendance')) {
        //     abort(403, __('You are not authorized to view this attendance record.'));
        // }

        return view('attendance.show', compact('attendance'));
    }

    /**
     * Display the web attendance page
     */
    public function webAttendance()
    {
        return view('attendance.web-attendance');
    }

    /**
     * Show employee's own attendance records
     */
    public function myAttendance()
    {
        // PERMISSION CHECK TEMPORARILY DISABLED
        // Check permission - user must be able to view own attendance
        // if (! auth()->user()->can('hrcore.view-own-attendance')) {
        //     abort(403, __('You are not authorized to view attendance records.'));
        // }

        $user = auth()->user();
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $attendances = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->with('attendanceLogs')
            ->orderBy('date', 'desc')
            ->get();

        // Calculate statistics correctly
        $present = 0;
        $late = 0;
        $absent = 0;
        $halfDay = 0;

        foreach ($attendances as $attendance) {
            // Count present (checked_in or checked_out status)
            if (in_array($attendance->status, [Attendance::STATUS_CHECKED_IN, Attendance::STATUS_CHECKED_OUT])) {
                $present++;

                // Check if late
                if ($attendance->late_hours > 0) {
                    $late++;
                }
            }

            // Count absent
            if ($attendance->status === Attendance::STATUS_ABSENT) {
                $absent++;
            }

            // Count half day
            if ($attendance->status === Attendance::STATUS_HALF_DAY) {
                $halfDay++;
            }
        }

        $statistics = [
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'half_day' => $halfDay,
        ];

        return view('attendance.my-attendance', compact('user', 'attendances', 'statistics'));
    }

    /**
     * Show specific attendance record for logged-in user
     */
    public function showMyAttendance($id)
    {
        $attendance = Attendance::where('id', $id)
            ->where('user_id', auth()->id())
            ->with('attendanceLogs')
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $attendance,
        ]);
    }

    /**
     * Show regularization requests for employee (ESS - Employee Self Service)
     */
    public function regularization()
    {
        $user = auth()->user();

        // Get regularization requests for the logged-in employee only
        $regularizationRequests = \Illuminate\Support\Facades\DB::table('attendance_regularizations')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get(); // Changed to get() for statistics calculation

        // Convert to collection with proper date and JSON handling
        $regularizationRequests = $regularizationRequests->map(function ($item) {
            $item->created_at = \Carbon\Carbon::parse($item->created_at);
            // Decode JSON fields
            if (is_string($item->attachments)) {
                $item->attachments = json_decode($item->attachments, true);
            }

            return $item;
        });

        return view('attendance.regularization', compact('user', 'regularizationRequests'));
    }

    /**
     * Show attendance reports for employee
     */
    public function myReports(Request $request)
    {
        $user = auth()->user();

        // Get date range from request or use default (last 3 months)
        $period = $request->input('period', 'last_3_months');
        $today = now();

        switch ($period) {
            case 'current_month':
                $startDate = $today->copy()->startOfMonth();
                $endDate = $today->copy()->endOfMonth();
                break;
            case 'last_month':
                $startDate = $today->copy()->subMonth()->startOfMonth();
                $endDate = $today->copy()->subMonth()->endOfMonth();
                break;
            case 'last_3_months':
                $startDate = $today->copy()->subMonths(2)->startOfMonth();
                $endDate = $today->copy()->endOfMonth();
                break;
            case 'last_6_months':
                $startDate = $today->copy()->subMonths(5)->startOfMonth();
                $endDate = $today->copy()->endOfMonth();
                break;
            case 'current_year':
                $startDate = $today->copy()->startOfYear();
                $endDate = $today->copy()->endOfYear();
                break;
            case 'custom':
                $startDate = $request->input('start_date')
                    ? Carbon::parse($request->input('start_date'))
                    : $today->copy()->subMonths(2)->startOfMonth();
                $endDate = $request->input('end_date')
                    ? Carbon::parse($request->input('end_date'))
                    : $today->copy()->endOfMonth();
                break;
            default:
                $startDate = $today->copy()->subMonths(2)->startOfMonth();
                $endDate = $today->copy()->endOfMonth();
        }

        $monthlyStats = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            // Calculate the month's start and end dates within the selected range
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            // Adjust to respect the selected date range
            $effectiveStart = $monthStart->lt($startDate) ? $startDate : $monthStart;
            $effectiveEnd = $monthEnd->gt($endDate) ? $endDate : $monthEnd;

            $attendances = Attendance::where('user_id', $user->id)
                ->whereBetween('date', [$effectiveStart, $effectiveEnd])
                ->get();

            // Count present days (any record with check_in counts as present)
            $presentCount = $attendances->filter(function ($attendance) {
                return in_array($attendance->status, [
                    Attendance::STATUS_CHECKED_IN,
                    Attendance::STATUS_CHECKED_OUT,
                ]) || $attendance->check_in_time !== null;
            })->count();

            // Count late days
            $lateCount = $attendances->filter(function ($attendance) {
                return $attendance->late_hours > 0;
            })->count();

            // Count absent days
            $absentCount = $attendances->where('status', Attendance::STATUS_ABSENT)->count();

            // Count half day
            $halfDayCount = $attendances->where('status', Attendance::STATUS_HALF_DAY)->count();

            $monthlyStats[] = [
                'month' => $current->format('F Y'),
                'present' => $presentCount,
                'absent' => $absentCount,
                'late' => $lateCount,
                'half_day' => $halfDayCount,
                'total_hours' => round($attendances->sum('working_hours'), 2),
            ];

            $current->addMonth();
        }

        return view('attendance.reports', compact('user', 'monthlyStats', 'period'));
    }

    /**
     * Get today's attendance status for the logged-in user
     */
    public function getTodayStatus()
    {
        $userId = auth()->id();
        $today = Carbon::today();

        try {
            // Check if multiple check-in/out is enabled
            $isMultipleCheckInEnabled = $this->isMultipleCheckInEnabled();

            // Get today's attendance record
            $attendance = Attendance::where('user_id', $userId)
                ->whereDate('date', $today)
                ->with('attendanceLogs')
                ->first();

            if (! $attendance) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'hasCheckedIn' => false,
                        'hasCheckedOut' => false,
                        'checkInTime' => null,
                        'checkOutTime' => null,
                        'logs' => [],
                        'isMultipleCheckInEnabled' => $isMultipleCheckInEnabled,
                        'canCheckIn' => true,
                    ],
                ]);
            }

            // Get check-in and check-out logs
            // For multi-check-in mode, use the latest check-in log (for break tracking)
            // For single check-in mode, use the first check-in log
            if ($isMultipleCheckInEnabled) {
                $checkInLog = $attendance->attendanceLogs->where('type', 'check_in')->sortByDesc('created_at')->first();
            } else {
                $checkInLog = $attendance->attendanceLogs->where('type', 'check_in')->first();
            }
            $checkOutLog = $attendance->attendanceLogs->where('type', 'check_out')->last();
            $lastLog = $attendance->attendanceLogs->sortByDesc('created_at')->first();

            // Determine if user can check in again
            $canCheckIn = true;
            if (! $isMultipleCheckInEnabled && $checkOutLog) {
                $canCheckIn = false;
            }

            // Format logs for display (attendance logs)
            $logs = $attendance->attendanceLogs->map(function ($log) {
                return [
                    'type' => $log->type,
                    'created_at' => $log->created_at->toISOString(),
                ];
            })->values()->toArray();

            // Add break logs if BreakSystem is enabled
            $addonService = app(\App\Services\AddonService\IAddonService::class);
            if ($addonService->isAddonEnabled('BreakSystem')) {
                $breakLogs = \Modules\BreakSystem\App\Models\AttendanceBreak::where('attendance_id', $attendance->id)
                    ->orderBy('start_time')
                    ->get();

                foreach ($breakLogs as $breakLog) {
                    // Add break start
                    $logs[] = [
                        'type' => 'break_start',
                        'created_at' => $breakLog->start_time->toISOString(),
                    ];

                    // Add break end if it exists
                    if ($breakLog->end_time) {
                        $logs[] = [
                            'type' => 'break_end',
                            'created_at' => $breakLog->end_time->toISOString(),
                        ];
                    }
                }

                // Sort all logs by created_at
                usort($logs, function ($a, $b) {
                    return strtotime($a['created_at']) - strtotime($b['created_at']);
                });
            }

            // Get check-in and check-out times from attendance record or logs
            $checkInTime = null;
            $checkOutTime = null;

            // Log attendance record data for debugging
            Log::info('Attendance Record Data:', [
                'attendance_id' => $attendance->id,
                'check_in_time' => $attendance->check_in_time,
                'check_out_time' => $attendance->check_out_time,
                'checkInLog_exists' => (bool) $checkInLog,
                'checkOutLog_exists' => (bool) $checkOutLog,
                'checkInLog_created_at' => $checkInLog ? $checkInLog->created_at->toISOString() : null,
                'checkOutLog_created_at' => $checkOutLog ? $checkOutLog->created_at->toISOString() : null,
            ]);

            if ($attendance->check_in_time) {
                // Check if check_in_time is already a full datetime or just time
                $checkInTimeStr = $attendance->check_in_time;
                if (strpos($checkInTimeStr, ':') !== false && strlen($checkInTimeStr) <= 8) {
                    // It's just a time (HH:MM:SS format), add today's date
                    $checkInTime = Carbon::parse($today->format('Y-m-d').' '.$checkInTimeStr)->toISOString();
                    Log::info('Parsed check-in time as time only:', ['original' => $checkInTimeStr, 'parsed' => $checkInTime]);
                } else {
                    // It's already a full datetime
                    $checkInTime = Carbon::parse($checkInTimeStr)->toISOString();
                    Log::info('Parsed check-in time as datetime:', ['original' => $checkInTimeStr, 'parsed' => $checkInTime]);
                }
            } elseif ($checkInLog) {
                // Fallback to log's created_at
                $checkInTime = $checkInLog->created_at->toISOString();
                Log::info('Using check-in log created_at:', ['checkInTime' => $checkInTime]);
            }

            if ($attendance->check_out_time) {
                // Check if check_out_time is already a full datetime or just time
                $checkOutTimeStr = $attendance->check_out_time;
                if (strpos($checkOutTimeStr, ':') !== false && strlen($checkOutTimeStr) <= 8) {
                    // It's just a time (HH:MM:SS format), add today's date
                    $checkOutTime = Carbon::parse($today->format('Y-m-d').' '.$checkOutTimeStr)->toISOString();
                    Log::info('Parsed check-out time as time only:', ['original' => $checkOutTimeStr, 'parsed' => $checkOutTime]);
                } else {
                    // It's already a full datetime
                    $checkOutTime = Carbon::parse($checkOutTimeStr)->toISOString();
                    Log::info('Parsed check-out time as datetime:', ['original' => $checkOutTimeStr, 'parsed' => $checkOutTime]);
                }
            } elseif ($checkOutLog) {
                // Fallback to log's created_at
                $checkOutTime = $checkOutLog->created_at->toISOString();
                Log::info('Using check-out log created_at:', ['checkOutTime' => $checkOutTime]);
            }

            // Check if BreakSystem module is enabled and get break status
            $isBreakSystemEnabled = false;
            $isOnBreak = false;

            $addonService = app(\App\Services\AddonService\IAddonService::class);
            if ($addonService->isAddonEnabled('BreakSystem')) {
                $isBreakSystemEnabled = true;

                // Check if user is currently on break
                if ($checkInLog) {
                    $runningBreak = \Modules\BreakSystem\App\Models\AttendanceBreak::where('attendance_log_id', $checkInLog->id)
                        ->whereNull('end_time')
                        ->first();

                    $isOnBreak = (bool) $runningBreak;
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'hasCheckedIn' => (bool) $checkInLog || ! empty($attendance->check_in_time),
                    'hasCheckedOut' => (bool) $checkOutLog || ! empty($attendance->check_out_time),
                    'checkInTime' => $checkInTime,
                    'checkOutTime' => $checkOutTime,
                    'logs' => $logs,
                    'isMultipleCheckInEnabled' => $isMultipleCheckInEnabled,
                    'canCheckIn' => $canCheckIn,
                    'lastLogType' => $lastLog ? $lastLog->type : null,
                    'isBreakSystemEnabled' => $isBreakSystemEnabled,
                    'isOnBreak' => $isOnBreak,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Get today status error: '.$e->getMessage());

            return response()->json([
                'status' => 'failed',
                'data' => __('An error occurred while fetching attendance status.'),
            ], 500);
        }
    }

    /**
     * Handle web check-in/check-out
     */
    public function webCheckIn(Request $request)
    {
        $userId = auth()->id();
        $date = $request->input('date', Carbon::today()->toDateString());
        $time = $request->input('time', Carbon::now()->format('H:i:s'));
        $latitude = $request->input('latitude', null);
        $longitude = $request->input('longitude', null);

        try {
            // Check if multiple check-in/out is enabled for user's role
            $isMultipleCheckInEnabled = $this->isMultipleCheckInEnabled();

            // Check if attendance record exists for today
            $attendance = Attendance::where('user_id', $userId)
                ->whereDate('date', $date)
                ->first();

            if (! $attendance) {
                // Create new attendance record
                $attendance = Attendance::create([
                    'user_id' => $userId,
                    'check_in_time' => Carbon::parse($date.' '.$time),
                    'shift_id' => auth()->user()->shift_id,
                    'date' => $date,
                    'created_at' => Carbon::parse($date.' '.$time),
                    'updated_at' => now(),
                ]);
            }

            // Check last log to determine if this is check-in or check-out
            $lastLog = AttendanceLog::where('attendance_id', $attendance->id)
                ->orderBy('created_at', 'desc')
                ->first();

            // Determine the type of action
            $type = (! $lastLog || $lastLog->type === 'check_out') ? 'check_in' : 'check_out';

            // Check if user can perform this action
            if (! $isMultipleCheckInEnabled && $lastLog && $lastLog->type === 'check_out') {
                return response()->json([
                    'status' => 'failed',
                    'data' => __('You have already checked out for today. Multiple check-ins are not allowed.'),
                ], 400);
            }

            // Handle check-in: Update attendance record and calculate late hours
            if ($type === 'check_in') {
                $attendance->check_in_time = Carbon::parse($date.' '.$time);
                $attendance->shift_id = $attendance->shift_id ?? auth()->user()->shift_id;

                // Calculate late hours immediately if shift is defined
                if ($attendance->shift) {
                    $attendance->refresh(); // Reload to get shift relationship
                    $lateMinutes = $attendance->getLateMinutesAttribute();
                    $attendance->late_hours = round($lateMinutes / 60, 2);
                }

                $attendance->save();
            }

            // Handle check-out: Update attendance record and calculate all hours
            if ($type === 'check_out' && ! $isMultipleCheckInEnabled) {
                $attendance->check_out_time = Carbon::parse($date.' '.$time);

                // Calculate all hours (working, break, late, early, overtime)
                $attendance->calculateHours();
                $attendance->save();
            }

            // Create attendance log
            AttendanceLog::create([
                'attendance_id' => $attendance->id,
                'user_id' => $userId,
                'date' => $date,
                'time' => $time,
                'logged_at' => Carbon::parse($date.' '.$time),
                'type' => $type,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'created_at' => Carbon::parse($date.' '.$time),
                'updated_at' => now(),
            ]);

            // TODO: Implement late check-in notification feature
            // Late check-in detection logic can be added here when notification class is created

            $message = $type === 'check_in'
              ? __('Checked in successfully')
              : __('Checked out successfully');

            // Check if this is an AJAX request or regular form submission
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'message' => $message,
                        'type' => $type,
                        'isMultipleCheckInEnabled' => $isMultipleCheckInEnabled,
                    ],
                ]);
            }

            // For regular form submissions, redirect back with success message
            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Web check-in error: '.$e->getMessage());

            // Check if this is an AJAX request or regular form submission
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'failed',
                    'data' => __('An error occurred. Please try again.'),
                ], 500);
            }

            // For regular form submissions, redirect back with error message
            return redirect()->back()->with('error', __('An error occurred. Please try again.'));
        }
    }

    /**
     * Web check-out endpoint
     */
    public function webCheckOut(Request $request)
    {
        // Use the same webCheckIn method as it handles both check-in and check-out
        return $this->webCheckIn($request);
    }

    /**
     * Start or stop break
     */
    public function startStopBreak(Request $request)
    {
        // Check if BreakSystem module is enabled
        $addonService = app(\App\Services\AddonService\IAddonService::class);
        if (! $addonService->isAddonEnabled('BreakSystem')) {
            return response()->json([
                'status' => 'failed',
                'data' => __('Break system is not enabled'),
            ], 400);
        }

        $userId = auth()->id();
        $today = Carbon::today();

        try {
            // Get today's attendance record first
            $attendance = Attendance::where('user_id', $userId)
                ->whereDate('date', $today)
                ->first();

            if (! $attendance) {
                return response()->json([
                    'status' => 'failed',
                    'data' => __('You have not checked in yet'),
                ], 400);
            }

            // Get the check-in log for this attendance
            $attendanceLog = AttendanceLog::where('attendance_id', $attendance->id)
                ->where('type', 'check_in')
                ->latest()
                ->first();

            if (! $attendanceLog) {
                return response()->json([
                    'status' => 'failed',
                    'data' => __('You have not checked in yet'),
                ], 400);
            }

            // Check for running break
            $runningBreak = \Modules\BreakSystem\App\Models\AttendanceBreak::where('attendance_log_id', $attendanceLog->id)
                ->whereNull('end_time')
                ->first();

            if ($runningBreak) {
                // Stop the break
                $runningBreak->end_time = now();
                $runningBreak->save();

                // Send notification to admin/HR
                if (class_exists(\App\Helpers\NotificationHelper::class) &&
                    class_exists(\App\Notifications\Alerts\BreakAlert::class)) {
                    \App\Helpers\NotificationHelper::notifyAdminHR(
                        new \App\Notifications\Alerts\BreakAlert(auth()->user()->getFullName(), 'stopped')
                    );
                }

                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'message' => __('Break stopped successfully'),
                        'isOnBreak' => false,
                    ],
                ]);
            } else {
                // Start a new break
                $break = new \Modules\BreakSystem\App\Models\AttendanceBreak;
                $break->attendance_log_id = $attendanceLog->id;
                $break->attendance_id = $attendanceLog->attendance_id;
                $break->start_time = now();
                $break->reason = 'Break';
                $break->save();

                // Send notification to admin/HR
                if (class_exists(\App\Helpers\NotificationHelper::class) &&
                    class_exists(\App\Notifications\Alerts\BreakAlert::class)) {
                    \App\Helpers\NotificationHelper::notifyAdminHR(
                        new \App\Notifications\Alerts\BreakAlert(auth()->user()->getFullName(), 'started')
                    );
                }

                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'message' => __('Break started successfully'),
                        'isOnBreak' => true,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in startStopBreak: '.$e->getMessage());

            return response()->json([
                'status' => 'failed',
                'data' => __('An error occurred. Please try again.'),
            ], 500);
        }
    }

    /**
     * Check if multiple check-in/out is enabled for the user
     */
    private function isMultipleCheckInEnabled(): bool
    {
        // Check settings first
        $settingsService = app(ModuleSettingsService::class);
        $isEnabled = $settingsService->get('HRCore', 'is_multiple_check_in_enabled', true);

        // If setting is disabled, return false regardless of permission
        if (! $isEnabled) {
            return false;
        }

        // If enabled in settings, check user permission
        return auth()->user()->can('hrcore.multiple-check-in');
    }

    /**
     * Get global attendance status for floating indicator
     */
    public function getGlobalStatus()
    {
        $userId = auth()->id();
        $today = Carbon::today();

        try {
            // Get today's attendance record
            $attendance = Attendance::where('user_id', $userId)
                ->whereDate('date', $today)
                ->with('attendanceLogs')
                ->first();

            if (! $attendance) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'isCheckedIn' => false,
                        'showIndicator' => false,
                    ],
                ]);
            }

            $isMultipleCheckInEnabled = $this->isMultipleCheckInEnabled();
            $lastLog = $attendance->attendanceLogs->sortByDesc('created_at')->first();

            // Determine if user is currently checked in
            $isCurrentlyCheckedIn = false;
            if ($isMultipleCheckInEnabled) {
                $isCurrentlyCheckedIn = $lastLog && $lastLog->type === 'check_in';
            } else {
                $isCurrentlyCheckedIn = $attendance->check_in_time && ! $attendance->check_out_time;
            }

            $response = [
                'isCheckedIn' => $isCurrentlyCheckedIn,
                'showIndicator' => $isCurrentlyCheckedIn,
                'checkInTime' => null,
                'workingHours' => null,
            ];

            // Use the attendance table's check_in_time for calculation
            if ($attendance->check_in_time) {
                // Parse the check_in_time properly (it's in UTC format)
                $checkInTime = Carbon::parse($attendance->check_in_time);
                $response['checkInTime'] = $checkInTime->format('h:i A');

                // Calculate working hours if currently checked in
                if ($isCurrentlyCheckedIn) {
                    $now = Carbon::now();

                    // Calculate total minutes difference
                    $totalMinutes = abs($now->diffInMinutes($checkInTime));
                    $hours = floor($totalMinutes / 60);
                    $minutes = $totalMinutes % 60;

                    // Format as "HH:MM" for display in utilities panel
                    $response['workingHours'] = sprintf('%02d:%02d', $hours, $minutes);
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $response,
            ]);

        } catch (\Exception $e) {
            Log::error('Global status error: '.$e->getMessage());

            return response()->json([
                'status' => 'failed',
                'data' => __('An error occurred while fetching status.'),
            ], 500);
        }
    }

    /**
     * Get attendance statistics for the given date
     */
    public function statistics(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());

        // Get total active users
        $totalUsers = User::where('status', UserAccountStatus::ACTIVE)->count();

        // Get attendance records for the date
        $attendances = Attendance::whereDate('date', $date)
            ->with('attendanceLogs')
            ->get();

        $present = 0;
        $late = 0;
        $absent = 0;
        $earlyCheckout = 0;
        $overtime = 0;

        // Calculate statistics
        foreach ($attendances as $attendance) {
            $checkIn = $attendance->attendanceLogs->where('type', 'check_in')->first();

            if ($checkIn) {
                // Count as present (includes both on-time and late)
                $present++;

                // Check if late using the actual late_hours field
                if ($attendance->late_hours > 0) {
                    $late++;
                }

                // Check for early checkout
                if ($attendance->early_hours > 0) {
                    $earlyCheckout++;
                }

                // Check for overtime
                if ($attendance->overtime_hours > 0) {
                    $overtime++;
                }
            }
        }

        // Calculate absent count
        $checkedInUserIds = $attendances->pluck('user_id')->toArray();
        $absent = User::where('status', UserAccountStatus::ACTIVE)
            ->whereNotIn('id', $checkedInUserIds)
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total' => $totalUsers,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'early_checkout' => $earlyCheckout,
                'overtime' => $overtime,
                'date' => $date,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified attendance
     */
    public function edit($id)
    {
        $attendance = Attendance::with([
            'user.designation.department',
            'shift',
            'attendanceLogs',
        ])->findOrFail($id);

        // Get times from attendance table first, fallback to logs
        $checkInTime = '';
        $checkOutTime = '';

        if ($attendance->check_in_time) {
            $checkInTime = Carbon::parse($attendance->check_in_time)->format('H:i');
        } else {
            // Fallback to log if attendance table doesn't have the time
            $checkIn = $attendance->attendanceLogs->where('type', 'check_in')->first();
            $checkInTime = $checkIn ? $checkIn->created_at->format('H:i') : '';
        }

        if ($attendance->check_out_time) {
            $checkOutTime = Carbon::parse($attendance->check_out_time)->format('H:i');
        } else {
            // Fallback to log if attendance table doesn't have the time
            $checkOut = $attendance->attendanceLogs->where('type', 'check_out')->last();
            $checkOutTime = $checkOut ? $checkOut->created_at->format('H:i') : '';
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'attendance' => $attendance,
                'checkInTime' => $checkInTime,
                'checkOutTime' => $checkOutTime,
                'date' => $attendance->date ? $attendance->date->format('Y-m-d') : $attendance->created_at->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Update the specified attendance in storage
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i|after:check_in_time',
            'status' => 'required|in:present,absent,late,half-day',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $attendance = Attendance::findOrFail($id);

            // Parse the times with the attendance date
            $checkInDateTime = Carbon::parse($attendance->date->format('Y-m-d').' '.$request->check_in_time);
            $checkOutDateTime = $request->filled('check_out_time')
                ? Carbon::parse($attendance->date->format('Y-m-d').' '.$request->check_out_time)
                : null;

            // Update attendance record with actual times
            $updateData = [
                'check_in_time' => $checkInDateTime,
                'check_out_time' => $checkOutDateTime,
                'status' => $request->status,
                'notes' => $request->notes,
            ];

            // Calculate hours if both check-in and check-out are present
            if ($checkInDateTime && $checkOutDateTime) {
                $attendance->check_in_time = $checkInDateTime;
                $attendance->check_out_time = $checkOutDateTime;
                $attendance->calculateHours();
                $updateData['working_hours'] = $attendance->working_hours;
                $updateData['late_hours'] = $attendance->late_hours;
                $updateData['early_hours'] = $attendance->early_hours;
            } elseif ($checkInDateTime && $attendance->shift) {
                // Calculate late hours for check-in only
                $attendance->check_in_time = $checkInDateTime;
                $lateMinutes = $attendance->getLateMinutesAttribute();
                $updateData['late_hours'] = round($lateMinutes / 60, 2);
            }

            $attendance->update($updateData);

            // Update check-in log
            $checkInLog = $attendance->attendanceLogs->where('type', 'check_in')->first();
            if ($checkInLog) {
                $checkInLog->update([
                    'time' => $request->check_in_time,
                    'logged_at' => $checkInDateTime,
                    'created_at' => $checkInDateTime,
                ]);
            } else {
                // Create check-in log if it doesn't exist
                AttendanceLog::create([
                    'attendance_id' => $attendance->id,
                    'user_id' => $attendance->user_id,
                    'date' => $attendance->date,
                    'time' => $request->check_in_time,
                    'logged_at' => $checkInDateTime,
                    'type' => 'check_in',
                    'shift_id' => $attendance->shift_id,
                    'created_at' => $checkInDateTime,
                    'updated_at' => now(),
                ]);
            }

            // Update or create check-out log
            if ($request->filled('check_out_time')) {
                $checkOutLog = $attendance->attendanceLogs->where('type', 'check_out')->last();
                if ($checkOutLog) {
                    $checkOutLog->update([
                        'time' => $request->check_out_time,
                        'logged_at' => $checkOutDateTime,
                        'created_at' => $checkOutDateTime,
                    ]);
                } else {
                    AttendanceLog::create([
                        'attendance_id' => $attendance->id,
                        'user_id' => $attendance->user_id,
                        'date' => $attendance->date,
                        'time' => $request->check_out_time,
                        'logged_at' => $checkOutDateTime,
                        'type' => 'check_out',
                        'shift_id' => $attendance->shift_id,
                        'created_at' => $checkOutDateTime,
                        'updated_at' => now(),
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'message' => __('Attendance updated successfully'),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Attendance update error: '.$e->getMessage());

            return response()->json([
                'status' => 'failed',
                'data' => __('Failed to update attendance: '.$e->getMessage()),
            ], 500);
        }
    }

    /**
     * Display the daily attendance report
     */
    public function dailyReport()
    {
        $users = User::where('status', UserAccountStatus::ACTIVE)
            ->with('designation.department')
            ->get();

        $shifts = \App\Models\Shift::all();
        $departments = \App\Models\Department::all();

        return view('attendance.daily-report', compact('users', 'shifts', 'departments'));
    }

    /**
     * Get daily attendance report data for DataTables
     */
    public function dailyReportAjax(Request $request)
    {
        $query = Attendance::query()
            ->select('attendances.*')
            ->with([
                'user.designation.department',
                'shift',
                'attendanceLogs' => function ($q) {
                    $q->whereIn('type', ['check_in', 'check_out']);
                },
            ]);

        // Date filter - default to today
        $date = $request->input('date', Carbon::today()->toDateString());
        $query->whereDate('date', $date);

        // User filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Department filter
        if ($request->filled('department_id')) {
            $query->whereHas('user.designation.department', function ($q) use ($request) {
                $q->where('id', $request->input('department_id'));
            });
        }

        // Shift filter
        if ($request->filled('shift_id')) {
            $query->where('attendances.shift_id', $request->input('shift_id'));
        }

        // Status filter
        if ($request->filled('status')) {
            $status = $request->input('status');
            switch ($status) {
                case 'present':
                    $query->whereNotNull('check_in_time');
                    break;
                case 'late':
                    $query->where('late_hours', '>', 0);
                    break;
                case 'early':
                    $query->where('early_hours', '>', 0);
                    break;
                case 'overtime':
                    $query->where('overtime_hours', '>', 0);
                    break;
            }
        }

        return DataTables::of($query)
            ->addColumn('user', function ($attendance) {
                return view('components.datatable-user', [
                    'user' => $attendance->user,
                    'showCode' => true,
                    'linkRoute' => 'employees.show',
                ])->render();
            })
            ->addColumn('date', function ($attendance) {
                return $attendance->date ? $attendance->date->format('d M, Y') : 'N/A';
            })
            ->addColumn('check_in', function ($attendance) {
                if ($attendance->check_in_time) {
                    return '<div class="text-nowrap">'.Carbon::parse($attendance->check_in_time)->format('h:i A').'</div>';
                }
                $checkInLog = $attendance->attendanceLogs->where('type', 'check_in')->first();

                return $checkInLog ? '<div class="text-nowrap">'.$checkInLog->created_at->format('h:i A').'</div>' : '<span class="text-muted">—</span>';
            })
            ->addColumn('check_out', function ($attendance) {
                if ($attendance->check_out_time) {
                    return '<div class="text-nowrap">'.Carbon::parse($attendance->check_out_time)->format('h:i A').'</div>';
                }
                $checkOutLog = $attendance->attendanceLogs->where('type', 'check_out')->last();

                return $checkOutLog ? '<div class="text-nowrap">'.$checkOutLog->created_at->format('h:i A').'</div>' : '<span class="text-muted">—</span>';
            })
            ->addColumn('shift', function ($attendance) {
                if ($attendance->shift) {
                    return '<div class="text-nowrap">'.$attendance->shift->name.'</div>';
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('working_hours', function ($attendance) {
                if ($attendance->working_hours > 0) {
                    $formatted = formatHours($attendance->working_hours);

                    return '<span class="badge bg-label-info">'.$formatted.'</span>';
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('late_hours', function ($attendance) {
                if ($attendance->late_hours > 0) {
                    $formatted = formatHours($attendance->late_hours);

                    return '<span class="badge bg-label-warning"><i class="bx bx-time-five"></i> '.$formatted.'</span>';
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('early_hours', function ($attendance) {
                if ($attendance->early_hours > 0) {
                    $formatted = formatHours($attendance->early_hours);

                    return '<span class="badge bg-label-danger"><i class="bx bx-log-out"></i> '.$formatted.'</span>';
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('overtime_hours', function ($attendance) {
                if ($attendance->overtime_hours > 0) {
                    $formatted = formatHours($attendance->overtime_hours);

                    return '<span class="badge bg-label-success"><i class="bx bx-plus-circle"></i> '.$formatted.'</span>';
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('status', function ($attendance) {
                $status = $attendance->status ?? 'present';
                $badgeClass = match ($status) {
                    'present' => 'bg-label-success',
                    'absent' => 'bg-label-danger',
                    'on_leave' => 'bg-label-info',
                    'half_day' => 'bg-label-warning',
                    default => 'bg-label-secondary',
                };

                return '<span class="badge '.$badgeClass.'">'.ucfirst(str_replace('_', ' ', $status)).'</span>';
            })
            ->addColumn('location', function ($attendance) {
                $checkInLog = $attendance->attendanceLogs->where('type', 'check_in')->first();
                if ($checkInLog && ($checkInLog->latitude || $checkInLog->address)) {
                    $address = $checkInLog->address ?: 'Lat: '.$checkInLog->latitude.', Long: '.$checkInLog->longitude;

                    return '<div class="text-truncate" style="max-width: 150px;" title="'.$address.'">
                                <i class="bx bx-map-pin"></i> '.substr($address, 0, 30).'...
                            </div>';
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('actions', function ($attendance) {
                $actions = [];

                // PERMISSIONS TEMPORARILY DISABLED
                // if (auth()->user()->can('hrcore.view-attendance') && $attendance->id) {
                if ($attendance->id) {
                    $actions[] = [
                        'label' => __('View Details'),
                        'icon' => 'bx bx-show',
                        'url' => route('hrcore.attendance.show', ['id' => $attendance->id]),
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $attendance->id,
                    'actions' => $actions,
                ])->render();
            })
            ->filterColumn('user.first_name', function ($query, $keyword) {
                $query->whereHas('user', function ($q) use ($keyword) {
                    $q->where('first_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%")
                        ->orWhere('code', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('shift.name', function ($query, $keyword) {
                $query->whereHas('shift', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['user', 'check_in', 'check_out', 'shift', 'working_hours', 'late_hours', 'early_hours', 'overtime_hours', 'status', 'location', 'actions'])
            ->make(true);
    }

    /**
     * Display department-wise attendance comparison report
     */
    public function departmentComparison()
    {
        $departments = \App\Models\Department::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('attendance.department-comparison', compact('departments'));
    }

    /**
     * Get department comparison statistics
     */
    public function departmentComparisonStats(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $departmentIds = $request->input('department_ids', []);

        $query = \App\Models\Department::query()
            ->where('status', 'active');

        if (! empty($departmentIds)) {
            $query->whereIn('id', $departmentIds);
        }

        $departments = $query->orderBy('name')->get();

        $comparisonData = [];
        $overallStats = [
            'total_employees' => 0,
            'total_present_days' => 0,
            'total_working_hours' => 0,
            'total_late_instances' => 0,
            'total_overtime_hours' => 0,
            'best_department' => null,
            'worst_department' => null,
        ];

        foreach ($departments as $department) {
            // Get all employees in this department
            $employeeIds = \App\Models\User::whereHas('designation', function ($q) use ($department) {
                $q->where('department_id', $department->id);
            })
                ->where('status', UserAccountStatus::ACTIVE)
                ->pluck('id');

            $totalEmployees = $employeeIds->count();

            if ($totalEmployees === 0) {
                continue;
            }

            // Get attendance records for this department in date range
            $attendances = Attendance::whereIn('user_id', $employeeIds)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            $totalPresentDays = $attendances->count();
            $totalWorkingHours = $attendances->sum('working_hours');
            $totalLateInstances = $attendances->where('late_hours', '>', 0)->count();
            $averageLateHours = $attendances->avg('late_hours') ?: 0;
            $totalOvertimeHours = $attendances->sum('overtime_hours');
            $averageOvertimeHours = $attendances->avg('overtime_hours') ?: 0;

            // Calculate working days in range
            $workingDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;

            // Calculate attendance rate
            $expectedAttendance = $totalEmployees * $workingDays;
            $attendanceRate = $expectedAttendance > 0 ? ($totalPresentDays / $expectedAttendance) * 100 : 0;

            // Calculate punctuality score (100 - percentage of late instances)
            $punctualityScore = $totalPresentDays > 0 ? (($totalPresentDays - $totalLateInstances) / $totalPresentDays) * 100 : 100;

            // Determine trend (comparing with previous period)
            $previousStartDate = Carbon::parse($startDate)->subDays($workingDays)->toDateString();
            $previousEndDate = Carbon::parse($startDate)->subDay()->toDateString();

            $previousAttendances = Attendance::whereIn('user_id', $employeeIds)
                ->whereBetween('date', [$previousStartDate, $previousEndDate])
                ->get();

            $previousPresentDays = $previousAttendances->count();
            $previousExpectedAttendance = $totalEmployees * $workingDays;
            $previousAttendanceRate = $previousExpectedAttendance > 0 ? ($previousPresentDays / $previousExpectedAttendance) * 100 : 0;

            $trend = $attendanceRate > $previousAttendanceRate ? 'up' : ($attendanceRate < $previousAttendanceRate ? 'down' : 'stable');

            $comparisonData[] = [
                'department_id' => $department->id,
                'department_name' => $department->name,
                'department_code' => $department->code,
                'total_employees' => $totalEmployees,
                'total_present_days' => $totalPresentDays,
                'attendance_rate' => round($attendanceRate, 2),
                'total_working_hours' => round($totalWorkingHours, 2),
                'average_working_hours' => $totalPresentDays > 0 ? round($totalWorkingHours / $totalPresentDays, 2) : 0,
                'total_late_instances' => $totalLateInstances,
                'average_late_hours' => round($averageLateHours, 2),
                'total_overtime_hours' => round($totalOvertimeHours, 2),
                'average_overtime_hours' => round($averageOvertimeHours, 2),
                'punctuality_score' => round($punctualityScore, 2),
                'trend' => $trend,
            ];

            // Update overall stats
            $overallStats['total_employees'] += $totalEmployees;
            $overallStats['total_present_days'] += $totalPresentDays;
            $overallStats['total_working_hours'] += $totalWorkingHours;
            $overallStats['total_late_instances'] += $totalLateInstances;
            $overallStats['total_overtime_hours'] += $totalOvertimeHours;
        }

        // Sort by attendance rate to determine best/worst
        usort($comparisonData, function ($a, $b) {
            return $b['attendance_rate'] <=> $a['attendance_rate'];
        });

        if (! empty($comparisonData)) {
            $overallStats['best_department'] = $comparisonData[0];
            $overallStats['worst_department'] = end($comparisonData);
            $overallStats['average_attendance_rate'] = round(array_sum(array_column($comparisonData, 'attendance_rate')) / count($comparisonData), 2);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'departments' => $comparisonData,
                'overall' => $overallStats,
                'date_range' => [
                    'start' => Carbon::parse($startDate)->format('d M, Y'),
                    'end' => Carbon::parse($endDate)->format('d M, Y'),
                ],
            ],
        ]);
    }

    /**
     * Get department comparison data for DataTable
     */
    public function departmentComparisonAjax(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $departmentIds = $request->input('department_ids', []);

        $query = \App\Models\Department::query()
            ->where('status', 'active');

        if (! empty($departmentIds)) {
            $query->whereIn('id', $departmentIds);
        }

        $departments = $query->orderBy('name')->get();

        $tableData = [];

        foreach ($departments as $department) {
            // Get all employees in this department
            $employeeIds = \App\Models\User::whereHas('designation', function ($q) use ($department) {
                $q->where('department_id', $department->id);
            })
                ->where('status', UserAccountStatus::ACTIVE)
                ->pluck('id');

            $totalEmployees = $employeeIds->count();

            if ($totalEmployees === 0) {
                continue;
            }

            // Get attendance records
            $attendances = Attendance::whereIn('user_id', $employeeIds)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            $totalPresentDays = $attendances->count();
            $totalWorkingHours = $attendances->sum('working_hours');
            $totalLateInstances = $attendances->where('late_hours', '>', 0)->count();
            $averageLateHours = $attendances->avg('late_hours') ?: 0;
            $totalOvertimeHours = $attendances->sum('overtime_hours');
            $averageOvertimeHours = $attendances->avg('overtime_hours') ?: 0;

            // Calculate metrics
            $workingDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
            $expectedAttendance = $totalEmployees * $workingDays;
            $attendanceRate = $expectedAttendance > 0 ? ($totalPresentDays / $expectedAttendance) * 100 : 0;
            $punctualityScore = $totalPresentDays > 0 ? (($totalPresentDays - $totalLateInstances) / $totalPresentDays) * 100 : 100;

            $tableData[] = [
                'department' => $department->name,
                'code' => $department->code,
                'total_employees' => $totalEmployees,
                'attendance_rate' => $attendanceRate,
                'total_present_days' => $totalPresentDays,
                'total_working_hours' => $totalWorkingHours,
                'average_late_hours' => $averageLateHours,
                'total_late_instances' => $totalLateInstances,
                'total_overtime_hours' => $totalOvertimeHours,
                'average_overtime_hours' => $averageOvertimeHours,
                'punctuality_score' => $punctualityScore,
            ];
        }

        return DataTables::of(collect($tableData))
            ->addColumn('department', function ($row) {
                return '<div class="fw-semibold">'.$row['department'].'</div>
                        <small class="text-muted">'.$row['code'].'</small>';
            })
            ->addColumn('total_employees', function ($row) {
                return '<span class="badge bg-label-primary">'.$row['total_employees'].'</span>';
            })
            ->addColumn('attendance_rate', function ($row) {
                $rate = round($row['attendance_rate'], 2);
                $color = $rate >= 90 ? 'success' : ($rate >= 75 ? 'warning' : 'danger');

                return '<div class="d-flex align-items-center">
                            <span class="badge bg-label-'.$color.' me-2">'.$rate.'%</span>
                            <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                <div class="progress-bar bg-'.$color.'" style="width: '.$rate.'%"></div>
                            </div>
                        </div>';
            })
            ->addColumn('total_present_days', function ($row) {
                return '<span class="text-primary fw-semibold">'.$row['total_present_days'].'</span>';
            })
            ->addColumn('total_working_hours', function ($row) {
                $formatted = formatHours($row['total_working_hours']);

                return '<span class="badge bg-label-info">'.$formatted.'</span>';
            })
            ->addColumn('late_metrics', function ($row) {
                $avgLateFormatted = formatHours($row['average_late_hours']);

                return '<div class="text-nowrap">
                            <span class="badge bg-label-warning mb-1">'.$row['total_late_instances'].' '.__('instances').'</span><br>
                            <small class="text-muted">'.__('Avg').': '.$avgLateFormatted.'</small>
                        </div>';
            })
            ->addColumn('overtime_metrics', function ($row) {
                $totalFormatted = formatHours($row['total_overtime_hours']);
                $avgFormatted = formatHours($row['average_overtime_hours']);

                return '<div class="text-nowrap">
                            <span class="badge bg-label-success mb-1">'.$totalFormatted.'</span><br>
                            <small class="text-muted">'.__('Avg').': '.$avgFormatted.'</small>
                        </div>';
            })
            ->addColumn('punctuality_score', function ($row) {
                $score = round($row['punctuality_score'], 2);
                $color = $score >= 90 ? 'success' : ($score >= 75 ? 'warning' : 'danger');

                return '<span class="badge bg-'.$color.'">'.$score.'%</span>';
            })
            ->addColumn('ranking', function ($row) use ($tableData) {
                // Sort by attendance rate to get ranking
                usort($tableData, function ($a, $b) {
                    return $b['attendance_rate'] <=> $a['attendance_rate'];
                });
                $rank = array_search($row, $tableData) + 1;

                $icon = $rank === 1 ? '<i class="bx bx-trophy text-warning"></i>' : '<span class="text-muted">#'.$rank.'</span>';

                return $icon;
            })
            ->rawColumns(['department', 'total_employees', 'attendance_rate', 'total_present_days', 'total_working_hours', 'late_metrics', 'overtime_metrics', 'punctuality_score', 'ranking'])
            ->make(true);
    }

    /**
     * Display employee attendance history report
     */
    public function employeeHistory(Request $request, $userId = null)
    {
        // If no user specified, redirect to selection page
        if (! $userId) {
            $users = User::where('status', UserAccountStatus::ACTIVE)
                ->orderBy('first_name')
                ->get();

            return view('attendance.employee-history-select', compact('users'));
        }

        // Get the employee
        $employee = User::with(['designation.department', 'shift'])->findOrFail($userId);

        // PERMISSIONS TEMPORARILY DISABLED
        // Check permissions
        // if (! auth()->user()->can('hrcore.view-attendance') &&
        //     $employee->id !== auth()->id() &&
        //     ! auth()->user()->can('hrcore.view-own-attendance')) {
        //     abort(403, __('You are not authorized to view this attendance history.'));
        // }

        return view('attendance.employee-history', compact('employee'));
    }

    /**
     * Get employee attendance history data for AJAX
     */
    public function employeeHistoryData(Request $request, $userId)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        // Get the employee
        $employee = User::with(['designation.department', 'shift'])->findOrFail($userId);

        // PERMISSIONS TEMPORARILY DISABLED
        // Check permissions
        // if (! auth()->user()->can('hrcore.view-attendance') &&
        //     $employee->id !== auth()->id() &&
        //     ! auth()->user()->can('hrcore.view-own-attendance')) {
        //     return response()->json(['error' => __('Unauthorized')], 403);
        // }

        // Get attendance records
        $attendances = Attendance::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['attendanceLogs.shift', 'shift', 'site'])
            ->orderBy('date', 'desc')
            ->get();

        // Calculate statistics
        $statistics = [
            'total_days' => $attendances->count(),
            'present_days' => $attendances->where('status', 'present')->count(),
            'absent_days' => 0, // Will be calculated based on working days
            'late_days' => $attendances->where('late_hours', '>', 0)->count(),
            'early_checkout_days' => $attendances->where('early_hours', '>', 0)->count(),
            'total_working_hours' => $attendances->sum('working_hours'),
            'total_late_hours' => $attendances->sum('late_hours'),
            'total_early_hours' => $attendances->sum('early_hours'),
            'total_overtime_hours' => $attendances->sum('overtime_hours'),
            'avg_working_hours' => $attendances->avg('working_hours') ?? 0,
            'attendance_percentage' => 0,
        ];

        // Calculate working days in range (excluding weekends)
        $workingDays = 0;
        $currentDate = Carbon::parse($startDate);
        $endDateCarbon = Carbon::parse($endDate);

        while ($currentDate <= $endDateCarbon) {
            if (! $currentDate->isWeekend()) {
                $workingDays++;
            }
            $currentDate->addDay();
        }

        $statistics['absent_days'] = max(0, $workingDays - $statistics['total_days']);
        $statistics['attendance_percentage'] = $workingDays > 0
            ? round(($statistics['total_days'] / $workingDays) * 100, 2)
            : 0;

        // Format attendance records for response
        $formattedAttendances = $attendances->map(function ($attendance) {
            $checkInLog = $attendance->attendanceLogs->where('type', 'check_in')->first();
            $checkOutLog = $attendance->attendanceLogs->where('type', 'check_out')->last();

            return [
                'id' => $attendance->id,
                'date' => $attendance->date->format('Y-m-d'),
                'date_formatted' => $attendance->date->format('D, M d, Y'),
                'check_in_time' => $attendance->check_in_time
                    ? Carbon::parse($attendance->check_in_time)->format('h:i A')
                    : ($checkInLog ? $checkInLog->created_at->format('h:i A') : null),
                'check_out_time' => $attendance->check_out_time
                    ? Carbon::parse($attendance->check_out_time)->format('h:i A')
                    : ($checkOutLog ? $checkOutLog->created_at->format('h:i A') : null),
                'working_hours' => round($attendance->working_hours ?? 0, 2),
                'late_hours' => round($attendance->late_hours ?? 0, 2),
                'early_hours' => round($attendance->early_hours ?? 0, 2),
                'overtime_hours' => round($attendance->overtime_hours ?? 0, 2),
                'break_hours' => round($attendance->break_hours ?? 0, 2),
                'status' => $attendance->status,
                'shift_name' => $attendance->shift?->name ?? __('N/A'),
                'site_name' => $attendance->site?->name ?? __('N/A'),
                'check_in_address' => $checkInLog?->address ?? __('N/A'),
                'check_out_address' => $checkOutLog?->address ?? __('N/A'),
                'logs_count' => $attendance->attendanceLogs->count(),
                'logs' => $attendance->attendanceLogs->map(function ($log) {
                    return [
                        'type' => $log->type,
                        'time' => $log->created_at->format('h:i A'),
                        'address' => $log->address ?? __('N/A'),
                        'latitude' => $log->latitude,
                        'longitude' => $log->longitude,
                    ];
                }),
            ];
        });

        // Generate calendar data
        $calendarData = [];
        $currentDate = Carbon::parse($startDate);

        while ($currentDate <= $endDateCarbon) {
            $dateStr = $currentDate->format('Y-m-d');
            $attendance = $attendances->firstWhere('date', $currentDate->toDateString());

            $status = 'not-marked';
            $statusClass = 'bg-light';

            if ($attendance) {
                if ($attendance->late_hours > 0) {
                    $status = 'late';
                    $statusClass = 'bg-warning';
                } elseif ($attendance->status === 'present') {
                    $status = 'present';
                    $statusClass = 'bg-success';
                } elseif ($attendance->status === 'absent') {
                    $status = 'absent';
                    $statusClass = 'bg-danger';
                } elseif ($attendance->status === 'half-day') {
                    $status = 'half-day';
                    $statusClass = 'bg-info';
                }
            } elseif ($currentDate->isWeekend()) {
                $status = 'weekend';
                $statusClass = 'bg-secondary';
            } elseif ($currentDate->isPast()) {
                $status = 'absent';
                $statusClass = 'bg-danger';
            }

            $calendarData[] = [
                'date' => $dateStr,
                'day' => $currentDate->format('d'),
                'dayName' => $currentDate->format('D'),
                'status' => $status,
                'statusClass' => $statusClass,
                'isWeekend' => $currentDate->isWeekend(),
                'isToday' => $currentDate->isToday(),
            ];

            $currentDate->addDay();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'attendances' => $formattedAttendances,
                'statistics' => $statistics,
                'calendar' => $calendarData,
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->getFullName(),
                    'code' => $employee->code,
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'department' => $employee->designation?->department?->name ?? __('N/A'),
                    'designation' => $employee->designation?->name ?? __('N/A'),
                    'shift' => $employee->shift?->name ?? __('N/A'),
                    'photo_url' => $employee->profile_photo_path
                        ? asset('storage/'.$employee->profile_photo_path)
                        : asset('assets/img/avatars/1.png'),
                ],
            ],
        ]);
    }

    /**
     * Display monthly calendar view
     */
    public function monthlyCalendar()
    {
        return view('attendance.monthly-calendar');
    }

    /**
     * Get monthly calendar data for AJAX
     */
    public function monthlyCalendarData(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $departmentId = $request->input('department_id');
        $search = $request->input('search');

        // Get start and end dates for the month
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        $daysInMonth = $endDate->day;

        // Build employee query
        $employeesQuery = User::where('status', UserAccountStatus::ACTIVE)
            ->with(['designation.department', 'shift']);

        // Apply department filter
        if ($departmentId) {
            $employeesQuery->whereHas('designation.department', function ($q) use ($departmentId) {
                $q->where('id', $departmentId);
            });
        }

        // Apply search filter
        if ($search) {
            $employeesQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $employees = $employeesQuery->orderBy('first_name')->get();

        // Get all attendance records for the month
        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
            ->whereIn('user_id', $employees->pluck('id'))
            ->get()
            ->groupBy('user_id');

        // Get approved leave requests for the month
        $leaveRequests = \App\Models\LeaveRequest::where('status', \App\Enums\LeaveRequestStatus::APPROVED)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('from_date', [$startDate, $endDate])
                    ->orWhereBetween('to_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('from_date', '<=', $startDate)
                            ->where('to_date', '>=', $endDate);
                    });
            })
            ->whereIn('user_id', $employees->pluck('id'))
            ->get()
            ->groupBy('user_id');

        // Get active holidays for the month
        $holidays = \App\Models\Holiday::where('is_active', true)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(function ($holiday) {
                return $holiday->date->format('Y-m-d');
            });

        // Inject calculation service for real-time calculations
        $calculationService = app(\App\Services\AttendanceCalculationService::class);

        // Build the calendar data
        $calendarData = [];

        foreach ($employees as $employee) {
            $employeeData = [
                'id' => $employee->id,
                'code' => $employee->code,
                'name' => $employee->getFullName(),
                'email' => $employee->email,
                'department' => $employee->designation?->department?->name ?? 'N/A',
                'designation' => $employee->designation?->name ?? 'N/A',
                'photo_url' => $employee->profile_photo_path
                    ? asset('storage/'.$employee->profile_photo_path)
                    : asset('assets/img/avatars/1.png'),
                'days' => [],
            ];

            // Initialize all days
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = Carbon::create($year, $month, $day);
                $dateStr = $currentDate->format('Y-m-d');

                // Find attendance for this day
                $attendance = null;
                if (isset($attendances[$employee->id])) {
                    $attendance = $attendances[$employee->id]->first(function ($att) use ($currentDate) {
                        return $att->date->isSameDay($currentDate);
                    });
                }

                // Real-time calculation for today if data is missing
                $isRealtime = false;
                if ($currentDate->isToday() && $attendance) {
                    // Check if attendance has calculated data
                    if (! $attendance->working_hours || $attendance->working_hours == 0) {
                        // Clone to avoid saving to database
                        $tempAttendance = clone $attendance;
                        $tempAttendance->setRelation('attendanceLogs', $attendance->attendanceLogs);
                        $tempAttendance->setRelation('shift', $attendance->shift);

                        // Calculate using service (save = false)
                        $calculationService->calculateAttendance($tempAttendance, false);

                        // Use calculated values
                        $attendance = $tempAttendance;
                        $isRealtime = true;
                    }
                }

                // Determine status
                $status = 'not-marked';
                $statusLabel = '—';
                $statusClass = 'bg-light text-muted';
                $checkInTime = null;
                $checkOutTime = null;
                $workingHours = null;

                // First check if there's a holiday on this date
                if (isset($holidays[$dateStr])) {
                    $holiday = $holidays[$dateStr];
                    if ($holiday->is_half_day) {
                        $status = 'half-day';
                        $statusLabel = 'H';
                        $statusClass = 'bg-info text-white';
                    } else {
                        $status = 'holiday';
                        $statusLabel = 'HD';
                        $statusClass = 'bg-info text-white';
                    }
                }
                // Then check if employee has approved leave on this date
                elseif (isset($leaveRequests[$employee->id])) {
                    foreach ($leaveRequests[$employee->id] as $leave) {
                        $leaveFromDate = Carbon::parse($leave->from_date);
                        $leaveToDate = Carbon::parse($leave->to_date);

                        if ($currentDate->between($leaveFromDate, $leaveToDate)) {
                            if ($leave->is_half_day) {
                                $status = 'half-day';
                                $statusLabel = 'H';
                                $statusClass = 'bg-info text-white';
                            } else {
                                $status = 'leave';
                                $statusLabel = 'LV';
                                $statusClass = 'bg-primary text-white';
                            }
                            break;
                        }
                    }
                }
                // Check if weekend (Saturday/Sunday)
                elseif ($currentDate->isWeekend()) {
                    $status = 'weekend';
                    $statusLabel = 'W';
                    $statusClass = 'bg-secondary text-white';
                }
                // Then check attendance record if exists
                elseif ($attendance) {
                    $checkInTime = $attendance->check_in_time ? Carbon::parse($attendance->check_in_time)->format('h:i A') : null;
                    $checkOutTime = $attendance->check_out_time ? Carbon::parse($attendance->check_out_time)->format('h:i A') : null;
                    $workingHours = $attendance->working_hours > 0 ? formatHours($attendance->working_hours) : null;

                    // Check if late (only if checked in)
                    if ($attendance->late_hours > 0 && $attendance->check_in_time) {
                        $status = 'late';
                        $statusLabel = 'L';
                        $statusClass = 'bg-warning text-dark';
                    }
                    // Check if early checkout (only if checked in)
                    elseif ($attendance->early_hours > 0 && $attendance->check_in_time) {
                        $status = 'early';
                        $statusLabel = 'E';
                        $statusClass = 'bg-danger text-white';
                    }
                    // Present (has check in time)
                    elseif ($attendance->check_in_time) {
                        $status = 'present';
                        $statusLabel = 'P';
                        $statusClass = 'bg-success text-white';
                    }
                    // Absent (explicitly marked)
                    elseif ($attendance->status === Attendance::STATUS_ABSENT) {
                        $status = 'absent';
                        $statusLabel = 'A';
                        $statusClass = 'bg-danger text-white';
                    }
                    // Half day (explicitly marked) - Fixed: use 'half_day' instead of 'half-day'
                    elseif ($attendance->status === Attendance::STATUS_HALF_DAY) {
                        $status = 'half-day';
                        $statusLabel = 'H';
                        $statusClass = 'bg-info text-white';
                    }
                }
                // If past date with no attendance, leave, or holiday, mark as absent
                elseif ($currentDate->isPast()) {
                    $status = 'absent';
                    $statusLabel = 'A';
                    $statusClass = 'bg-danger text-white';
                }

                $employeeData['days'][] = [
                    'date' => $dateStr,
                    'day' => $day,
                    'status' => $status,
                    'statusLabel' => $statusLabel,
                    'statusClass' => $statusClass,
                    'checkInTime' => $checkInTime,
                    'checkOutTime' => $checkOutTime,
                    'workingHours' => $workingHours,
                    'isWeekend' => $currentDate->isWeekend(),
                    'isToday' => $currentDate->isToday(),
                    'attendance_id' => $attendance?->id,
                    'isRealtime' => $isRealtime, // Flag for real-time vs calculated data
                ];
            }

            $calendarData[] = $employeeData;
        }

        // Get departments for filter
        $departments = \App\Models\Department::where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($dept) {
                return [
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'code' => $dept->code,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'employees' => $calendarData,
                'departments' => $departments,
                'month' => $month,
                'year' => $year,
                'monthName' => Carbon::create($year, $month, 1)->format('F Y'),
                'daysInMonth' => $daysInMonth,
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Manually recalculate attendance for a date range
     * Used by admins to trigger recalculation when needed
     */
    public function recalculateAttendance(Request $request)
    {
        // Validate request
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            // Use service to recalculate
            $calculationService = app(\App\Services\AttendanceCalculationService::class);
            $stats = $calculationService->calculateForDateRange($startDate, $endDate);

            return response()->json([
                'status' => 'success',
                'message' => __('Attendance recalculated successfully'),
                'data' => [
                    'processed' => $stats['processed'],
                    'absents_created' => $stats['absents_created'],
                    'dates' => $stats['dates'],
                    'errors' => $stats['errors'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Manual recalculation error: '.$e->getMessage());

            return response()->json([
                'status' => 'failed',
                'message' => __('Failed to recalculate attendance: ').$e->getMessage(),
            ], 500);
        }
    }
}
