<?php

namespace App\DataTables;

use App\Models\UserAvailableLeave;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\Services\DataTable;

class LeaveExpiringBalanceDataTable extends DataTable
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
            ->addColumn('carried_forward', function ($model) {
                return '<span class="fw-medium">'.number_format($model->carried_forward_leaves ?? 0, 2).'</span>';
            })
            ->addColumn('expiry_date', function ($model) {
                if (! $model->carry_forward_expiry_date) {
                    return '<span class="text-muted">-</span>';
                }

                $daysRemaining = $model->carry_forward_expiry_date->diffInDays(now());
                $isPast = $model->carry_forward_expiry_date->isPast();

                if ($isPast) {
                    $badge = 'bg-danger';
                    $text = __('Expired');
                } elseif ($daysRemaining <= 7) {
                    $badge = 'bg-danger';
                    $text = $model->carry_forward_expiry_date->format('M d, Y')." ({$daysRemaining} ".__('days left').')';
                } elseif ($daysRemaining <= 30) {
                    $badge = 'bg-warning';
                    $text = $model->carry_forward_expiry_date->format('M d, Y')." ({$daysRemaining} ".__('days left').')';
                } else {
                    $badge = 'bg-info';
                    $text = $model->carry_forward_expiry_date->format('M d, Y');
                }

                return "<span class='badge {$badge}'>{$text}</span>";
            })
            ->addColumn('urgency', function ($model) {
                if (! $model->carry_forward_expiry_date) {
                    return '';
                }

                $daysRemaining = $model->carry_forward_expiry_date->diffInDays(now());
                $isPast = $model->carry_forward_expiry_date->isPast();

                if ($isPast) {
                    return '<span class="badge bg-danger"><i class="bx bx-error"></i> '.__('Urgent').'</span>';
                } elseif ($daysRemaining <= 7) {
                    return '<span class="badge bg-danger"><i class="bx bx-error-circle"></i> '.__('Critical').'</span>';
                } elseif ($daysRemaining <= 14) {
                    return '<span class="badge bg-warning"><i class="bx bx-info-circle"></i> '.__('High').'</span>';
                } else {
                    return '<span class="badge bg-info">'.__('Normal').'</span>';
                }
            })
            ->rawColumns(['employee', 'carried_forward', 'expiry_date', 'urgency']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(UserAvailableLeave $model): QueryBuilder
    {
        $thirtyDaysFromNow = Carbon::now()->addDays(30);

        return $model->newQuery()
            ->with(['user.department', 'leaveType'])
            ->whereNotNull('carry_forward_expiry_date')
            ->where('carry_forward_expiry_date', '<=', $thirtyDaysFromNow)
            ->where('carried_forward_leaves', '>', 0)
            ->select('users_available_leaves.*')
            ->orderByRaw('CASE
                WHEN carry_forward_expiry_date < NOW() THEN 1
                WHEN DATEDIFF(carry_forward_expiry_date, NOW()) <= 7 THEN 2
                WHEN DATEDIFF(carry_forward_expiry_date, NOW()) <= 14 THEN 3
                ELSE 4
            END')
            ->orderBy('carry_forward_expiry_date');
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('leaveExpiringBalanceTable')
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
            ['data' => 'carried_forward', 'name' => 'carried_forward_leaves', 'title' => __('CF Leaves')],
            ['data' => 'expiry_date', 'name' => 'carry_forward_expiry_date', 'title' => __('Expiry Date')],
            ['data' => 'urgency', 'name' => 'urgency', 'title' => __('Urgency'), 'orderable' => false],
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'ExpiringLeaveBalance_'.date('YmdHis');
    }
}
