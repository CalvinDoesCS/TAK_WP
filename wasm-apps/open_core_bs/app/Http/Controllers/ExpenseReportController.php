<?php

namespace App\Http\Controllers;

use App\ApiClasses\Error;
use App\DataTables\ApprovalPipelineDataTable;
use App\Models\ExpenseRequest;
use App\Models\ExpenseType;
use App\Models\Settings;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpenseReportController extends Controller
{
    /**
     * Display Expense Summary Report
     */
    public function expenseSummary()
    {
        $settings = Settings::first();
        $expenseTypes = ExpenseType::all();

        return view('expenses.reports.expense-summary', compact('settings', 'expenseTypes'));
    }

    /**
     * Get Expense Summary Report Data (AJAX)
     */
    public function expenseSummaryData(Request $request)
    {
        try {
            // Get filter parameters
            $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
            $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
            $status = $request->input('status');
            $expenseTypeId = $request->input('expense_type_id');

            // Base query for statistics
            $query = ExpenseRequest::query()
                ->whereBetween('for_date', [$dateFrom, $dateTo]);

            // Apply filters
            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            if ($expenseTypeId) {
                $query->where('expense_type_id', $expenseTypeId);
            }

            // Calculate statistics
            $statistics = $this->getExpenseSummaryStatistics($dateFrom, $dateTo, $status, $expenseTypeId);

            // Get data grouped by expense type
            $byTypeQuery = ExpenseRequest::query()
                ->with('expenseType')
                ->whereBetween('for_date', [$dateFrom, $dateTo]);

            // Apply filters
            if ($status && $status !== 'all') {
                $byTypeQuery->where('status', $status);
            }

            if ($expenseTypeId) {
                $byTypeQuery->where('expense_type_id', $expenseTypeId);
            }

            // Group by expense type
            $byType = $byTypeQuery
                ->select(
                    'expense_type_id',
                    DB::raw('SUM(amount) as total_submitted'),
                    DB::raw('SUM(approved_amount) as total_approved'),
                    DB::raw('COUNT(*) as request_count')
                )
                ->groupBy('expense_type_id')
                ->get();

            // Prepare chart data
            $chartData = [
                'donut' => [
                    'labels' => [],
                    'series' => [],
                ],
                'bar' => [
                    'categories' => [],
                    'submitted' => [],
                    'approved' => [],
                ],
            ];

            foreach ($byType as $item) {
                if ($item->expenseType) {
                    $typeName = $item->expenseType->name;

                    // Donut chart data
                    $chartData['donut']['labels'][] = $typeName;
                    $chartData['donut']['series'][] = round($item->total_submitted, 2);

                    // Bar chart data
                    $chartData['bar']['categories'][] = $typeName;
                    $chartData['bar']['submitted'][] = round($item->total_submitted, 2);
                    $chartData['bar']['approved'][] = round($item->total_approved, 2);
                }
            }

            return response()->json([
                'success' => true,
                'statistics' => $statistics,
                'chartData' => $chartData,
            ]);
        } catch (Exception $e) {
            Log::error('Expense Summary Report Data Error: '.$e->getMessage());

            return Error::response(__('Failed to load expense summary report data'));
        }
    }

    /**
     * Get Expense Summary DataTable Data (AJAX)
     */
    public function expenseSummaryTable(Request $request)
    {
        try {
            // Get filter parameters
            $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
            $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
            $status = $request->input('status');
            $expenseTypeId = $request->input('expense_type_id');

            // Base query
            $query = ExpenseRequest::query()
                ->with('expenseType')
                ->whereBetween('for_date', [$dateFrom, $dateTo]);

            // Apply filters
            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            if ($expenseTypeId) {
                $query->where('expense_type_id', $expenseTypeId);
            }

            // DataTable parameters
            $columns = [
                1 => 'expense_type_id',
                2 => 'total_submitted',
                3 => 'total_approved',
                4 => 'request_count',
                5 => 'approval_rate',
            ];

            $limit = $request->input('length', 10);
            $start = $request->input('start', 0);
            $orderColumnIndex = $request->input('order.0.column', 1);
            $order = $columns[$orderColumnIndex] ?? 'expense_type_id';
            $dir = $request->input('order.0.dir', 'asc');

            // Group by expense type and get aggregated data
            $dataQuery = clone $query;
            $dataQuery->select(
                'expense_type_id',
                DB::raw('SUM(amount) as total_submitted'),
                DB::raw('SUM(approved_amount) as total_approved'),
                DB::raw('COUNT(*) as request_count'),
                DB::raw('CASE WHEN SUM(amount) > 0 THEN (SUM(approved_amount) / SUM(amount)) * 100 ELSE 0 END as approval_rate'),
                DB::raw('AVG(amount) as avg_amount')
            )
                ->groupBy('expense_type_id');

            // Get total count before pagination
            $totalData = (clone $dataQuery)->get()->count();
            $totalFiltered = $totalData;

            // Search functionality
            if (! empty($request->input('search.value'))) {
                $search = $request->input('search.value');
                $dataQuery->whereHas('expenseType', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                });
                $totalFiltered = (clone $dataQuery)->get()->count();
            }

            // Ordering
            if ($order !== 'request_count' && $order !== 'approval_rate') {
                $dataQuery->orderBy($order, $dir);
            }

            // Get paginated results
            $results = $dataQuery->offset($start)->limit($limit)->get();

            // If ordering by request_count or approval_rate, do it in collection
            if ($order === 'request_count' || $order === 'approval_rate') {
                $results = $results->sortBy($order, SORT_REGULAR, $dir === 'desc')->values();
            }

            // Get settings for currency
            $settings = Settings::first();
            $currencySymbol = $settings->currency_symbol ?? '$';

            // Format data for DataTable
            $data = [];
            foreach ($results as $item) {
                if ($item->expenseType) {
                    $approvalRate = $item->approval_rate;

                    // Badge color based on approval rate
                    $badgeClass = 'bg-label-danger';
                    if ($approvalRate >= 80) {
                        $badgeClass = 'bg-label-success';
                    } elseif ($approvalRate >= 50) {
                        $badgeClass = 'bg-label-warning';
                    }

                    $nestedData = [
                        'expense_type' => $item->expenseType->name,
                        'total_submitted' => $currencySymbol.' '.number_format($item->total_submitted, 2),
                        'total_approved' => $currencySymbol.' '.number_format($item->total_approved, 2),
                        'request_count' => $item->request_count,
                        'approval_rate' => '<span class="badge '.$badgeClass.'">'.number_format($approvalRate, 1).'%</span>',
                        'avg_amount' => $currencySymbol.' '.number_format($item->avg_amount, 2),
                    ];

                    $data[] = $nestedData;
                }
            }

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalData,
                'recordsFiltered' => $totalFiltered,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            Log::error('Expense Summary Table Error: '.$e->getMessage());

            return response()->json([
                'draw' => 0,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    /**
     * Calculate expense summary statistics
     */
    private function getExpenseSummaryStatistics(string $dateFrom, string $dateTo, ?string $status, ?int $expenseTypeId): array
    {
        $query = ExpenseRequest::query()
            ->whereBetween('for_date', [$dateFrom, $dateTo]);

        // Apply filters
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($expenseTypeId) {
            $query->where('expense_type_id', $expenseTypeId);
        }

        // Calculate statistics
        $totalSubmitted = $query->sum('amount');
        $totalApproved = $query->sum('approved_amount');
        $totalRequests = $query->count();
        $approvalRate = $totalSubmitted > 0 ? ($totalApproved / $totalSubmitted) * 100 : 0;

        // Get settings for currency
        $settings = Settings::first();
        $currencySymbol = $settings->currency_symbol ?? '$';

        return [
            'total_submitted' => $currencySymbol.' '.number_format($totalSubmitted, 2),
            'total_submitted_raw' => $totalSubmitted,
            'total_approved' => $currencySymbol.' '.number_format($totalApproved, 2),
            'total_approved_raw' => $totalApproved,
            'approval_rate' => number_format($approvalRate, 1),
            'approval_rate_raw' => $approvalRate,
            'total_requests' => $totalRequests,
        ];
    }

    /**
     * Display the approval pipeline report page
     */
    public function approvalPipeline()
    {
        $approvers = User::whereHas('approvedExpenses')
            ->orderBy('first_name')
            ->get();

        return view('expenses.reports.approval-pipeline', compact('approvers'));
    }

    /**
     * Get approval pipeline data for DataTable
     */
    public function approvalPipelineAjax(ApprovalPipelineDataTable $dataTable)
    {
        return $dataTable->ajax();
    }

    /**
     * Get statistics for approval pipeline report
     */
    public function approvalPipelineStatistics(Request $request)
    {
        $dateFrom = $request->input('date_from', Carbon::now()->subMonth()->toDateString());
        $dateTo = $request->input('date_to', Carbon::now()->toDateString());
        $status = $request->input('status');
        $aging = $request->input('aging');
        $approverId = $request->input('approver_id');

        // Base query
        $query = ExpenseRequest::query()
            ->whereBetween('for_date', [$dateFrom, $dateTo]);

        // Apply status filter
        if ($status) {
            $query->where('status', $status);
        }

        // Apply aging filter
        if ($aging) {
            switch ($aging) {
                case 'less_7':
                    $query->whereRaw('DATEDIFF(NOW(), created_at) < 7');
                    break;
                case '7_14':
                    $query->whereRaw('DATEDIFF(NOW(), created_at) BETWEEN 7 AND 14');
                    break;
                case '14_30':
                    $query->whereRaw('DATEDIFF(NOW(), created_at) BETWEEN 14 AND 30');
                    break;
                case 'over_30':
                    $query->whereRaw('DATEDIFF(NOW(), created_at) > 30');
                    break;
            }
        }

        // Apply approver filter
        if ($approverId) {
            $query->where('approved_by_id', $approverId);
        }

        // Total pending
        $totalPending = (clone $query)->where('status', 'pending')->count();

        // Total pending amount
        $totalPendingAmount = (clone $query)->where('status', 'pending')->sum('amount');

        // Average days pending
        $avgDaysPending = ExpenseRequest::where('status', 'pending')
            ->whereBetween('for_date', [$dateFrom, $dateTo])
            ->selectRaw('AVG(DATEDIFF(NOW(), created_at)) as avg_days')
            ->value('avg_days') ?? 0;

        // Requests over 7 days old
        $over7Days = (clone $query)
            ->where('status', 'pending')
            ->whereRaw('DATEDIFF(NOW(), created_at) > 7')
            ->count();

        // Current approval rate (approved vs total processed in period)
        $totalProcessed = (clone $query)
            ->whereIn('status', ['approved', 'rejected'])
            ->count();

        $totalApproved = (clone $query)
            ->where('status', 'approved')
            ->count();

        $approvalRate = $totalProcessed > 0 ? round(($totalApproved / $totalProcessed) * 100, 1) : 0;

        // Status distribution
        $statusDistribution = ExpenseRequest::selectRaw('status, COUNT(*) as count')
            ->whereBetween('for_date', [$dateFrom, $dateTo])
            ->when($approverId, function ($q) use ($approverId) {
                $q->where('approved_by_id', $approverId);
            })
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Pending requests by approver
        $pendingByApprover = ExpenseRequest::select('approved_by_id', DB::raw('COUNT(*) as count'))
            ->where('status', 'pending')
            ->whereBetween('for_date', [$dateFrom, $dateTo])
            ->when($approverId, function ($q) use ($approverId) {
                $q->where('approved_by_id', $approverId);
            })
            ->groupBy('approved_by_id')
            ->with('approvedBy')
            ->get()
            ->map(function ($item) {
                return [
                    'approver_name' => $item->approvedBy ? $item->approvedBy->getFullName() : __('Not Assigned'),
                    'count' => $item->count,
                ];
            })
            ->toArray();

        // Average approval time (time from creation to approval)
        $avgApprovalTime = ExpenseRequest::where('status', 'approved')
            ->whereBetween('for_date', [$dateFrom, $dateTo])
            ->whereNotNull('approved_at')
            ->when($approverId, function ($q) use ($approverId) {
                $q->where('approved_by_id', $approverId);
            })
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, approved_at)) as avg_hours')
            ->value('avg_hours') ?? 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_pending' => $totalPending,
                'total_pending_amount' => number_format($totalPendingAmount, 2),
                'avg_days_pending' => round($avgDaysPending, 1),
                'over_7_days' => $over7Days,
                'approval_rate' => $approvalRate,
                'avg_approval_time_hours' => round($avgApprovalTime, 1),
                'status_distribution' => [
                    ['status' => __('Pending'), 'count' => $statusDistribution['pending'] ?? 0],
                    ['status' => __('Approved'), 'count' => $statusDistribution['approved'] ?? 0],
                    ['status' => __('Rejected'), 'count' => $statusDistribution['rejected'] ?? 0],
                    ['status' => __('Cancelled'), 'count' => $statusDistribution['cancelled'] ?? 0],
                ],
                'pending_by_approver' => $pendingByApprover,
            ],
        ]);
    }
}
