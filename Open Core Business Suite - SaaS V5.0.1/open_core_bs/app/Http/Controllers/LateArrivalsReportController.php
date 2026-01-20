<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LateArrivalsReportController extends Controller
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
     * Display the late arrivals report page
     */
    public function index()
    {
        $departments = Department::orderBy('name')->get();
        $users = User::with('designation.department')
            ->orderBy('first_name')
            ->get();

        return view('attendance.late-arrivals-report', compact('departments', 'users'));
    }

    /**
     * Get late arrivals data for DataTable
     */
    public function indexAjax(Request $request)
    {
        $query = Attendance::query()
            ->with(['user.designation.department', 'shift'])
            ->where('late_hours', '>', 0);

        // Date range filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        // Department filter
        if ($request->filled('department_id')) {
            $query->whereHas('user.designation.department', function ($q) use ($request) {
                $q->where('id', $request->department_id);
            });
        }

        // User filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Minimum late minutes filter
        if ($request->filled('min_late_minutes')) {
            $minLateHours = $request->min_late_minutes / 60;
            $query->where('late_hours', '>=', $minLateHours);
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
                return Carbon::parse($attendance->date)->format('d M Y');
            })
            ->addColumn('day_of_week', function ($attendance) {
                $date = Carbon::parse($attendance->date);

                return '<span class="badge bg-label-secondary">'.$date->format('l').'</span>';
            })
            ->editColumn('check_in_time', function ($attendance) {
                if ($attendance->check_in_time) {
                    return Carbon::parse($attendance->check_in_time)->format('h:i A');
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('scheduled_time', function ($attendance) {
                if ($attendance->shift && $attendance->shift->start_time) {
                    // Extract time part only if it includes date
                    $shiftStartTime = $attendance->shift->start_time;
                    if (strpos($shiftStartTime, ' ') !== false) {
                        $shiftStartTime = explode(' ', $shiftStartTime)[1];
                    }

                    return Carbon::parse($shiftStartTime)->format('h:i A');
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('late_duration', function ($attendance) {
                $lateMinutes = round($attendance->late_hours * 60);
                $hours = floor($lateMinutes / 60);
                $minutes = $lateMinutes % 60;

                $badge = 'bg-label-warning';
                if ($lateMinutes >= 60) {
                    $badge = 'bg-label-danger';
                }

                if ($hours > 0) {
                    return '<span class="badge '.$badge.'"><i class="bx bx-time-five"></i> '.$hours.'h '.$minutes.'m</span>';
                } else {
                    return '<span class="badge '.$badge.'"><i class="bx bx-time-five"></i> '.$minutes.'m</span>';
                }
            })
            ->addColumn('late_reason', function ($attendance) {
                if ($attendance->late_reason) {
                    $reason = strlen($attendance->late_reason) > 50
                        ? substr($attendance->late_reason, 0, 50).'...'
                        : $attendance->late_reason;

                    return '<span class="text-muted" data-bs-toggle="tooltip" title="'.e($attendance->late_reason).'">'.$reason.'</span>';
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('shift', function ($attendance) {
                if ($attendance->shift) {
                    return '<span class="badge bg-label-info">'.$attendance->shift->name.'</span>';
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('department', function ($attendance) {
                if ($attendance->user && $attendance->user->designation && $attendance->user->designation->department) {
                    return $attendance->user->designation->department->name;
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('actions', function ($attendance) {
                $actions = [];

                if (auth()->user()->can('hrcore.view-attendance')) {
                    $actions[] = [
                        'label' => __('View Details'),
                        'icon' => 'bx bx-show',
                        'url' => route('hrcore.attendance.show', $attendance->id),
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $attendance->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['user', 'day_of_week', 'check_in_time', 'scheduled_time', 'late_duration', 'late_reason', 'shift', 'actions'])
            ->make(true);
    }

    /**
     * Get statistics for late arrivals
     */
    public function statistics(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->subMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $departmentId = $request->input('department_id');
        $userId = $request->input('user_id');
        $minLateMinutes = $request->input('min_late_minutes', 0);

        $minLateHours = $minLateMinutes / 60;

        // Base query for late arrivals
        $query = Attendance::where('late_hours', '>', 0)
            ->whereBetween('date', [$startDate, $endDate]);

        if ($departmentId) {
            $query->whereHas('user.designation.department', function ($q) use ($departmentId) {
                $q->where('id', $departmentId);
            });
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($minLateMinutes > 0) {
            $query->where('late_hours', '>=', $minLateHours);
        }

        // Total late instances
        $totalLateInstances = $query->count();

        // Average late minutes
        $avgLateMinutes = round($query->avg('late_hours') * 60, 0);

        // Most late employee
        $mostLateEmployee = Attendance::select('user_id', DB::raw('COUNT(*) as late_count'))
            ->where('late_hours', '>', 0)
            ->whereBetween('date', [$startDate, $endDate])
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->whereHas('user.designation.department', function ($q) use ($departmentId) {
                    $q->where('id', $departmentId);
                });
            })
            ->when($minLateMinutes > 0, function ($q) use ($minLateHours) {
                $q->where('late_hours', '>=', $minLateHours);
            })
            ->groupBy('user_id')
            ->orderByDesc('late_count')
            ->with('user')
            ->first();

        // Late arrivals by day of week
        $lateByDayOfWeek = Attendance::selectRaw('DAYOFWEEK(date) as day_of_week, COUNT(*) as count')
            ->where('late_hours', '>', 0)
            ->whereBetween('date', [$startDate, $endDate])
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->whereHas('user.designation.department', function ($q) use ($departmentId) {
                    $q->where('id', $departmentId);
                });
            })
            ->when($userId, function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->when($minLateMinutes > 0, function ($q) use ($minLateHours) {
                $q->where('late_hours', '>=', $minLateHours);
            })
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->pluck('count', 'day_of_week')
            ->toArray();

        // Map MySQL day of week (1=Sunday) to day names
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $lateByDayOfWeekFormatted = [];
        for ($i = 0; $i < 7; $i++) {
            $mysqlDay = ($i + 1) % 7 + 1; // Convert to MySQL format
            $lateByDayOfWeekFormatted[] = [
                'day' => $dayNames[$i],
                'count' => $lateByDayOfWeek[$mysqlDay] ?? 0,
            ];
        }

        // Late arrivals by department
        $lateByDepartment = Attendance::select(DB::raw('departments.name as department_name, COUNT(*) as count'))
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->join('designations', 'users.designation_id', '=', 'designations.id')
            ->join('departments', 'designations.department_id', '=', 'departments.id')
            ->where('attendances.late_hours', '>', 0)
            ->whereBetween('attendances.date', [$startDate, $endDate])
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->where('departments.id', $departmentId);
            })
            ->when($userId, function ($q) use ($userId) {
                $q->where('attendances.user_id', $userId);
            })
            ->when($minLateMinutes > 0, function ($q) use ($minLateHours) {
                $q->where('attendances.late_hours', '>=', $minLateHours);
            })
            ->groupBy('departments.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();

        // Top 10 late employees
        $topLateEmployees = Attendance::select('user_id', DB::raw('COUNT(*) as late_count'), DB::raw('SUM(late_hours) as total_late_hours'))
            ->where('late_hours', '>', 0)
            ->whereBetween('date', [$startDate, $endDate])
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->whereHas('user.designation.department', function ($q) use ($departmentId) {
                    $q->where('id', $departmentId);
                });
            })
            ->when($minLateMinutes > 0, function ($q) use ($minLateHours) {
                $q->where('late_hours', '>=', $minLateHours);
            })
            ->groupBy('user_id')
            ->orderByDesc('late_count')
            ->limit(10)
            ->with('user')
            ->get()
            ->map(function ($item) {
                return [
                    'user_name' => $item->user ? $item->user->getFullName() : 'Unknown',
                    'late_count' => $item->late_count,
                    'total_late_minutes' => round($item->total_late_hours * 60, 0),
                ];
            })
            ->toArray();

        // Trend over time (last 30 days or selected range)
        $trendData = Attendance::selectRaw('DATE(date) as date, COUNT(*) as count')
            ->where('late_hours', '>', 0)
            ->whereBetween('date', [$startDate, $endDate])
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->whereHas('user.designation.department', function ($q) use ($departmentId) {
                    $q->where('id', $departmentId);
                });
            })
            ->when($userId, function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->when($minLateMinutes > 0, function ($q) use ($minLateHours) {
                $q->where('late_hours', '>=', $minLateHours);
            })
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('Y-m-d'),
                    'count' => $item->count,
                ];
            })
            ->toArray();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_late_instances' => $totalLateInstances,
                'avg_late_minutes' => $avgLateMinutes,
                'most_late_employee' => $mostLateEmployee ? [
                    'name' => $mostLateEmployee->user ? $mostLateEmployee->user->getFullName() : 'Unknown',
                    'count' => $mostLateEmployee->late_count,
                ] : null,
                'late_by_day_of_week' => $lateByDayOfWeekFormatted,
                'late_by_department' => $lateByDepartment,
                'top_late_employees' => $topLateEmployees,
                'trend_data' => $trendData,
            ],
        ]);
    }
}
