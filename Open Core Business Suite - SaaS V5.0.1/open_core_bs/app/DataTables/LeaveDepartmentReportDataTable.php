<?php

namespace App\DataTables;

use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\Services\DataTable;

class LeaveDepartmentReportDataTable extends DataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable(QueryBuilder $query): DataTableAbstract
    {
        $year = request('year', date('Y'));

        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->addColumn('department', function ($model) {
                return '<span class="fw-medium">'.$model->name.'</span>';
            })
            ->addColumn('total_employees', function ($model) {
                $count = User::where('department_id', $model->id)
                    ->whereNotNull('employee_code')
                    ->count();

                return '<span class="badge bg-label-primary">'.$count.'</span>';
            })
            ->addColumn('total_leaves_taken', function ($model) use ($year) {
                $total = LeaveRequest::whereHas('user', function ($q) use ($model) {
                    $q->where('department_id', $model->id);
                })
                    ->where('status', 'approved')
                    ->whereYear('from_date', $year)
                    ->sum('total_days');

                return '<span class="fw-medium">'.number_format($total, 1).'</span>';
            })
            ->addColumn('average_per_employee', function ($model) use ($year) {
                $employeeCount = User::where('department_id', $model->id)
                    ->whereNotNull('employee_code')
                    ->count();

                if ($employeeCount == 0) {
                    return '<span class="text-muted">0.0</span>';
                }

                $total = LeaveRequest::whereHas('user', function ($q) use ($model) {
                    $q->where('department_id', $model->id);
                })
                    ->where('status', 'approved')
                    ->whereYear('from_date', $year)
                    ->sum('total_days');

                $average = $total / $employeeCount;

                return '<span class="fw-medium">'.number_format($average, 1).'</span>';
            })
            ->addColumn('utilization_rate', function ($model) use ($year) {
                // Get total entitled leaves for department
                $totalEntitled = DB::table('users_available_leaves')
                    ->join('users', 'users_available_leaves.user_id', '=', 'users.id')
                    ->where('users.department_id', $model->id)
                    ->where('users_available_leaves.year', $year)
                    ->sum('users_available_leaves.entitled_leaves');

                // Get total used leaves
                $totalUsed = DB::table('users_available_leaves')
                    ->join('users', 'users_available_leaves.user_id', '=', 'users.id')
                    ->where('users.department_id', $model->id)
                    ->where('users_available_leaves.year', $year)
                    ->sum('users_available_leaves.used_leaves');

                if ($totalEntitled == 0) {
                    return '<span class="badge bg-label-secondary">0%</span>';
                }

                $percentage = ($totalUsed / $totalEntitled) * 100;

                // Badge coloring based on utilization
                $badgeClass = 'bg-label-success';
                if ($percentage >= 80) {
                    $badgeClass = 'bg-label-danger';
                } elseif ($percentage >= 60) {
                    $badgeClass = 'bg-label-warning';
                }

                return '<span class="badge '.$badgeClass.'">'.number_format($percentage, 1).'%</span>';
            })
            ->addColumn('pending_requests', function ($model) use ($year) {
                $pending = LeaveRequest::whereHas('user', function ($q) use ($model) {
                    $q->where('department_id', $model->id);
                })
                    ->where('status', 'pending')
                    ->whereYear('from_date', $year)
                    ->count();

                $badgeClass = $pending > 0 ? 'bg-label-warning' : 'bg-label-secondary';

                return '<span class="badge '.$badgeClass.'">'.$pending.'</span>';
            })
            ->rawColumns(['department', 'total_employees', 'total_leaves_taken', 'average_per_employee', 'utilization_rate', 'pending_requests']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(Department $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->select('departments.*')
            ->whereHas('users', function ($q) {
                $q->whereNotNull('employee_code');
            });

        // Apply department filter
        if (request()->has('department_id') && request('department_id') !== '') {
            $query->where('id', request('department_id'));
        }

        return $query->orderBy('name');
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('leaveDepartmentReportTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0)
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
            ['data' => 'department', 'name' => 'name', 'title' => __('Department')],
            ['data' => 'total_employees', 'name' => 'total_employees', 'title' => __('Total Employees'), 'orderable' => false],
            ['data' => 'total_leaves_taken', 'name' => 'total_leaves_taken', 'title' => __('Total Leaves Taken'), 'orderable' => false],
            ['data' => 'average_per_employee', 'name' => 'average_per_employee', 'title' => __('Average per Employee'), 'orderable' => false],
            ['data' => 'utilization_rate', 'name' => 'utilization_rate', 'title' => __('Utilization Rate'), 'orderable' => false],
            ['data' => 'pending_requests', 'name' => 'pending_requests', 'title' => __('Pending Requests'), 'orderable' => false],
        ];
    }

    /**
     * Get buttons for export.
     */
    protected function getButtons(): array
    {
        // Check if DataImportExport addon is active
        $addonService = app(\App\Services\AddonService\IAddonService::class);
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
        return 'LeaveDepartmentReport_'.date('YmdHis');
    }
}
