<?php

namespace App\Http\Controllers;

use App\Enums\UserAccountStatus;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AttendanceMonthlySummaryController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct()
    {
        // PERMISSIONS TEMPORARILY DISABLED
        // $this->middleware('permission:hrcore.view-attendance')->only(['index', 'indexAjax', 'statistics']);
    }

    /**
     * Display the monthly attendance summary report
     */
    public function index()
    {
        $departments = Department::where('status', \App\Enums\Status::ACTIVE)
            ->orderBy('name')
            ->get();

        $users = User::where('status', UserAccountStatus::ACTIVE)
            ->orderBy('first_name')
            ->get();

        return view('attendance.monthly-summary', [
            'departments' => $departments,
            'users' => $users,
        ]);
    }

    /**
     * Get monthly summary data for DataTable
     */
    public function indexAjax(Request $request)
    {
        $month = $request->input('month', now()->format('m'));
        $year = $request->input('year', now()->format('Y'));
        $departmentId = $request->input('department_id');
        $userId = $request->input('user_id');

        // Start and end dates for the month
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Build user query
        $query = User::where('status', UserAccountStatus::ACTIVE)
            ->with(['designation.department']);

        // Apply filters
        if ($departmentId) {
            $query->whereHas('designation.department', function ($q) use ($departmentId) {
                $q->where('id', $departmentId);
            });
        }

        if ($userId) {
            $query->where('id', $userId);
        }

        $users = $query->get();

        // Calculate metrics for each user
        $summaryData = $users->map(function ($user) use ($startDate, $endDate) {
            $attendances = Attendance::where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            // Calculate working days in the month (excluding weekends)
            $workingDays = $this->calculateWorkingDays($startDate, $endDate);

            // Calculate metrics
            $presentDays = $attendances->where('status', Attendance::STATUS_CHECKED_OUT)->count() +
                          $attendances->where('status', Attendance::STATUS_CHECKED_IN)->count();

            $absentDays = $workingDays - $presentDays;
            $lateDays = $attendances->where('late_hours', '>', 0)->count();
            $halfDays = $attendances->where('is_half_day', true)->count();

            $totalWorkingHours = $attendances->sum('working_hours');
            $totalLateHours = $attendances->sum('late_hours');
            $totalEarlyHours = $attendances->sum('early_hours');
            $totalOvertimeHours = $attendances->sum('overtime_hours');

            $attendancePercentage = $workingDays > 0 ? round(($presentDays / $workingDays) * 100, 2) : 0;

            return [
                'user' => $user,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'late_days' => $lateDays,
                'half_days' => $halfDays,
                'total_working_hours' => $totalWorkingHours,
                'total_late_hours' => $totalLateHours,
                'total_early_hours' => $totalEarlyHours,
                'total_overtime_hours' => $totalOvertimeHours,
                'attendance_percentage' => $attendancePercentage,
            ];
        });

        return DataTables::of($summaryData)
            ->addColumn('user', function ($data) {
                return view('components.datatable-user', [
                    'user' => $data['user'],
                    'showCode' => true,
                    'showDepartment' => true,
                    'linkRoute' => 'employees.show',
                ])->render();
            })
            ->addColumn('present_days', function ($data) {
                return '<span class="badge bg-label-success">'.$data['present_days'].'</span>';
            })
            ->addColumn('absent_days', function ($data) {
                if ($data['absent_days'] > 0) {
                    return '<span class="badge bg-label-danger">'.$data['absent_days'].'</span>';
                }

                return '<span class="text-muted">0</span>';
            })
            ->addColumn('late_days', function ($data) {
                if ($data['late_days'] > 0) {
                    return '<span class="badge bg-label-warning"><i class="bx bx-time-five"></i> '.$data['late_days'].'</span>';
                }

                return '<span class="text-muted">0</span>';
            })
            ->addColumn('half_days', function ($data) {
                if ($data['half_days'] > 0) {
                    return '<span class="badge bg-label-info">'.$data['half_days'].'</span>';
                }

                return '<span class="text-muted">0</span>';
            })
            ->addColumn('total_working_hours', function ($data) {
                return formatHours($data['total_working_hours']);
            })
            ->addColumn('total_late_hours', function ($data) {
                if ($data['total_late_hours'] > 0) {
                    return '<span class="text-warning">'.formatHours($data['total_late_hours']).'</span>';
                }

                return '<span class="text-muted">'.formatHours(0).'</span>';
            })
            ->addColumn('total_early_hours', function ($data) {
                if ($data['total_early_hours'] > 0) {
                    return '<span class="text-danger">'.formatHours($data['total_early_hours']).'</span>';
                }

                return '<span class="text-muted">'.formatHours(0).'</span>';
            })
            ->addColumn('total_overtime_hours', function ($data) {
                if ($data['total_overtime_hours'] > 0) {
                    return '<span class="text-success">'.formatHours($data['total_overtime_hours']).'</span>';
                }

                return '<span class="text-muted">'.formatHours(0).'</span>';
            })
            ->addColumn('attendance_percentage', function ($data) {
                $percentage = $data['attendance_percentage'];
                $badgeClass = 'bg-label-danger';

                if ($percentage >= 95) {
                    $badgeClass = 'bg-label-success';
                } elseif ($percentage >= 85) {
                    $badgeClass = 'bg-label-primary';
                } elseif ($percentage >= 75) {
                    $badgeClass = 'bg-label-warning';
                }

                return '<span class="badge '.$badgeClass.'">'.$percentage.'%</span>';
            })
            ->addColumn('actions', function ($data) {
                $user = $data['user'];
                $actions = [];

                // View detailed attendance
                if (auth()->user()->can('hrcore.view-attendance')) {
                    $actions[] = [
                        'label' => __('View Details'),
                        'icon' => 'bx bx-show',
                        'url' => route('hrcore.attendance.index', ['userId' => $user->id]),
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $user->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['user', 'present_days', 'absent_days', 'late_days', 'half_days', 'total_late_hours', 'total_early_hours', 'total_overtime_hours', 'attendance_percentage', 'actions'])
            ->make(true);
    }

    /**
     * Get aggregate statistics for the month
     */
    public function statistics(Request $request)
    {
        $month = $request->input('month', now()->format('m'));
        $year = $request->input('year', now()->format('Y'));
        $departmentId = $request->input('department_id');
        $userId = $request->input('user_id');

        // Start and end dates for the month
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Build user query
        $query = User::where('status', UserAccountStatus::ACTIVE);

        // Apply filters
        if ($departmentId) {
            $query->whereHas('designation.department', function ($q) use ($departmentId) {
                $q->where('id', $departmentId);
            });
        }

        if ($userId) {
            $query->where('id', $userId);
        }

        $users = $query->get();
        $totalEmployees = $users->count();

        if ($totalEmployees === 0) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_employees' => 0,
                    'average_attendance_rate' => 0,
                    'total_working_hours' => 0,
                    'total_overtime_hours' => 0,
                ],
            ]);
        }

        // Calculate working days
        $workingDays = $this->calculateWorkingDays($startDate, $endDate);

        // Get all attendances for the period
        $attendanceQuery = Attendance::whereBetween('date', [$startDate, $endDate]);

        if ($userId) {
            $attendanceQuery->where('user_id', $userId);
        } elseif ($departmentId) {
            $userIds = $users->pluck('id');
            $attendanceQuery->whereIn('user_id', $userIds);
        } else {
            $userIds = $users->pluck('id');
            $attendanceQuery->whereIn('user_id', $userIds);
        }

        $attendances = $attendanceQuery->get();

        // Calculate totals
        $totalPresentDays = $attendances->whereIn('status', [
            Attendance::STATUS_CHECKED_OUT,
            Attendance::STATUS_CHECKED_IN,
        ])->count();

        $totalWorkingHours = $attendances->sum('working_hours');
        $totalOvertimeHours = $attendances->sum('overtime_hours');

        // Calculate average attendance rate
        $averageAttendanceRate = $totalEmployees > 0 && $workingDays > 0
            ? round(($totalPresentDays / ($totalEmployees * $workingDays)) * 100, 2)
            : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_employees' => $totalEmployees,
                'average_attendance_rate' => $averageAttendanceRate,
                'total_working_hours' => round($totalWorkingHours, 2),
                'total_overtime_hours' => round($totalOvertimeHours, 2),
            ],
        ]);
    }

    /**
     * Calculate working days in a date range (excluding weekends)
     */
    private function calculateWorkingDays(Carbon $startDate, Carbon $endDate): int
    {
        $workingDays = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            // Skip Saturdays (6) and Sundays (0)
            if ($currentDate->dayOfWeek !== 0 && $currentDate->dayOfWeek !== 6) {
                $workingDays++;
            }
            $currentDate->addDay();
        }

        return $workingDays;
    }
}
