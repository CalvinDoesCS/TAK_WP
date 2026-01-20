<?php

namespace App\DataTables;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\Services\DataTable;

class LeavePolicyAlertsDataTable extends DataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable(QueryBuilder $query): DataTableAbstract
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->addColumn('employee', function ($model) {
                return view('components.datatable-user', ['user' => $model->user])->render();
            })
            ->addColumn('leave_type', fn ($model) => $model->leaveType->name ?? '-')
            ->addColumn('date_range', function ($model) {
                return $model->from_date->format('M d, Y').' - '.$model->to_date->format('M d, Y');
            })
            ->addColumn('alert_type', function ($model) {
                // Check for various policy violations
                $alerts = [];

                // Check if overlapping with other leaves
                if ($model->hasOverlappingLeave()) {
                    $alerts[] = '<span class="badge bg-danger mb-1"><i class="bx bx-error"></i> '.__('Overlapping Leave').'</span>';
                }

                // Check if employee has insufficient balance
                $balance = $model->user->availableLeaves()
                    ->where('leave_type_id', $model->leave_type_id)
                    ->where('year', $model->from_date->year)
                    ->first();

                if ($balance && $balance->available_leaves < $model->total_days) {
                    $alerts[] = '<span class="badge bg-warning mb-1"><i class="bx bx-info-circle"></i> '.__('Insufficient Balance').'</span>';
                }

                // Check if leave is too far in advance (more than 90 days)
                if ($model->from_date->diffInDays(now()) > 90) {
                    $alerts[] = '<span class="badge bg-info mb-1">'.__('Far Future Leave').'</span>';
                }

                // Check if it's a very long leave (more than 15 days)
                if ($model->total_days > 15) {
                    $alerts[] = '<span class="badge bg-warning mb-1">'.__('Extended Leave').'</span>';
                }

                return implode(' ', $alerts) ?: '<span class="text-muted">-</span>';
            })
            ->addColumn('status', function ($model) {
                $statusColors = [
                    'pending' => 'bg-label-warning',
                    'approved' => 'bg-label-success',
                    'rejected' => 'bg-label-danger',
                ];

                $statusValue = is_string($model->status) ? $model->status : $model->status->value;
                $class = $statusColors[$statusValue] ?? 'bg-label-secondary';

                return "<span class='badge {$class}'>".ucfirst($statusValue).'</span>';
            })
            ->addColumn('total_days', fn ($model) => number_format($model->total_days, 1))
            ->rawColumns(['employee', 'alert_type', 'status']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(LeaveRequest $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['user.department', 'leaveType'])
            ->where(function ($query) {
                // Get leaves that might have policy violations
                $query->where('status', 'pending')
                    ->orWhere(function ($q) {
                        // Or approved leaves that are very long
                        $q->where('status', 'approved')
                            ->where('total_days', '>', 15);
                    });
            })
            ->whereDate('from_date', '>=', now())
            ->select('leave_requests.*')
            ->orderBy('from_date');
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('leavePolicyAlertsTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0)
            ->parameters([
                'dom' => 'frtip',
                'scrollX' => true,
                'paging' => true,
                'searching' => true,
                'pageLength' => 10,
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
            ['data' => 'leave_type', 'name' => 'leaveType.name', 'title' => __('Leave Type')],
            ['data' => 'date_range', 'name' => 'from_date', 'title' => __('Date Range')],
            ['data' => 'total_days', 'name' => 'total_days', 'title' => __('Days')],
            ['data' => 'alert_type', 'name' => 'alert_type', 'title' => __('Alerts'), 'orderable' => false],
            ['data' => 'status', 'name' => 'status', 'title' => __('Status')],
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'PolicyAlerts_'.date('YmdHis');
    }
}
