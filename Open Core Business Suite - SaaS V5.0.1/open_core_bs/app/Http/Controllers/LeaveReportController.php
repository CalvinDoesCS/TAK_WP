<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\UserAvailableLeave;
use App\Services\LeaveReportService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class LeaveReportController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct(
        protected LeaveReportService $reportService
    ) {
        // Permission checks temporarily disabled
        // $this->middleware('permission:hrcore.view-leave-reports')->except(['dashboard', 'getDashboardData']);
        // $this->middleware('permission:hrcore.view-leaves|hrcore.view-leave-reports')->only(['dashboard', 'getDashboardData']);
    }

    /**
     * Display the main analytics dashboard
     */
    public function dashboard()
    {
        $pageTitle = __('Leave Analytics Dashboard');
        $breadcrumbs = [
            ['name' => __('Dashboard'), 'url' => route('dashboard')],
            ['name' => __('Leave Management'), 'url' => route('hrcore.leaves.index')],
            ['name' => __('Reports Dashboard'), 'url' => ''],
        ];

        // Get leave types for filters
        $leaveTypes = LeaveType::where('status', 'active')->get();

        // Get departments for filters
        $departments = Department::all();

        return view('leave.reports.dashboard', compact('pageTitle', 'breadcrumbs', 'leaveTypes', 'departments'));
    }

    /**
     * Get dashboard statistics and chart data (AJAX)
     */
    public function getDashboardData(Request $request)
    {
        try {
            $validated = $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'department_id' => 'nullable|exists:departments,id',
                'leave_type_id' => 'nullable|exists:leave_types,id',
                'year' => 'nullable|integer',
            ]);

            // Build filters with user access control
            $filters = $this->buildFilters($validated);

            // Get dashboard statistics
            $statistics = $this->reportService->getDashboardStats($filters);

            // Get chart data
            $year = $validated['year'] ?? date('Y');
            $monthlyTrend = $this->reportService->getMonthlyTrendData($year, $filters);
            $leaveTypeDistribution = $this->reportService->getLeaveTypeDistribution($filters);
            $departmentUtilization = $this->reportService->getDepartmentUtilization($filters);
            $statusDistribution = $this->reportService->getStatusDistribution($filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $statistics,
                    'monthly_trend' => $monthlyTrend,
                    'leave_type_distribution' => $leaveTypeDistribution,
                    'department_utilization' => $departmentUtilization,
                    'status_distribution' => $statusDistribution,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Leave dashboard data error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch dashboard data'),
            ], 500);
        }
    }

    /**
     * Build filters with user access control
     */
    protected function buildFilters(array $validated): array
    {
        $user = auth()->user();
        $filters = [];

        // Get user's role
        $userRole = $user->roles->first()?->name ?? 'employee';

        // Get accessible user IDs based on role
        $userIds = $this->reportService->getAccessibleUserIds($user->id, $userRole);
        $filters['user_ids'] = $userIds;

        // Apply department filter
        if (! empty($validated['department_id'])) {
            $departmentUserIds = User::where('team_id', $validated['department_id'])->pluck('id')->toArray();
            $filters['user_ids'] = array_intersect($userIds, $departmentUserIds);
        }

        // Apply employee filter
        if (! empty($validated['employee_id'])) {
            // Check against current filtered user_ids (after department filter) or original user_ids
            $currentUserIds = $filters['user_ids'] ?? $userIds;

            // Convert employee_id to integer for comparison
            $employeeId = (int) $validated['employee_id'];

            // If specific employee requested, filter to that employee only (if accessible)
            if (in_array($employeeId, $currentUserIds)) {
                $filters['user_ids'] = [$employeeId];
            } else {
                // Employee not accessible - return empty results instead of ignoring filter
                // This prevents showing other employees when requesting a specific inaccessible employee
                $filters['user_ids'] = [-1]; // Non-existent user ID to return empty results
            }
        }

        // Apply date filters
        if (! empty($validated['date_from'])) {
            $filters['date_from'] = $validated['date_from'];
        }
        if (! empty($validated['date_to'])) {
            $filters['date_to'] = $validated['date_to'];
        }
        if (! empty($validated['year'])) {
            $filters['year'] = $validated['year'];
        }

        // Apply leave type filter
        if (! empty($validated['leave_type_id'])) {
            $filters['leave_type_id'] = $validated['leave_type_id'];
        }

        // Apply status filter (ensure it's a string value)
        if (! empty($validated['status'])) {
            $filters['status'] = (string) $validated['status'];
        }

        // Apply expiring soon filter for balance reports
        if (isset($validated['expiring_soon']) && $validated['expiring_soon'] === '1') {
            $filters['expiring_soon'] = true;
        }

        return $filters;
    }

    /**
     * Display leave balance report page
     */
    public function balanceReport()
    {
        // Get leave types for filters
        $leaveTypes = LeaveType::where('status', 'active')->get();

        // Get departments for filters
        $departments = Department::all();

        return view('leave.reports.balance', compact('leaveTypes', 'departments'));
    }

    /**
     * Get leave balance report data for DataTable (AJAX)
     */
    public function balanceReportData(Request $request)
    {
        try {
            // Build filters from request
            $filters = $this->buildFilters($request->all());

            $userIds = $filters['user_ids'] ?? null;
            $leaveTypeId = $filters['leave_type_id'] ?? null;
            $expiringSoon = $filters['expiring_soon'] ?? false;
            $year = $filters['year'] ?? date('Y');

            // Build query - DO NOT call ->get() so DataTables can handle pagination
            $query = UserAvailableLeave::query()
                ->with(['user.team', 'leaveType'])
                ->when($userIds, fn ($q) => $q->whereIn('user_id', $userIds))
                ->when($leaveTypeId, fn ($q) => $q->where('leave_type_id', $leaveTypeId))
                ->where('year', $year);

            // Apply expiring soon filter if checked
            if ($expiringSoon) {
                $query->whereNotNull('carry_forward_expiry_date')
                    ->where('carry_forward_expiry_date', '<=', \Carbon\Carbon::now()->addDays(30));
            }

            // Pass query builder to DataTables (NOT an array)
            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('employee', function ($balance) {
                    return $balance->user ? view('components.datatable-user', ['user' => $balance->user])->render() : '-';
                })
                ->editColumn('leave_type', fn ($balance) => $balance->leaveType->name ?? '-')
                ->editColumn('entitled', fn ($balance) => number_format($balance->entitled_leaves, 2))
                ->editColumn('used', fn ($balance) => number_format($balance->used_leaves, 2))
                ->editColumn('available', fn ($balance) => number_format($balance->available_leaves, 2))
                ->editColumn('carried_forward', fn ($balance) => number_format($balance->carried_forward_leaves ?? 0, 2))
                ->editColumn('expiry_date', fn ($balance) => $balance->carry_forward_expiry_date?->format('M d, Y') ?? '-')
                ->addColumn('actions', function ($balance) {
                    return view('components.datatable-actions', [
                        'id' => $balance->user_id,
                        'actions' => [
                            ['label' => __('View Details'), 'icon' => 'bx bx-show', 'onclick' => "viewBalanceDetails({$balance->user_id})"],
                        ],
                    ])->render();
                })
                ->rawColumns(['employee', 'actions'])
                ->make(true);
        } catch (Exception $e) {
            Log::error('Leave balance report data error: '.$e->getMessage());

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => __('Failed to fetch balance report data'),
            ], 500);
        }
    }

    /**
     * Display leave history report page
     */
    public function historyReport()
    {
        // Get leave types and departments for filters
        $leaveTypes = LeaveType::where('status', 'active')->get();
        $departments = Department::all();

        return view('leave.reports.history', compact('leaveTypes', 'departments'));
    }

    /**
     * Get leave history report data for DataTable (AJAX)
     */
    public function historyReportData(Request $request)
    {
        try {
            $filters = $this->buildFilters($request->all());
            $history = $this->reportService->getLeaveHistory($filters);

            return DataTables::of($history)
                ->addIndexColumn()
                ->addColumn('employee', function ($leave) {
                    $user = User::find($leave['user_id']);

                    return $user ? view('components.datatable-user', ['user' => $user])->render() : $leave['user_name'];
                })
                ->editColumn('leave_type', fn ($leave) => $leave['leave_type_name'])
                ->addColumn('date_range', fn ($leave) => $leave['from_date'].' to '.$leave['to_date'])
                ->editColumn('total_days', fn ($leave) => number_format($leave['total_days'], 1))
                ->editColumn('status', function ($leave) {
                    $statusClass = match ($leave['status']) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        'cancelled' => 'secondary',
                        default => 'secondary',
                    };

                    return '<span class="badge bg-label-'.$statusClass.'">'.ucfirst($leave['status_label']).'</span>';
                })
                ->addColumn('requested_on', fn ($leave) => \Carbon\Carbon::parse($leave['created_at'] ?? now())->format('Y-m-d'))
                ->addColumn('action_by', fn ($leave) => $leave['approved_by'] ?? $leave['rejected_by'] ?? $leave['cancelled_by'] ?? '-')
                ->addColumn('actions', function ($leave) {
                    return view('components.datatable-actions', [
                        'id' => $leave['id'],
                        'actions' => [
                            ['label' => __('View Details'), 'icon' => 'bx bx-show', 'onclick' => "viewLeaveDetails({$leave['id']})"],
                        ],
                    ])->render();
                })
                ->rawColumns(['employee', 'status', 'actions'])
                ->make(true);
        } catch (Exception $e) {
            Log::error('Leave history report data error: '.$e->getMessage());

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => __('Failed to fetch history report data'),
            ], 500);
        }
    }

    /**
     * Display department statistics report page
     */
    public function departmentReport()
    {
        // Get departments
        $departments = Department::all();

        return view('leave.reports.department', compact('departments'));
    }

    /**
     * Display compliance monitoring report page
     */
    public function complianceReport()
    {
        return view('leave.reports.compliance');
    }

    /**
     * Export leave balance report to Excel
     */
    public function exportBalance(Request $request)
    {
        try {
            $filters = $this->buildFilters($request->all());
            $export = new \App\Exports\LeaveBalanceReportExport($filters);

            return \Maatwebsite\Excel\Facades\Excel::download(
                $export,
                'leave_balance_report_'.date('Y-m-d_His').'.xlsx'
            );
        } catch (Exception $e) {
            Log::error('Leave balance export error: '.$e->getMessage());

            return redirect()->back()->with('error', __('Failed to export balance report'));
        }
    }

    /**
     * Export leave history report to Excel
     */
    public function exportHistory(Request $request)
    {
        try {
            $filters = $this->buildFilters($request->all());
            $export = new \App\Exports\LeaveHistoryReportExport($filters);

            return \Maatwebsite\Excel\Facades\Excel::download(
                $export,
                'leave_history_report_'.date('Y-m-d_His').'.xlsx'
            );
        } catch (Exception $e) {
            Log::error('Leave history export error: '.$e->getMessage());

            return redirect()->back()->with('error', __('Failed to export history report'));
        }
    }

    /**
     * Get balance report statistics (AJAX)
     */
    public function balanceStatistics(Request $request)
    {
        try {
            $filters = $this->buildFilters($request->all());

            // Add expiring soon filter if provided
            if ($request->input('expiring_soon') === '1') {
                $filters['expiring_soon'] = true;
            }

            $balances = $this->reportService->getLeaveBalances($filters);

            // Group balances by user to count unique employees
            $uniqueEmployees = array_unique(array_column($balances, 'user_id'));

            $statistics = [
                'total_employees' => count($uniqueEmployees),
                'total_entitled' => array_sum(array_column($balances, 'entitled')),
                'total_used' => array_sum(array_column($balances, 'used')),
                'total_available' => array_sum(array_column($balances, 'available')),
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (Exception $e) {
            Log::error('Leave balance statistics error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch balance statistics'),
            ], 500);
        }
    }

    /**
     * Get balance details for a specific user (AJAX)
     */
    public function balanceDetails(Request $request, User $user)
    {
        try {
            $filters = [
                'user_ids' => [$user->id],
                'year' => $request->input('year', date('Y')),
            ];

            if ($request->has('leave_type_id') && $request->input('leave_type_id')) {
                $filters['leave_type_id'] = $request->input('leave_type_id');
            }

            $balances = $this->reportService->getLeaveBalances($filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'employee_name' => $user->getFullName(),
                    'employee_code' => $user->code ?? $user->email,
                    'balances' => $balances,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Leave balance details error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch balance details'),
            ], 500);
        }
    }

    /**
     * Get history report statistics (AJAX)
     */
    public function historyStatistics(Request $request)
    {
        try {
            $filters = $this->buildFilters($request->all());
            $history = $this->reportService->getLeaveHistory($filters);

            $statistics = [
                'total_requests' => count($history),
                'approved_count' => count(array_filter($history, fn ($item) => $item['status'] === \App\Enums\LeaveRequestStatus::APPROVED->value)),
                'pending_count' => count(array_filter($history, fn ($item) => $item['status'] === \App\Enums\LeaveRequestStatus::PENDING->value)),
                'rejected_count' => count(array_filter($history, fn ($item) => $item['status'] === \App\Enums\LeaveRequestStatus::REJECTED->value)),
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (Exception $e) {
            Log::error('Leave history statistics error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch history statistics'),
            ], 500);
        }
    }

    /**
     * Get compliance report statistics (AJAX)
     */
    public function complianceStatistics(Request $request)
    {
        try {
            $filters = $this->buildFilters($request->all());
            $complianceData = $this->reportService->getComplianceData($filters);

            $statistics = [
                'expiring_count' => $complianceData['expiring_leaves_count'] ?? 0,
                'encashment_eligible' => $complianceData['encashment_eligible_count'] ?? 0,
                'policy_alerts' => $complianceData['high_unused_balance_count'] ?? 0,
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (Exception $e) {
            Log::error('Leave compliance statistics error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch compliance statistics'),
            ], 500);
        }
    }

    /**
     * Get department report data for DataTable (AJAX)
     */
    public function departmentReportData(Request $request)
    {
        try {
            $filters = $this->buildFilters($request->all());
            $departmentStats = $this->reportService->getDepartmentStatistics($filters);

            return DataTables::of($departmentStats)
                ->addIndexColumn()
                ->editColumn('department', fn ($stat) => $stat['department_name'])
                ->editColumn('total_employees', fn ($stat) => $stat['employee_count'] ?? 0)
                ->editColumn('total_leaves_taken', fn ($stat) => number_format($stat['total_used'] ?? 0, 2))
                ->editColumn('average_per_employee', fn ($stat) => number_format($stat['avg_per_employee'] ?? 0, 2))
                ->editColumn('utilization_rate', fn ($stat) => number_format($stat['utilization_rate'] ?? 0, 1).'%')
                ->editColumn('pending_requests', fn ($stat) => 0) // Not calculated in service yet
                ->make(true);
        } catch (Exception $e) {
            Log::error('Department report data error: '.$e->getMessage());

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => __('Failed to fetch department report data'),
            ], 500);
        }
    }

    /**
     * Get department chart data (AJAX)
     */
    public function departmentChartData(Request $request)
    {
        try {
            $filters = $this->buildFilters($request->all());
            $departmentStats = $this->reportService->getDepartmentStatistics($filters);

            $departments = [];
            $leavesTaken = [];
            $averagePerEmployee = [];

            foreach ($departmentStats as $stat) {
                $departments[] = $stat['department_name'];
                $leavesTaken[] = (float) ($stat['total_used'] ?? 0);
                $averagePerEmployee[] = (float) ($stat['avg_per_employee'] ?? 0);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'departments' => $departments,
                    'leaves_taken' => $leavesTaken,
                    'average_per_employee' => $averagePerEmployee,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Department chart data error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to fetch chart data'),
            ], 500);
        }
    }

    /**
     * Get expiring balance data for DataTable (AJAX)
     */
    public function complianceExpiringData(Request $request)
    {
        try {
            $filters = $this->buildFilters($request->all());
            $complianceData = $this->reportService->getComplianceData($filters);
            $expiringLeaves = $complianceData['expiring_leaves'] ?? [];

            return DataTables::of($expiringLeaves)
                ->addIndexColumn()
                ->addColumn('employee', function ($balance) {
                    $user = User::find($balance['user_id']);

                    return $user ? view('components.datatable-user', ['user' => $user])->render() : $balance['user_name'];
                })
                ->addColumn('leave_type', fn ($balance) => $balance['leave_type_name'])
                ->addColumn('cf_leaves', fn ($balance) => number_format($balance['carried_forward_leaves'], 2))
                ->addColumn('expiry_date', fn ($balance) => \Carbon\Carbon::parse($balance['expiry_date'])->format('M d, Y'))
                ->addColumn('urgency', function ($balance) {
                    $daysUntilExpiry = abs($balance['days_until_expiry']);

                    return $daysUntilExpiry <= 7 ? '<span class="badge bg-danger">Critical</span>' :
                          ($daysUntilExpiry <= 14 ? '<span class="badge bg-warning">High</span>' :
                           '<span class="badge bg-info">Medium</span>');
                })
                ->rawColumns(['employee', 'urgency'])
                ->make(true);
        } catch (Exception $e) {
            Log::error('Compliance expiring data error: '.$e->getMessage());

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => __('Failed to fetch expiring balance data'),
            ], 500);
        }
    }

    /**
     * Get encashment eligible data for DataTable (AJAX)
     */
    public function complianceEncashmentData(Request $request)
    {
        try {
            $filters = $this->buildFilters($request->all());
            $complianceData = $this->reportService->getComplianceData($filters);
            $encashmentEligible = $complianceData['encashment_eligible'] ?? [];

            return DataTables::of($encashmentEligible)
                ->addIndexColumn()
                ->addColumn('employee', function ($record) {
                    $user = User::find($record['user_id']);

                    return $user ? view('components.datatable-user', ['user' => $user])->render() : $record['user_name'];
                })
                ->addColumn('leave_type', fn ($record) => $record['leave_type_name'])
                ->addColumn('available_leaves', fn ($record) => number_format($record['available_leaves'], 2))
                ->addColumn('max_encashment', fn ($record) => number_format($record['max_encashment_days'], 2))
                ->addColumn('eligible_for_encashment', fn ($record) => number_format($record['max_encashment_days'], 2))
                ->addColumn('status', fn ($record) => '<span class="badge bg-success">Eligible</span>')
                ->rawColumns(['employee', 'status'])
                ->make(true);
        } catch (Exception $e) {
            Log::error('Compliance encashment data error: '.$e->getMessage());

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => __('Failed to fetch encashment data'),
            ], 500);
        }
    }

    /**
     * Get policy alerts data for DataTable (AJAX)
     */
    public function complianceAlertsData(Request $request)
    {
        try {
            $filters = $this->buildFilters($request->all());
            $complianceData = $this->reportService->getComplianceData($filters);
            $policyAlerts = $complianceData['high_unused_balance'] ?? [];

            return DataTables::of($policyAlerts)
                ->addIndexColumn()
                ->addColumn('employee', function ($alert) {
                    $user = User::find($alert['user_id']);

                    return $user ? view('components.datatable-user', ['user' => $user])->render() : $alert['user_name'];
                })
                ->addColumn('leave_type', fn ($alert) => $alert['leave_type_name'])
                ->addColumn('date_range', fn ($alert) => 'N/A')
                ->addColumn('days', fn ($alert) => number_format($alert['available_leaves'], 2))
                ->addColumn('alerts', fn ($alert) => '<span class="badge bg-warning me-1">High Unused Balance ('.number_format(100 - $alert['utilization_rate'], 1).'% unused)</span>')
                ->addColumn('status', fn ($alert) => '<span class="badge bg-label-warning">Needs Attention</span>')
                ->rawColumns(['employee', 'alerts', 'status'])
                ->make(true);
        } catch (Exception $e) {
            Log::error('Compliance alerts data error: '.$e->getMessage());

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => __('Failed to fetch alerts data'),
            ], 500);
        }
    }

    /**
     * Get status badge color
     */
    private function getStatusBadgeColor(string $status): string
    {
        return match ($status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }
}
