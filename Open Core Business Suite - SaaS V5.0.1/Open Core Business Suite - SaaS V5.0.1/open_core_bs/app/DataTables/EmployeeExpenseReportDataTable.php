<?php

namespace App\DataTables;

use App\Models\ExpenseRequest;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\Services\DataTable;

class EmployeeExpenseReportDataTable extends DataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable(QueryBuilder $query): DataTableAbstract
    {
        return datatables()
            ->of($query)
            ->addIndexColumn()
            ->addColumn('employee', function ($model) {
                // Load the actual User model to use component methods
                $user = \App\Models\User::find($model->user_id);

                if (! $user) {
                    return '<span class="text-muted">User not found</span>';
                }

                return view('components.datatable-user', ['user' => $user])->render();
            })
            ->addColumn('total_submitted', function ($model) {
                $settings = \App\Models\Settings::first();
                $symbol = $settings->currency_symbol ?? '$';

                return '<span class="fw-semibold">'.$symbol.' '.number_format($model->total_submitted, 2).'</span>';
            })
            ->addColumn('total_approved', function ($model) {
                $settings = \App\Models\Settings::first();
                $symbol = $settings->currency_symbol ?? '$';

                return '<span class="fw-semibold text-success">'.$symbol.' '.number_format($model->total_approved ?? 0, 2).'</span>';
            })
            ->addColumn('total_requests', function ($model) {
                return '<span class="badge bg-label-primary">'.$model->total_requests.'</span>';
            })
            ->addColumn('approval_rate', function ($model) {
                $approvedCount = $model->approved_count ?? 0;
                $totalRequests = $model->total_requests ?? 1;
                $rate = $totalRequests > 0 ? ($approvedCount / $totalRequests) * 100 : 0;

                // Determine badge color based on approval rate
                $badgeClass = 'bg-label-danger';
                if ($rate >= 80) {
                    $badgeClass = 'bg-label-success';
                } elseif ($rate >= 50) {
                    $badgeClass = 'bg-label-warning';
                }

                return "<span class='badge {$badgeClass}'>".number_format($rate, 1).'%</span>';
            })
            ->addColumn('pending_count', function ($model) {
                $pendingCount = $model->pending_count ?? 0;
                if ($pendingCount > 0) {
                    return '<span class="badge bg-label-warning">'.$pendingCount.'</span>';
                }

                return '<span class="text-muted">0</span>';
            })
            ->addColumn('actions', function ($model) {
                return view('components.datatable-actions', [
                    'id' => $model->user_id,
                    'actions' => [
                        [
                            'label' => __('View Details'),
                            'icon' => 'bx bx-show',
                            'onclick' => "viewEmployeeDetails({$model->user_id})",
                        ],
                    ],
                ])->render();
            })
            ->filterColumn('employee', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('users.first_name', 'like', "%{$keyword}%")
                        ->orWhere('users.last_name', 'like', "%{$keyword}%")
                        ->orWhere('users.email', 'like', "%{$keyword}%")
                        ->orWhere('users.code', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['employee', 'total_submitted', 'total_approved', 'total_requests', 'approval_rate', 'pending_count', 'actions']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(ExpenseRequest $model): QueryBuilder
    {
        // Get date range from request or default to current month
        $dateFrom = request('date_from', now()->startOfMonth()->toDateString());
        $dateTo = request('date_to', now()->endOfMonth()->toDateString());

        $query = $model->newQuery()
            ->join('users', 'expense_requests.user_id', '=', 'users.id')
            ->leftJoin('departments', 'users.team_id', '=', 'departments.id')
            ->select([
                'expense_requests.user_id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.code as employee_code',
                'users.profile_picture',
                'departments.name as department_name',
                DB::raw('SUM(expense_requests.amount) as total_submitted'),
                DB::raw('SUM(expense_requests.approved_amount) as total_approved'),
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('SUM(CASE WHEN expense_requests.status = "approved" THEN 1 ELSE 0 END) as approved_count'),
                DB::raw('SUM(CASE WHEN expense_requests.status = "pending" THEN 1 ELSE 0 END) as pending_count'),
            ])
            ->whereBetween('expense_requests.for_date', [$dateFrom, $dateTo])
            ->groupBy('expense_requests.user_id', 'users.first_name', 'users.last_name', 'users.email', 'users.code', 'users.profile_picture', 'departments.name');

        // Apply status filter
        if (request()->filled('status')) {
            $query->where('expense_requests.status', request('status'));
        }

        // Apply employee filter
        if (request()->filled('employee_id')) {
            $query->where('expense_requests.user_id', request('employee_id'));
        }

        // Apply department filter
        if (request()->filled('department_id')) {
            $query->where('users.team_id', request('department_id'));
        }

        return $query;
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('employeeExpenseReportTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1, 'desc') // Order by total_submitted descending
            ->selectStyleSingle()
            ->parameters([
                'dom' => 'Bfrtip',
                'scrollX' => true,
                'buttons' => $this->getButtons(),
                'language' => [
                    'search' => __('Search'),
                    'processing' => __('Processing...'),
                    'lengthMenu' => __('Show _MENU_ entries'),
                    'info' => __('Showing _START_ to _END_ of _TOTAL_ entries'),
                    'infoEmpty' => __('Showing 0 to 0 of 0 entries'),
                    'emptyTable' => __('No data available'),
                    'paginate' => [
                        'first' => __('First'),
                        'last' => __('Last'),
                        'next' => __('Next'),
                        'previous' => __('Previous'),
                    ],
                ],
            ]);
    }

    /**
     * Get columns.
     */
    protected function getColumns(): array
    {
        return [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false],
            ['data' => 'employee', 'name' => 'user.first_name', 'title' => __('Employee')],
            ['data' => 'total_submitted', 'name' => 'total_submitted', 'title' => __('Total Submitted')],
            ['data' => 'total_approved', 'name' => 'total_approved', 'title' => __('Total Approved')],
            ['data' => 'total_requests', 'name' => 'total_requests', 'title' => __('Total Requests')],
            ['data' => 'approval_rate', 'name' => 'approval_rate', 'title' => __('Approval Rate'), 'orderable' => false],
            ['data' => 'pending_count', 'name' => 'pending_count', 'title' => __('Pending')],
            ['data' => 'actions', 'name' => 'actions', 'title' => __('Actions'), 'orderable' => false, 'searchable' => false],
        ];
    }

    /**
     * Get buttons for export.
     */
    protected function getButtons(): array
    {
        // Check if DataImportExport addon is active
        $addonService = app(\App\Services\AddonService\AddonService::class);
        $isExportEnabled = $addonService->isAddonEnabled('DataImportExport');

        if (! $isExportEnabled) {
            return [];
        }

        return [
            'excel',
            'csv',
            'pdf',
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'EmployeeExpenseReport_'.date('YmdHis');
    }
}
