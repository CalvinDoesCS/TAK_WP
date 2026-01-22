<?php

namespace App\Http\Controllers;

use App\Enums\LifecycleEventType;
use App\Enums\UserAccountStatus;
use App\Exports\HeadcountExport;
use App\Exports\LifecycleEventsExport;
use App\Exports\ProbationAnalysisExport;
use App\Exports\TenureExport;
use App\Exports\TurnoverExport;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EmployeeLifecycleEvent;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class EmployeeReportController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct()
    {
        // Permission check temporarily disabled
        // $this->middleware('permission:hrcore.view-employees');
    }

    /**
     * Display the headcount dashboard
     */
    public function headcount()
    {
        $departments = Department::where('status', 1)->get();
        $designations = Designation::where('status', 1)->get();

        return view('employees.reports.headcount', [
            'departments' => $departments,
            'designations' => $designations,
        ]);
    }

    /**
     * Get headcount data for charts
     */
    public function headcountData(Request $request)
    {
        try {
            // Get total active employees
            $totalActiveEmployees = User::where('status', UserAccountStatus::ACTIVE)->count();

            // Get headcount by department (Teams)
            $byDepartment = DB::table('users')
                ->join('teams', 'users.team_id', '=', 'teams.id')
                ->where('users.status', UserAccountStatus::ACTIVE)
                ->whereNull('users.deleted_at')
                ->whereNull('teams.deleted_at')
                ->groupBy('teams.id', 'teams.name')
                ->select(
                    'teams.name as department_name',
                    DB::raw('COUNT(users.id) as count')
                )
                ->orderByDesc('count')
                ->get();

            // Get headcount by designation
            $byDesignation = DB::table('users')
                ->join('designations', 'users.designation_id', '=', 'designations.id')
                ->where('users.status', UserAccountStatus::ACTIVE)
                ->whereNull('users.deleted_at')
                ->whereNull('designations.deleted_at')
                ->groupBy('designations.id', 'designations.name')
                ->select(
                    'designations.name as designation_name',
                    DB::raw('COUNT(users.id) as count')
                )
                ->orderByDesc('count')
                ->get();

            // Get headcount by employment status
            $byEmploymentStatus = DB::table('users')
                ->where('status', UserAccountStatus::ACTIVE)
                ->whereNull('deleted_at')
                ->select(
                    DB::raw('CASE
                        WHEN probation_end_date IS NOT NULL AND probation_confirmed_at IS NULL AND probation_end_date > NOW() THEN "Under Probation"
                        WHEN probation_confirmed_at IS NOT NULL THEN "Confirmed"
                        ELSE "Regular"
                    END as employment_status'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('employment_status')
                ->get();

            // Get headcount trend over last 12 months (cached for 1 hour)
            $headcountTrend = Cache::remember('employee_headcount_trend_12m', 3600, function () {
                $trend = [];
                $now = Carbon::now();

                for ($i = 11; $i >= 0; $i--) {
                    $date = $now->copy()->subMonths($i)->endOfMonth();
                    $monthKey = $date->format('Y-m');

                    // Count active employees at end of that month
                    $count = User::where('status', UserAccountStatus::ACTIVE)
                        ->where(function ($query) use ($date) {
                            $query->whereNull('date_of_joining')
                                ->orWhereDate('date_of_joining', '<=', $date);
                        })
                        ->where(function ($query) use ($date) {
                            $query->whereNull('exit_date')
                                ->orWhereDate('exit_date', '>', $date);
                        })
                        ->count();

                    $trend[] = [
                        'month' => $monthKey,
                        'month_name' => $date->format('M Y'),
                        'count' => $count,
                    ];
                }

                return $trend;
            });

            // Location data disabled - LocationManagement addon not in use
            $byLocation = [];

            // Calculate growth metrics
            $previousMonthCount = $headcountTrend[count($headcountTrend) - 2]['count'] ?? $totalActiveEmployees;
            $growthCount = $totalActiveEmployees - $previousMonthCount;
            $growthPercentage = $previousMonthCount > 0
                ? round(($growthCount / $previousMonthCount) * 100, 2)
                : 0;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_active_employees' => $totalActiveEmployees,
                    'growth_count' => $growthCount,
                    'growth_percentage' => $growthPercentage,
                    'by_department' => $byDepartment,
                    'by_designation' => $byDesignation,
                    'by_employment_status' => $byEmploymentStatus,
                    'by_location' => $byLocation,
                    'headcount_trend' => $headcountTrend,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Error loading headcount data'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the demographics dashboard
     */
    public function demographics()
    {
        return view('employees.reports.demographics');
    }

    /**
     * Get demographics data for charts
     */
    public function demographicsData(Request $request)
    {
        try {
            // Get gender distribution
            $genderDistribution = User::where('status', UserAccountStatus::ACTIVE)
                ->whereNotNull('gender')
                ->select('gender', DB::raw('COUNT(*) as count'))
                ->groupBy('gender')
                ->get()
                ->map(function ($item) {
                    return [
                        'gender' => ucfirst($item->gender),
                        'count' => $item->count,
                    ];
                });

            // Get age groups distribution
            $ageGroups = DB::table('users')
                ->where('status', UserAccountStatus::ACTIVE)
                ->whereNotNull('dob')
                ->whereNull('deleted_at')
                ->select(
                    DB::raw('CASE
                        WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 18 AND 25 THEN "18-25"
                        WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 26 AND 35 THEN "26-35"
                        WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 36 AND 45 THEN "36-45"
                        WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 46 AND 55 THEN "46-55"
                        WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) >= 56 THEN "56+"
                        ELSE "Unknown"
                    END as age_group'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('age_group')
                ->orderByRaw('FIELD(age_group, "18-25", "26-35", "36-45", "46-55", "56+", "Unknown")')
                ->get();

            // Calculate average age
            $averageAge = DB::table('users')
                ->where('status', UserAccountStatus::ACTIVE)
                ->whereNotNull('dob')
                ->whereNull('deleted_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(YEAR, dob, CURDATE())) as avg_age')
                ->value('avg_age');

            $averageAge = $averageAge ? round($averageAge, 1) : 0;

            // Calculate average tenure (time since date_of_joining)
            $averageTenure = DB::table('users')
                ->where('status', UserAccountStatus::ACTIVE)
                ->whereNotNull('date_of_joining')
                ->whereNull('deleted_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MONTH, date_of_joining, CURDATE())) as avg_tenure_months')
                ->value('avg_tenure_months');

            $averageTenure = $averageTenure ? round($averageTenure, 1) : 0;

            // Get tenure distribution
            $tenureDistribution = DB::table('users')
                ->where('status', UserAccountStatus::ACTIVE)
                ->whereNotNull('date_of_joining')
                ->whereNull('deleted_at')
                ->select(
                    DB::raw('CASE
                        WHEN TIMESTAMPDIFF(MONTH, date_of_joining, CURDATE()) < 6 THEN "0-6 months"
                        WHEN TIMESTAMPDIFF(MONTH, date_of_joining, CURDATE()) BETWEEN 6 AND 11 THEN "6-12 months"
                        WHEN TIMESTAMPDIFF(YEAR, date_of_joining, CURDATE()) BETWEEN 1 AND 2 THEN "1-2 years"
                        WHEN TIMESTAMPDIFF(YEAR, date_of_joining, CURDATE()) BETWEEN 3 AND 5 THEN "3-5 years"
                        WHEN TIMESTAMPDIFF(YEAR, date_of_joining, CURDATE()) > 5 THEN "5+ years"
                        ELSE "Unknown"
                    END as tenure_group'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('tenure_group')
                ->orderByRaw('FIELD(tenure_group, "0-6 months", "6-12 months", "1-2 years", "3-5 years", "5+ years", "Unknown")')
                ->get();

            // Get probation status distribution
            $probationStatus = DB::table('users')
                ->where('status', UserAccountStatus::ACTIVE)
                ->whereNull('deleted_at')
                ->select(
                    DB::raw('CASE
                        WHEN probation_end_date IS NOT NULL AND probation_confirmed_at IS NULL AND probation_end_date > NOW() THEN "Under Probation"
                        WHEN probation_confirmed_at IS NOT NULL THEN "Confirmed"
                        WHEN probation_end_date IS NOT NULL AND probation_end_date < NOW() AND probation_confirmed_at IS NULL THEN "Pending Confirmation"
                        ELSE "Not Applicable"
                    END as probation_status'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('probation_status')
                ->get();

            // Get total active employees
            $totalActiveEmployees = User::where('status', UserAccountStatus::ACTIVE)->count();

            // Get employees with complete profile data
            $completeProfiles = User::where('status', UserAccountStatus::ACTIVE)
                ->whereNotNull('dob')
                ->whereNotNull('gender')
                ->whereNotNull('date_of_joining')
                ->count();

            $profileCompletionRate = $totalActiveEmployees > 0
                ? round(($completeProfiles / $totalActiveEmployees) * 100, 1)
                : 0;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_active_employees' => $totalActiveEmployees,
                    'gender_distribution' => $genderDistribution,
                    'age_groups' => $ageGroups,
                    'average_age' => $averageAge,
                    'average_tenure' => $averageTenure,
                    'tenure_distribution' => $tenureDistribution,
                    'probation_status' => $probationStatus,
                    'profile_completion_rate' => $profileCompletionRate,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Error loading demographics data'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display probation analysis dashboard
     */
    public function probationAnalysis()
    {
        $pageTitle = __('Probation Analysis');
        $breadcrumbs = [
            ['name' => __('Dashboard'), 'url' => route('dashboard')],
            ['name' => __('Employee Reports'), 'url' => ''],
            ['name' => __('Probation Analysis'), 'url' => ''],
        ];

        $departments = Department::all();

        return view('employees.reports.probation-analysis', compact('pageTitle', 'breadcrumbs', 'departments'));
    }

    /**
     * Get probation analysis statistics and chart data (AJAX)
     */
    public function probationAnalysisData(Request $request)
    {
        try {
            $validated = $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'department_id' => 'nullable|exists:departments,id',
                'year' => 'nullable|integer',
            ]);

            $filters = $this->buildFilters($validated);
            $statistics = $this->getProbationStatistics($filters);
            $year = $validated['year'] ?? date('Y');
            $monthlyData = $this->getMonthlyProbationData($year, $filters);
            $departmentData = $this->getDepartmentProbationData($filters);
            $outcomeDistribution = $this->getProbationOutcomes($filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $statistics,
                    'monthly_data' => $monthlyData,
                    'department_data' => $departmentData,
                    'outcome_distribution' => $outcomeDistribution,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Probation analysis data error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch probation analysis data'),
            ], 500);
        }
    }

    /**
     * Display lifecycle events report
     */
    public function lifecycleEvents()
    {
        $pageTitle = __('Employee Lifecycle Events');
        $breadcrumbs = [
            ['name' => __('Dashboard'), 'url' => route('dashboard')],
            ['name' => __('Employee Reports'), 'url' => ''],
            ['name' => __('Lifecycle Events'), 'url' => ''],
        ];

        $eventTypes = collect(LifecycleEventType::cases())->map(function ($type) {
            return [
                'value' => $type->value,
                'label' => $type->label(),
                'category' => $type->category(),
            ];
        })->toArray();

        $categories = collect($eventTypes)
            ->pluck('category')
            ->unique()
            ->sort()
            ->values()
            ->map(fn ($category) => [
                'value' => $category,
                'label' => ucfirst($category),
            ])
            ->toArray();

        $departments = Department::all();

        return view('employees.reports.lifecycle-events', compact('pageTitle', 'breadcrumbs', 'eventTypes', 'categories', 'departments'));
    }

    /**
     * Get lifecycle events data for DataTable (AJAX)
     */
    public function lifecycleEventsData(Request $request)
    {
        try {
            $validated = $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'event_type' => 'nullable|string',
                'event_category' => 'nullable|string',
                'employee_id' => 'nullable|exists:users,id',
                'department_id' => 'nullable|exists:departments,id',
            ]);

            $query = EmployeeLifecycleEvent::with(['user', 'triggeredBy'])
                ->orderBy('event_date', 'desc');

            if (! empty($validated['date_from'])) {
                $query->where('event_date', '>=', Carbon::parse($validated['date_from'])->startOfDay());
            }

            if (! empty($validated['date_to'])) {
                $query->where('event_date', '<=', Carbon::parse($validated['date_to'])->endOfDay());
            }

            if (! empty($validated['event_type'])) {
                $query->where('event_type', $validated['event_type']);
            }

            if (! empty($validated['event_category'])) {
                $query->ofCategory($validated['event_category']);
            }

            if (! empty($validated['employee_id'])) {
                $query->where('user_id', $validated['employee_id']);
            }

            if (! empty($validated['department_id'])) {
                $userIds = User::where('team_id', $validated['department_id'])->pluck('id');
                $query->whereIn('user_id', $userIds);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('event_date', function ($event) {
                    return $event->event_date->format('d M Y, h:i A');
                })
                ->addColumn('employee', function ($event) {
                    return $event->user ? view('components.datatable-user', ['user' => $event->user])->render() : '-';
                })
                ->addColumn('event_type', function ($event) {
                    $color = $event->event_type->color();
                    $icon = $event->event_type->icon();
                    $label = $event->event_type->label();

                    return '<span class="badge bg-label-'.$color.'"><i class="bx '.$icon.' me-1"></i>'.$label.'</span>';
                })
                ->addColumn('description', function ($event) {
                    $description = $event->notes ?? '';
                    if (! $description && $event->metadata) {
                        $metadata = is_array($event->metadata) ? $event->metadata : [];
                        if (! empty($metadata)) {
                            $description = implode(', ', array_map(
                                fn ($key, $value) => ucfirst(str_replace('_', ' ', $key)).': '.$value,
                                array_keys($metadata),
                                $metadata
                            ));
                        }
                    }

                    return $description ?: '-';
                })
                ->addColumn('triggered_by', function ($event) {
                    if ($event->triggeredBy) {
                        return view('components.datatable-user', ['user' => $event->triggeredBy])->render();
                    }

                    return '<span class="text-muted small">System</span>';
                })
                ->addColumn('actions', function ($event) {
                    $eventData = [
                        'id' => $event->id,
                        'employee' => $event->user ? $event->user->getFullName() : '-',
                        'employee_code' => $event->user?->code ?? '-',
                        'event_type' => $event->event_type->label(),
                        'event_date' => $event->event_date->format('d M Y, h:i A'),
                        'notes' => $event->notes ?: '-',
                        'metadata' => $event->metadata ?? [],
                        'triggered_by' => $event->triggeredBy ? $event->triggeredBy->getFullName() : 'System',
                    ];

                    return view('components.datatable-actions', [
                        'id' => $event->id,
                        'actions' => [
                            [
                                'label' => __('View Details'),
                                'icon' => 'bx bx-show',
                                'onclick' => 'viewEventDetails(this)',
                                'data-event' => json_encode($eventData),
                            ],
                        ],
                    ])->render();
                })
                ->filterColumn('employee', function ($query, $keyword) {
                    $query->whereHas('user', function ($q) use ($keyword) {
                        $q->where(function ($subQ) use ($keyword) {
                            $subQ->where('first_name', 'like', "%{$keyword}%")
                                ->orWhere('last_name', 'like', "%{$keyword}%")
                                ->orWhere('code', 'like', "%{$keyword}%")
                                ->orWhere('email', 'like', "%{$keyword}%")
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$keyword}%"]);
                        });
                    });
                })
                ->filterColumn('event_type', function ($query, $keyword) {
                    // Search in event_type enum values
                    $query->where('event_type', 'like', "%{$keyword}%");
                })
                ->filterColumn('description', function ($query, $keyword) {
                    $query->where('notes', 'like', "%{$keyword}%");
                })
                ->filterColumn('triggered_by', function ($query, $keyword) {
                    $query->whereHas('triggeredBy', function ($q) use ($keyword) {
                        $q->where(function ($subQ) use ($keyword) {
                            $subQ->where('first_name', 'like', "%{$keyword}%")
                                ->orWhere('last_name', 'like', "%{$keyword}%")
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$keyword}%"]);
                        });
                    });
                })
                ->rawColumns(['employee', 'event_type', 'triggered_by', 'actions'])
                ->make(true);
        } catch (Exception $e) {
            Log::error('Lifecycle events data error: '.$e->getMessage());

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => __('Failed to fetch lifecycle events data'),
            ], 500);
        }
    }

    /**
     * Get lifecycle event statistics
     */
    public function lifecycleEventStatistics(Request $request)
    {
        try {
            $validated = $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);

            $query = EmployeeLifecycleEvent::query();

            if (! empty($validated['date_from'])) {
                $query->where('event_date', '>=', Carbon::parse($validated['date_from'])->startOfDay());
            }

            if (! empty($validated['date_to'])) {
                $query->where('event_date', '<=', Carbon::parse($validated['date_to'])->endOfDay());
            }

            $totalEvents = $query->count();
            $recentEvents = (clone $query)->where('event_date', '>=', now()->subDays(30))->count();

            $eventsByType = (clone $query)
                ->select('event_type', DB::raw('count(*) as count'))
                ->groupBy('event_type')
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => $item->event_type->label(),
                        'count' => $item->count,
                    ];
                })
                ->toArray();

            $eventsByCategory = [];
            $allEvents = (clone $query)->get();
            foreach ($allEvents as $event) {
                $category = $event->event_type->category();
                if (! isset($eventsByCategory[$category])) {
                    $eventsByCategory[$category] = 0;
                }
                $eventsByCategory[$category]++;
            }

            $eventsByCategoryFormatted = [];
            foreach ($eventsByCategory as $category => $count) {
                $eventsByCategoryFormatted[] = [
                    'category' => ucfirst($category),
                    'count' => $count,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_events' => $totalEvents,
                    'recent_events' => $recentEvents,
                    'events_by_type' => $eventsByType,
                    'events_by_category' => $eventsByCategoryFormatted,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Lifecycle event statistics error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch lifecycle event statistics'),
            ], 500);
        }
    }

    /**
     * Get current probation employees for DataTable
     */
    public function currentProbationData(Request $request)
    {
        try {
            $query = User::with(['team', 'designation'])
                ->whereNotNull('probation_end_date')
                ->whereNull('probation_confirmed_at')
                ->where('probation_end_date', '>=', now())
                ->orderBy('probation_end_date', 'asc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('employee', function ($user) {
                    return view('components.datatable-user', ['user' => $user])->render();
                })
                ->addColumn('department', fn ($user) => $user->team?->name ?? '-')
                ->addColumn('probation_start', function ($user) {
                    return $user->date_of_joining ? Carbon::parse($user->date_of_joining)->format('d M Y') : '-';
                })
                ->addColumn('probation_end', function ($user) {
                    return $user->probation_end_date ? Carbon::parse($user->probation_end_date)->format('d M Y') : '-';
                })
                ->addColumn('days_remaining', function ($user) {
                    if (! $user->probation_end_date) {
                        return '-';
                    }
                    $days = (int) now()->diffInDays(Carbon::parse($user->probation_end_date), false);

                    return $days > 0 ? $days.' '.__('days') : '<span class="badge bg-danger">'.__('Overdue').'</span>';
                })
                ->addColumn('is_extended', function ($user) {
                    return $user->is_probation_extended ?
                        '<span class="badge bg-label-warning">'.__('Extended').'</span>' :
                        '<span class="badge bg-label-info">'.__('Normal').'</span>';
                })
                ->filterColumn('employee', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('first_name', 'like', "%{$keyword}%")
                            ->orWhere('last_name', 'like', "%{$keyword}%")
                            ->orWhere('code', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$keyword}%"]);
                    });
                })
                ->filterColumn('department', function ($query, $keyword) {
                    $query->whereHas('team', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['employee', 'days_remaining', 'is_extended'])
                ->make(true);
        } catch (Exception $e) {
            Log::error('Current probation data error: '.$e->getMessage());

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => __('Failed to fetch probation data'),
            ], 500);
        }
    }

    /**
     * Get upcoming probation endings for DataTable
     */
    public function upcomingProbationData(Request $request)
    {
        try {
            $query = User::with(['team', 'designation'])
                ->whereNotNull('probation_end_date')
                ->whereNull('probation_confirmed_at')
                ->whereBetween('probation_end_date', [now(), now()->addDays(30)])
                ->orderBy('probation_end_date', 'asc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('employee', function ($user) {
                    return view('components.datatable-user', ['user' => $user])->render();
                })
                ->addColumn('department', fn ($user) => $user->team?->name ?? '-')
                ->addColumn('end_date', function ($user) {
                    return $user->probation_end_date ? Carbon::parse($user->probation_end_date)->format('d M Y') : '-';
                })
                ->addColumn('days_left', function ($user) {
                    if (! $user->probation_end_date) {
                        return '-';
                    }
                    $days = (int) now()->diffInDays(Carbon::parse($user->probation_end_date), false);

                    if ($days <= 7) {
                        return '<span class="badge bg-danger">'.$days.' '.__('days').'</span>';
                    } elseif ($days <= 14) {
                        return '<span class="badge bg-warning">'.$days.' '.__('days').'</span>';
                    } else {
                        return '<span class="badge bg-info">'.$days.' '.__('days').'</span>';
                    }
                })
                ->addColumn('actions', function ($user) {
                    return view('components.datatable-actions', [
                        'id' => $user->id,
                        'actions' => [
                            ['label' => __('View Profile'), 'icon' => 'bx bx-user', 'onclick' => "viewEmployee({$user->id})"],
                        ],
                    ])->render();
                })
                ->filterColumn('employee', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('first_name', 'like', "%{$keyword}%")
                            ->orWhere('last_name', 'like', "%{$keyword}%")
                            ->orWhere('code', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('department', function ($query, $keyword) {
                    $query->whereHas('team', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['employee', 'days_left', 'actions'])
                ->make(true);
        } catch (Exception $e) {
            Log::error('Upcoming probation data error: '.$e->getMessage());

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => __('Failed to fetch upcoming probation data'),
            ], 500);
        }
    }

    /**
     * Get probation statistics
     */
    protected function getProbationStatistics(array $filters): array
    {
        $cacheKey = 'probation_stats_'.md5(json_encode($filters));

        return Cache::remember($cacheKey, 300, function () {
            $currentOnProbation = User::whereNotNull('probation_end_date')
                ->whereNull('probation_confirmed_at')
                ->where('probation_end_date', '>=', now())
                ->count();

            $confirmed = EmployeeLifecycleEvent::ofType(LifecycleEventType::PROBATION_CONFIRMED)->count();
            $failed = EmployeeLifecycleEvent::ofType(LifecycleEventType::PROBATION_FAILED)->count();
            $extended = EmployeeLifecycleEvent::ofType(LifecycleEventType::PROBATION_EXTENDED)->count();

            $total = $confirmed + $failed;
            $successRate = $total > 0 ? round(($confirmed / $total) * 100, 2) : 0;
            $failureRate = $total > 0 ? round(($failed / $total) * 100, 2) : 0;
            $extensionRate = ($confirmed + $failed) > 0 ? round(($extended / ($confirmed + $failed)) * 100, 2) : 0;

            $avgDuration = User::whereNotNull('probation_confirmed_at')
                ->whereNotNull('date_of_joining')
                ->get()
                ->map(function ($user) {
                    $joining = Carbon::parse($user->date_of_joining);
                    $confirmed = Carbon::parse($user->probation_confirmed_at);

                    return $joining->diffInDays($confirmed);
                })
                ->avg();

            $upcomingEndings = User::whereNotNull('probation_end_date')
                ->whereNull('probation_confirmed_at')
                ->whereBetween('probation_end_date', [now(), now()->addDays(30)])
                ->count();

            return [
                'current_on_probation' => $currentOnProbation,
                'success_rate' => $successRate,
                'failure_rate' => $failureRate,
                'extension_rate' => $extensionRate,
                'average_duration_days' => $avgDuration ? round($avgDuration, 0) : 0,
                'upcoming_endings' => $upcomingEndings,
                'total_confirmed' => $confirmed,
                'total_failed' => $failed,
                'total_extended' => $extended,
            ];
        });
    }

    /**
     * Get monthly probation completion data
     */
    protected function getMonthlyProbationData(int $year, array $filters): array
    {
        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            $confirmed = EmployeeLifecycleEvent::ofType(LifecycleEventType::PROBATION_CONFIRMED)
                ->whereBetween('event_date', [$startDate, $endDate])
                ->count();

            $failed = EmployeeLifecycleEvent::ofType(LifecycleEventType::PROBATION_FAILED)
                ->whereBetween('event_date', [$startDate, $endDate])
                ->count();

            $extended = EmployeeLifecycleEvent::ofType(LifecycleEventType::PROBATION_EXTENDED)
                ->whereBetween('event_date', [$startDate, $endDate])
                ->count();

            $monthlyData[] = [
                'month' => $startDate->format('M'),
                'confirmed' => $confirmed,
                'failed' => $failed,
                'extended' => $extended,
            ];
        }

        return $monthlyData;
    }

    /**
     * Get probation outcomes by department
     */
    protected function getDepartmentProbationData(array $filters): array
    {
        $departments = Department::all();
        $departmentData = [];

        foreach ($departments as $department) {
            $userIds = User::where('team_id', $department->id)->pluck('id');

            if ($userIds->isEmpty()) {
                continue;
            }

            $confirmed = EmployeeLifecycleEvent::ofType(LifecycleEventType::PROBATION_CONFIRMED)
                ->whereIn('user_id', $userIds)
                ->count();

            $failed = EmployeeLifecycleEvent::ofType(LifecycleEventType::PROBATION_FAILED)
                ->whereIn('user_id', $userIds)
                ->count();

            $extended = EmployeeLifecycleEvent::ofType(LifecycleEventType::PROBATION_EXTENDED)
                ->whereIn('user_id', $userIds)
                ->count();

            if ($confirmed > 0 || $failed > 0 || $extended > 0) {
                $departmentData[] = [
                    'department' => $department->name,
                    'confirmed' => $confirmed,
                    'failed' => $failed,
                    'extended' => $extended,
                ];
            }
        }

        return $departmentData;
    }

    /**
     * Get probation outcome distribution
     */
    protected function getProbationOutcomes(array $filters): array
    {
        $confirmed = EmployeeLifecycleEvent::ofType(LifecycleEventType::PROBATION_CONFIRMED)->count();
        $failed = EmployeeLifecycleEvent::ofType(LifecycleEventType::PROBATION_FAILED)->count();
        $extended = EmployeeLifecycleEvent::ofType(LifecycleEventType::PROBATION_EXTENDED)->count();
        $ongoing = User::whereNotNull('probation_end_date')
            ->whereNull('probation_confirmed_at')
            ->where('probation_end_date', '>=', now())
            ->count();

        return [
            ['label' => __('Confirmed'), 'value' => $confirmed],
            ['label' => __('Failed'), 'value' => $failed],
            ['label' => __('Extended'), 'value' => $extended],
            ['label' => __('Ongoing'), 'value' => $ongoing],
        ];
    }

    /**
     * Build filters
     */
    protected function buildFilters(array $validated): array
    {
        $filters = [];

        if (! empty($validated['date_from'])) {
            $filters['date_from'] = $validated['date_from'];
        }

        if (! empty($validated['date_to'])) {
            $filters['date_to'] = $validated['date_to'];
        }

        if (! empty($validated['year'])) {
            $filters['year'] = $validated['year'];
        }

        if (! empty($validated['department_id'])) {
            $filters['department_id'] = $validated['department_id'];
        }

        return $filters;
    }

    /**
     * Export headcount report to Excel
     */
    public function exportHeadcount(Request $request)
    {
        $filters = $request->only(['department', 'designation', 'location', 'date_from', 'date_to']);
        $filename = 'headcount-report-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new HeadcountExport($filters), $filename);
    }

    /**
     * Export turnover analysis to Excel
     */
    public function exportTurnover(Request $request)
    {
        $filters = $request->only(['department', 'date_from', 'date_to', 'termination_type']);
        $filename = 'turnover-report-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new TurnoverExport($filters), $filename);
    }

    /**
     * Export tenure analysis to Excel
     */
    public function exportTenure(Request $request)
    {
        $filters = $request->only(['department', 'designation']);
        $filename = 'tenure-report-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new TenureExport($filters), $filename);
    }

    /**
     * Export probation analysis to Excel
     */
    public function exportProbationAnalysis(Request $request)
    {
        $filters = $request->only(['department', 'date_from', 'date_to', 'year']);
        $filename = 'probation-analysis-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new ProbationAnalysisExport($filters), $filename);
    }

    /**
     * Export lifecycle events to Excel
     */
    public function exportLifecycleEvents(Request $request)
    {
        $filters = $request->only(['user_id', 'event_type', 'category', 'date_from', 'date_to', 'department_id']);
        $filename = 'lifecycle-events-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new LifecycleEventsExport($filters), $filename);
    }

    /**
     * Display the turnover analysis dashboard
     */
    public function turnover()
    {
        $departments = Department::where('status', 1)->get();

        return view('employees.reports.turnover', [
            'departments' => $departments,
        ]);
    }

    /**
     * Get turnover analysis data
     */
    public function turnoverData(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->subMonths(12)->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $departmentId = $request->input('department_id');

        // Get termination events in date range
        $terminationsQuery = EmployeeLifecycleEvent::query()
            ->ofType(LifecycleEventType::TERMINATED)
            ->with(['user.designation.department'])
            ->betweenDates($startDate, $endDate);

        if ($departmentId) {
            $terminationsQuery->whereHas('user.designation.department', function ($q) use ($departmentId) {
                $q->where('id', $departmentId);
            });
        }

        $terminations = $terminationsQuery->get();
        $terminatedCount = $terminations->count();

        // Calculate average headcount
        $startHeadcount = User::whereDate('date_of_joining', '<=', $startDate)
            ->where(function ($q) use ($startDate) {
                $q->whereNull('relieved_at')
                    ->orWhereDate('relieved_at', '>=', $startDate);
            })
            ->count();

        $endHeadcount = User::whereDate('date_of_joining', '<=', $endDate)
            ->where(function ($q) use ($endDate) {
                $q->whereNull('relieved_at')
                    ->orWhereDate('relieved_at', '>=', $endDate);
            })
            ->count();

        $averageHeadcount = ($startHeadcount + $endHeadcount) / 2;
        $turnoverRate = $averageHeadcount > 0 ? round(($terminatedCount / $averageHeadcount) * 100, 2) : 0;

        // Monthly turnover trend (last 12 months)
        $monthlyTrend = [];
        $currentMonth = Carbon::parse($endDate)->copy();

        for ($i = 0; $i < 12; $i++) {
            $monthStart = $currentMonth->copy()->startOfMonth();
            $monthEnd = $currentMonth->copy()->endOfMonth();

            $monthTerminations = EmployeeLifecycleEvent::query()
                ->ofType(LifecycleEventType::TERMINATED)
                ->betweenDates($monthStart, $monthEnd)
                ->when($departmentId, function ($q) use ($departmentId) {
                    $q->whereHas('user.designation.department', function ($query) use ($departmentId) {
                        $query->where('id', $departmentId);
                    });
                })
                ->count();

            // Calculate headcount for this month
            $monthHeadcount = User::whereDate('date_of_joining', '<=', $monthEnd)
                ->where(function ($q) use ($monthEnd) {
                    $q->whereNull('relieved_at')
                        ->orWhereDate('relieved_at', '>=', $monthEnd);
                })
                ->when($departmentId, function ($q) use ($departmentId) {
                    $q->whereHas('designation.department', function ($query) use ($departmentId) {
                        $query->where('id', $departmentId);
                    });
                })
                ->count();

            $monthTurnoverRate = $monthHeadcount > 0 ? round(($monthTerminations / $monthHeadcount) * 100, 2) : 0;

            $monthlyTrend[] = [
                'month' => $currentMonth->format('M Y'),
                'terminations' => $monthTerminations,
                'turnover_rate' => $monthTurnoverRate,
            ];

            $currentMonth->subMonth();
        }

        $monthlyTrend = array_reverse($monthlyTrend);

        // Turnover by department
        $turnoverByDepartment = DB::table('employee_lifecycle_events')
            ->join('users', 'employee_lifecycle_events.user_id', '=', 'users.id')
            ->join('designations', 'users.designation_id', '=', 'designations.id')
            ->join('departments', 'designations.department_id', '=', 'departments.id')
            ->where('employee_lifecycle_events.event_type', LifecycleEventType::TERMINATED->value)
            ->whereDate('employee_lifecycle_events.event_date', '>=', $startDate)
            ->whereDate('employee_lifecycle_events.event_date', '<=', $endDate)
            ->when($departmentId, function ($q) use ($departmentId) {
                return $q->where('departments.id', $departmentId);
            })
            ->groupBy('departments.id', 'departments.name')
            ->select(
                'departments.name as department_name',
                DB::raw('COUNT(*) as termination_count')
            )
            ->orderByDesc('termination_count')
            ->get();

        // Turnover by termination type
        $turnoverByType = $terminations->groupBy(function ($item) {
            return $item->metadata['termination_type'] ?? 'Unknown';
        })->map(function ($items, $type) {
            return [
                'type' => ucwords(str_replace('_', ' ', $type)),
                'count' => $items->count(),
            ];
        })->values();

        // Average tenure of terminated employees
        $averageTenure = 0;
        $terminatedEmployees = $terminations->pluck('user')->filter();

        if ($terminatedEmployees->count() > 0) {
            $totalTenureMonths = $terminatedEmployees->sum(function ($user) use ($terminations) {
                if (! $user->date_of_joining) {
                    return 0;
                }

                $terminationEvent = $terminations->firstWhere('user_id', $user->id);
                $terminationDate = $terminationEvent ? $terminationEvent->event_date : now();

                return Carbon::parse($user->date_of_joining)->diffInMonths($terminationDate);
            });

            $averageTenure = round($totalTenureMonths / $terminatedEmployees->count(), 1);
        }

        // Voluntary vs Involuntary
        $voluntary = $terminations->filter(function ($event) {
            $type = $event->metadata['termination_type'] ?? '';

            return in_array($type, ['resignation', 'retirement']);
        })->count();

        $involuntary = $terminatedCount - $voluntary;

        return response()->json([
            'status' => 'success',
            'data' => [
                'overall_turnover_rate' => $turnoverRate,
                'total_terminations' => $terminatedCount,
                'average_headcount' => round($averageHeadcount, 0),
                'monthly_trend' => $monthlyTrend,
                'turnover_by_department' => $turnoverByDepartment,
                'turnover_by_type' => $turnoverByType,
                'average_tenure_months' => $averageTenure,
                'voluntary_count' => $voluntary,
                'involuntary_count' => $involuntary,
            ],
        ]);
    }

    /**
     * Get turnover records for DataTable
     */
    public function turnoverRecordsAjax(Request $request)
    {
        $query = EmployeeLifecycleEvent::query()
            ->ofType(LifecycleEventType::TERMINATED)
            ->with(['user.designation.department', 'triggeredBy']);

        // Date range filter
        if ($request->has('start_date') && $request->input('start_date')) {
            $query->whereDate('event_date', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date') && $request->input('end_date')) {
            $query->whereDate('event_date', '<=', $request->input('end_date'));
        }

        // Default to last 3 months if no dates provided
        if (! $request->has('start_date') && ! $request->has('end_date')) {
            $query->whereDate('event_date', '>=', Carbon::now()->subMonths(3)->startOfMonth());
        }

        // Department filter
        if ($request->has('department_id') && $request->input('department_id')) {
            $departmentId = $request->input('department_id');
            $query->whereHas('user.designation.department', function ($q) use ($departmentId) {
                $q->where('id', $departmentId);
            });
        }

        // Termination type filter
        if ($request->has('termination_type') && $request->input('termination_type')) {
            $type = $request->input('termination_type');
            $query->where('metadata->termination_type', $type);
        }

        return DataTables::of($query)
            ->addColumn('employee', function ($event) {
                if (! $event->user) {
                    return '<span class="text-muted">—</span>';
                }

                return view('components.datatable-user', [
                    'user' => $event->user,
                    'showCode' => true,
                    'linkRoute' => 'employees.show',
                ])->render();
            })
            ->editColumn('event_date', function ($event) {
                return Carbon::parse($event->event_date)->format('d M, Y');
            })
            ->addColumn('department', function ($event) {
                if ($event->user && $event->user->designation && $event->user->designation->department) {
                    return $event->user->designation->department->name;
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('designation', function ($event) {
                if ($event->user && $event->user->designation) {
                    return $event->user->designation->name;
                }

                return '<span class="text-muted">—</span>';
            })
            ->addColumn('termination_type', function ($event) {
                $type = $event->metadata['termination_type'] ?? 'Unknown';
                $formattedType = ucwords(str_replace('_', ' ', $type));

                $badgeClass = match ($type) {
                    'resignation', 'retirement' => 'bg-label-primary',
                    'termination' => 'bg-label-danger',
                    'contract_end' => 'bg-label-warning',
                    default => 'bg-label-secondary',
                };

                return '<span class="badge '.$badgeClass.'">'.$formattedType.'</span>';
            })
            ->addColumn('tenure', function ($event) {
                if (! $event->user || ! $event->user->date_of_joining) {
                    return '<span class="text-muted">—</span>';
                }

                $months = Carbon::parse($event->user->date_of_joining)->diffInMonths($event->event_date);
                $years = floor($months / 12);
                $remainingMonths = $months % 12;

                if ($years > 0) {
                    return $years.' '.($years > 1 ? __('years') : __('year')).
                        ($remainingMonths > 0 ? ', '.$remainingMonths.' '.($remainingMonths > 1 ? __('months') : __('month')) : '');
                }

                return $remainingMonths.' '.($remainingMonths > 1 ? __('months') : __('month'));
            })
            ->addColumn('exit_reason', function ($event) {
                return $event->metadata['exit_reason'] ?? '<span class="text-muted">—</span>';
            })
            ->addColumn('actions', function ($event) {
                $actions = [];

                if ($event->user && auth()->user()->can('employee.view')) {
                    $actions[] = [
                        'label' => __('View Profile'),
                        'icon' => 'bx bx-show',
                        'url' => route('employees.show', $event->user->id),
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $event->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['employee', 'department', 'designation', 'termination_type', 'tenure', 'exit_reason', 'actions'])
            ->make(true);
    }

    /**
     * Display the tenure analysis dashboard
     */
    public function tenure()
    {
        $departments = Department::where('status', 1)->get();
        $designations = Designation::all();

        return view('employees.reports.tenure', [
            'departments' => $departments,
            'designations' => $designations,
        ]);
    }

    /**
     * Get tenure analysis data
     */
    public function tenureData(Request $request)
    {
        $departmentId = $request->input('department_id');
        $designationId = $request->input('designation_id');

        // Active employees only
        $employeesQuery = User::query()
            ->where('status', UserAccountStatus::ACTIVE)
            ->whereNotNull('date_of_joining');

        if ($departmentId) {
            $employeesQuery->whereHas('designation.department', function ($q) use ($departmentId) {
                $q->where('id', $departmentId);
            });
        }

        if ($designationId) {
            $employeesQuery->where('designation_id', $designationId);
        }

        $employees = $employeesQuery->get();

        // Calculate average tenure
        $totalTenureMonths = $employees->sum(function ($user) {
            return Carbon::parse($user->date_of_joining)->diffInMonths(now());
        });

        $averageTenure = $employees->count() > 0 ? round($totalTenureMonths / $employees->count(), 1) : 0;

        // Average tenure by department
        $tenureByDepartment = DB::table('users')
            ->join('designations', 'users.designation_id', '=', 'designations.id')
            ->join('departments', 'designations.department_id', '=', 'departments.id')
            ->where('users.status', UserAccountStatus::ACTIVE->value)
            ->whereNotNull('users.date_of_joining')
            ->when($departmentId, function ($q) use ($departmentId) {
                return $q->where('departments.id', $departmentId);
            })
            ->whereNull('users.deleted_at')
            ->groupBy('departments.id', 'departments.name')
            ->select(
                'departments.name as department_name',
                DB::raw('COUNT(*) as employee_count'),
                DB::raw('AVG(TIMESTAMPDIFF(MONTH, users.date_of_joining, NOW())) as avg_tenure_months')
            )
            ->orderBy('departments.name')
            ->get()
            ->map(function ($item) {
                $item->avg_tenure_months = round($item->avg_tenure_months, 1);

                return $item;
            });

        // Average tenure by designation
        $tenureByDesignation = DB::table('users')
            ->join('designations', 'users.designation_id', '=', 'designations.id')
            ->join('departments', 'designations.department_id', '=', 'departments.id')
            ->where('users.status', UserAccountStatus::ACTIVE->value)
            ->whereNotNull('users.date_of_joining')
            ->when($departmentId, function ($q) use ($departmentId) {
                return $q->where('departments.id', $departmentId);
            })
            ->when($designationId, function ($q) use ($designationId) {
                return $q->where('designations.id', $designationId);
            })
            ->whereNull('users.deleted_at')
            ->groupBy('designations.id', 'designations.name')
            ->select(
                'designations.name as designation_name',
                DB::raw('COUNT(*) as employee_count'),
                DB::raw('AVG(TIMESTAMPDIFF(MONTH, users.date_of_joining, NOW())) as avg_tenure_months')
            )
            ->orderBy('designations.name')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $item->avg_tenure_months = round($item->avg_tenure_months, 1);

                return $item;
            });

        // Tenure distribution
        $tenureDistribution = [
            ['range' => '0-1 Year', 'count' => 0],
            ['range' => '1-3 Years', 'count' => 0],
            ['range' => '3-5 Years', 'count' => 0],
            ['range' => '5-10 Years', 'count' => 0],
            ['range' => '10+ Years', 'count' => 0],
        ];

        foreach ($employees as $employee) {
            $months = Carbon::parse($employee->date_of_joining)->diffInMonths(now());

            if ($months < 12) {
                $tenureDistribution[0]['count']++;
            } elseif ($months < 36) {
                $tenureDistribution[1]['count']++;
            } elseif ($months < 60) {
                $tenureDistribution[2]['count']++;
            } elseif ($months < 120) {
                $tenureDistribution[3]['count']++;
            } else {
                $tenureDistribution[4]['count']++;
            }
        }

        // Longest serving employees (top 10)
        $longestServing = $employeesQuery->get()
            ->sortBy(function ($user) {
                return Carbon::parse($user->date_of_joining)->timestamp;
            })
            ->take(10)
            ->values()
            ->map(function ($user) {
                $months = Carbon::parse($user->date_of_joining)->diffInMonths(now());

                return [
                    'id' => $user->id,
                    'employee_html' => view('components.datatable-user', ['user' => $user])->render(),
                    'department' => $user->designation?->department?->name ?? 'N/A',
                    'designation' => $user->designation?->name ?? 'N/A',
                    'date_of_joining' => Carbon::parse($user->date_of_joining)->format('d M, Y'),
                    'tenure_months' => (int) $months,
                ];
            });

        // Newest employees (last 30 days)
        $newestEmployees = User::query()
            ->where('status', UserAccountStatus::ACTIVE)
            ->whereNotNull('date_of_joining')
            ->whereDate('date_of_joining', '>=', Carbon::now()->subDays(30))
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->whereHas('designation.department', function ($query) use ($departmentId) {
                    $query->where('id', $departmentId);
                });
            })
            ->when($designationId, function ($q) use ($designationId) {
                $q->where('designation_id', $designationId);
            })
            ->orderByDesc('date_of_joining')
            ->limit(10)
            ->get()
            ->map(function ($user) {
                $days = Carbon::parse($user->date_of_joining)->diffInDays(now());

                return [
                    'id' => $user->id,
                    'employee_html' => view('components.datatable-user', ['user' => $user])->render(),
                    'department' => $user->designation?->department?->name ?? 'N/A',
                    'designation' => $user->designation?->name ?? 'N/A',
                    'date_of_joining' => Carbon::parse($user->date_of_joining)->format('d M, Y'),
                    'days_since_joining' => (int) $days,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'average_tenure_months' => $averageTenure,
                'total_employees' => $employees->count(),
                'tenure_by_department' => $tenureByDepartment,
                'tenure_by_designation' => $tenureByDesignation,
                'tenure_distribution' => $tenureDistribution,
                'longest_serving' => $longestServing,
                'newest_employees' => $newestEmployees,
            ],
        ]);
    }
}
