<?php

namespace App\Http\Controllers;

use App\Enums\UserAccountStatus;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AttendanceReportController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct()
    {
        // PERMISSIONS TEMPORARILY DISABLED
        // $this->middleware('permission:hrcore.view-attendance')->only(['overtimeReport', 'overtimeReportAjax', 'overtimeStatistics']);
    }

    /**
     * Display the overtime report page
     */
    public function overtimeReport()
    {
        $departments = Department::where('status', 1)->get();
        $users = User::where('status', UserAccountStatus::ACTIVE)->get();

        return view('attendance.reports.overtime', [
            'departments' => $departments,
            'users' => $users,
        ]);
    }

    /**
     * Get overtime report data for DataTables
     */
    public function overtimeReportAjax(Request $request)
    {
        $query = Attendance::query()
            ->with(['user.designation.department', 'shift', 'approvedBy'])
            ->where('overtime_hours', '>', 0);

        // Date range filter
        if ($request->has('start_date') && $request->input('start_date')) {
            $query->whereDate('date', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date') && $request->input('end_date')) {
            $query->whereDate('date', '<=', $request->input('end_date'));
        }

        // Default to current month if no dates provided
        if (! $request->has('start_date') && ! $request->has('end_date')) {
            $query->whereMonth('date', Carbon::now()->month)
                ->whereYear('date', Carbon::now()->year);
        }

        // Department filter
        if ($request->has('department_id') && $request->input('department_id')) {
            $departmentId = $request->input('department_id');
            $query->whereHas('user.designation.department', function ($q) use ($departmentId) {
                $q->where('id', $departmentId);
            });
        }

        // User filter
        if ($request->has('user_id') && $request->input('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Minimum overtime hours filter
        if ($request->has('min_overtime_hours') && $request->input('min_overtime_hours')) {
            $query->where('overtime_hours', '>=', $request->input('min_overtime_hours'));
        }

        // Day type filter
        if ($request->has('day_type') && $request->input('day_type')) {
            $dayType = $request->input('day_type');
            switch ($dayType) {
                case 'weekend':
                    $query->where('is_weekend', true);
                    break;
                case 'holiday':
                    $query->where('is_holiday', true);
                    break;
                case 'weekday':
                    $query->where('is_weekend', false)
                        ->where('is_holiday', false);
                    break;
            }
        }

        // Approval status filter
        if ($request->has('approval_status') && $request->input('approval_status')) {
            $status = $request->input('approval_status');
            if ($status === 'approved') {
                $query->whereNotNull('approved_by_id')
                    ->whereNotNull('approved_at');
            } elseif ($status === 'pending') {
                $query->whereNull('approved_by_id')
                    ->whereNull('approved_at');
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
            ->editColumn('date', function ($attendance) {
                return Carbon::parse($attendance->date)->format('d M, Y');
            })
            ->addColumn('day_type', function ($attendance) {
                if ($attendance->is_holiday) {
                    return '<span class="badge bg-label-warning"><i class="bx bx-gift"></i> '.__('Holiday').'</span>';
                } elseif ($attendance->is_weekend) {
                    return '<span class="badge bg-label-info"><i class="bx bx-calendar-week"></i> '.__('Weekend').'</span>';
                } else {
                    return '<span class="badge bg-label-secondary"><i class="bx bx-calendar"></i> '.__('Weekday').'</span>';
                }
            })
            ->editColumn('working_hours', function ($attendance) {
                return formatHours($attendance->working_hours);
            })
            ->editColumn('overtime_hours', function ($attendance) {
                $formatted = formatHours($attendance->overtime_hours);

                return '<span class="badge bg-label-success"><i class="bx bx-plus-circle"></i> '.$formatted.'</span>';
            })
            ->addColumn('shift_details', function ($attendance) {
                if ($attendance->shift) {
                    $start = Carbon::parse($attendance->shift->start_time)->format('h:i A');
                    $end = Carbon::parse($attendance->shift->end_time)->format('h:i A');

                    return '<div class="text-nowrap">'.
                        '<small class="text-muted">'.$attendance->shift->name.'</small><br>'.
                        '<small>'.$start.' - '.$end.'</small>'.
                        '</div>';
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('check_times', function ($attendance) {
                $checkIn = $attendance->check_in_time
                    ? Carbon::parse($attendance->check_in_time)->format('h:i A')
                    : '—';
                $checkOut = $attendance->check_out_time
                    ? Carbon::parse($attendance->check_out_time)->format('h:i A')
                    : '—';

                return '<div class="text-nowrap">'.
                    '<small class="text-muted">'.__('In:').'</small> '.$checkIn.'<br>'.
                    '<small class="text-muted">'.__('Out:').'</small> '.$checkOut.
                    '</div>';
            })
            ->addColumn('approval_status', function ($attendance) {
                if ($attendance->approved_by_id && $attendance->approved_at) {
                    $approvedBy = $attendance->approvedBy ? $attendance->approvedBy->getFullName() : __('N/A');
                    $approvedAt = Carbon::parse($attendance->approved_at)->format('d M, Y');

                    return '<span class="badge bg-label-success"><i class="bx bx-check-circle"></i> '.__('Approved').'</span>'.
                        '<br><small class="text-muted">'.__('By:').' '.$approvedBy.'</small>'.
                        '<br><small class="text-muted">'.$approvedAt.'</small>';
                } else {
                    return '<span class="badge bg-label-warning"><i class="bx bx-time"></i> '.__('Pending').'</span>';
                }
            })
            ->addColumn('department', function ($attendance) {
                if ($attendance->user && $attendance->user->designation && $attendance->user->designation->department) {
                    return $attendance->user->designation->department->name;
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('actions', function ($attendance) {
                $actions = [];

                // PERMISSIONS TEMPORARILY DISABLED
                // if (auth()->user()->can('hrcore.view-attendance')) {
                    $actions[] = [
                        'label' => __('View Details'),
                        'icon' => 'bx bx-show',
                        'url' => route('hrcore.attendance.show', $attendance->id),
                    ];
                // }

                // PERMISSIONS TEMPORARILY DISABLED
                // if (auth()->user()->can('hrcore.edit-attendance') && ! $attendance->approved_by_id) {
                if (! $attendance->approved_by_id) {
                    $actions[] = [
                        'label' => __('Approve Overtime'),
                        'icon' => 'bx bx-check',
                        'onclick' => "approveOvertime({$attendance->id})",
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $attendance->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['user', 'day_type', 'overtime_hours', 'shift_details', 'check_times', 'approval_status', 'department', 'actions'])
            ->make(true);
    }

    /**
     * Get overtime statistics
     */
    public function overtimeStatistics(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $departmentId = $request->input('department_id');

        // Base query
        $query = Attendance::query()
            ->where('overtime_hours', '>', 0)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);

        // Apply department filter if specified
        if ($departmentId) {
            $query->whereHas('user.designation.department', function ($q) use ($departmentId) {
                $q->where('id', $departmentId);
            });
        }

        // Total overtime hours
        $totalOvertimeHours = round($query->sum('overtime_hours'), 2);

        // Employees with overtime
        $employeesWithOvertime = $query->distinct('user_id')->count('user_id');

        // Average overtime per employee
        $averageOvertimePerEmployee = $employeesWithOvertime > 0
            ? round($totalOvertimeHours / $employeesWithOvertime, 2)
            : 0;

        // Weekend/Holiday breakdown
        $weekendOvertimeHours = (clone $query)->where('is_weekend', true)->sum('overtime_hours');
        $holidayOvertimeHours = (clone $query)->where('is_holiday', true)->sum('overtime_hours');
        $weekdayOvertimeHours = $totalOvertimeHours - $weekendOvertimeHours - $holidayOvertimeHours;

        // Overtime by department (top 5)
        $overtimeByDepartment = DB::table('attendances')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->join('designations', 'users.designation_id', '=', 'designations.id')
            ->join('departments', 'designations.department_id', '=', 'departments.id')
            ->where('attendances.overtime_hours', '>', 0)
            ->whereDate('attendances.date', '>=', $startDate)
            ->whereDate('attendances.date', '<=', $endDate)
            ->when($departmentId, function ($q) use ($departmentId) {
                return $q->where('departments.id', $departmentId);
            })
            ->whereNull('attendances.deleted_at')
            ->groupBy('departments.id', 'departments.name')
            ->select(
                'departments.name as department_name',
                DB::raw('ROUND(SUM(attendances.overtime_hours), 2) as total_overtime')
            )
            ->orderByDesc('total_overtime')
            ->limit(5)
            ->get();

        // Monthly trend (last 6 months or custom range)
        $monthlyTrend = DB::table('attendances')
            ->select(
                DB::raw('DATE_FORMAT(date, "%Y-%m") as month'),
                DB::raw('ROUND(SUM(overtime_hours), 2) as total_overtime'),
                DB::raw('COUNT(DISTINCT user_id) as employee_count')
            )
            ->where('overtime_hours', '>', 0)
            ->whereDate('date', '>=', Carbon::parse($startDate)->subMonths(5)->startOfMonth())
            ->whereDate('date', '<=', $endDate)
            ->when($departmentId, function ($q) use ($departmentId) {
                return $q->whereHas('user.designation.department', function ($query) use ($departmentId) {
                    $query->where('id', $departmentId);
                });
            })
            ->whereNull('deleted_at')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Top overtime employees (top 10)
        $topOvertimeEmployees = DB::table('attendances')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->where('attendances.overtime_hours', '>', 0)
            ->whereDate('attendances.date', '>=', $startDate)
            ->whereDate('attendances.date', '<=', $endDate)
            ->when($departmentId, function ($q) use ($departmentId) {
                return $q->whereHas('user.designation.department', function ($query) use ($departmentId) {
                    $query->where('id', $departmentId);
                });
            })
            ->whereNull('attendances.deleted_at')
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.code')
            ->select(
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.code',
                DB::raw('ROUND(SUM(attendances.overtime_hours), 2) as total_overtime'),
                DB::raw('COUNT(*) as overtime_days')
            )
            ->orderByDesc('total_overtime')
            ->limit(10)
            ->get()
            ->map(function ($employee) {
                $employee->full_name = $employee->first_name.' '.$employee->last_name;

                return $employee;
            });

        // Approved vs Pending
        $approvedOvertimeHours = (clone $query)
            ->whereNotNull('approved_by_id')
            ->sum('overtime_hours');
        $pendingOvertimeHours = $totalOvertimeHours - $approvedOvertimeHours;

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_overtime_hours' => $totalOvertimeHours,
                'employees_with_overtime' => $employeesWithOvertime,
                'average_overtime_per_employee' => $averageOvertimePerEmployee,
                'weekday_overtime_hours' => round($weekdayOvertimeHours, 2),
                'weekend_overtime_hours' => round($weekendOvertimeHours, 2),
                'holiday_overtime_hours' => round($holidayOvertimeHours, 2),
                'approved_overtime_hours' => round($approvedOvertimeHours, 2),
                'pending_overtime_hours' => round($pendingOvertimeHours, 2),
                'overtime_by_department' => $overtimeByDepartment,
                'monthly_trend' => $monthlyTrend,
                'top_overtime_employees' => $topOvertimeEmployees,
            ],
        ]);
    }

    /**
     * Approve overtime for an attendance record
     */
    public function approveOvertime(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        if ($attendance->approved_by_id) {
            return response()->json([
                'status' => 'failed',
                'data' => __('Overtime has already been approved.'),
            ], 400);
        }

        $attendance->update([
            'approved_by_id' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => __('Overtime approved successfully'),
            ],
        ]);
    }
}
