<?php

namespace App\DataTables;

use App\Models\UserAvailableLeave;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\Services\DataTable;

class LeaveEncashmentEligibleDataTable extends DataTable
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
            ->addColumn('available', function ($model) {
                return '<span class="badge bg-label-success fw-medium">'.number_format($model->available_leaves, 2).'</span>';
            })
            ->addColumn('max_encashment', function ($model) {
                $maxEncash = $model->leaveType->max_encashment_days ?? 0;

                return '<span class="fw-medium">'.number_format($maxEncash, 2).'</span>';
            })
            ->addColumn('eligible_for_encashment', function ($model) {
                $maxEncash = $model->leaveType->max_encashment_days ?? 0;
                $eligible = min($model->available_leaves, $maxEncash);

                return '<span class="badge bg-label-info fw-medium">'.number_format($eligible, 2).' '.__('days').'</span>';
            })
            ->addColumn('status', function ($model) {
                if ($model->available_leaves >= 10) {
                    return '<span class="badge bg-label-warning">'.__('High Balance').'</span>';
                } elseif ($model->available_leaves >= 5) {
                    return '<span class="badge bg-label-info">'.__('Moderate Balance').'</span>';
                } else {
                    return '<span class="badge bg-label-secondary">'.__('Low Balance').'</span>';
                }
            })
            ->rawColumns(['employee', 'available', 'max_encashment', 'eligible_for_encashment', 'status']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(UserAvailableLeave $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['user.department', 'leaveType'])
            ->whereHas('leaveType', function ($q) {
                $q->where('allow_encashment', true);
            })
            ->where('available_leaves', '>', 0)
            ->select('users_available_leaves.*')
            ->orderBy('available_leaves', 'desc');
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('leaveEncashmentEligibleTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(3, 'desc')
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
            ['data' => 'available', 'name' => 'available_leaves', 'title' => __('Available Leaves')],
            ['data' => 'max_encashment', 'name' => 'max_encashment', 'title' => __('Max Encashment'), 'orderable' => false],
            ['data' => 'eligible_for_encashment', 'name' => 'eligible_for_encashment', 'title' => __('Eligible for Encashment'), 'orderable' => false],
            ['data' => 'status', 'name' => 'status', 'title' => __('Status'), 'orderable' => false],
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'EncashmentEligible_'.date('YmdHis');
    }
}
