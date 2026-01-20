<?php

namespace App\DataTables;

use App\Models\UserAvailableLeave;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\Services\DataTable;

class LeaveBalanceReportDataTable extends DataTable
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
            ->addColumn('entitled', fn ($model) => number_format($model->entitled_leaves, 2))
            ->addColumn('used', fn ($model) => number_format($model->used_leaves, 2))
            ->addColumn('available', function ($model) {
                $badgeClass = $model->available_leaves > 0 ? 'bg-label-success' : 'bg-label-danger';

                return "<span class='badge {$badgeClass}'>".number_format($model->available_leaves, 2).'</span>';
            })
            ->addColumn('carried_forward', fn ($model) => number_format($model->carried_forward_leaves ?? 0, 2))
            ->addColumn('expiry_date', function ($model) {
                if (! $model->carry_forward_expiry_date) {
                    return '<span class="text-muted">-</span>';
                }

                $isExpiring = $model->carry_forward_expiry_date->isPast() ||
                              $model->carry_forward_expiry_date->diffInDays(now()) <= 30;
                $badge = $isExpiring ? 'bg-danger' : 'bg-info';

                return "<span class='badge {$badge}'>{$model->carry_forward_expiry_date->format('M d, Y')}</span>";
            })
            ->addColumn('actions', function ($model) {
                return view('components.datatable-actions', [
                    'id' => $model->id,
                    'actions' => [
                        [
                            'label' => __('View Details'),
                            'icon' => 'bx bx-show',
                            'onclick' => "viewBalanceDetails({$model->id})",
                        ],
                    ],
                ])->render();
            })
            ->filterColumn('employee', function ($query, $keyword) {
                $query->whereHas('user', function ($q) use ($keyword) {
                    $q->where('first_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('employee_code', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['employee', 'available', 'expiry_date', 'actions']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(UserAvailableLeave $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['user.department', 'leaveType'])
            ->select('users_available_leaves.*');

        // Default to current year
        $year = request('year', date('Y'));
        $query->where('year', $year);

        // Apply role-based filtering
        if (! auth()->user()->hasRole('Super Admin|HR Manager')) {
            // Manager can see their department employees
            if (auth()->user()->hasRole('Manager')) {
                $query->whereHas('user', function ($q) {
                    $q->where('department_id', auth()->user()->department_id);
                });
            } else {
                // Regular users see only their own balance
                $query->where('user_id', auth()->id());
            }
        }

        // Apply request filters
        if (request()->has('employee_id') && request('employee_id') !== '') {
            $query->where('user_id', request('employee_id'));
        }

        if (request()->has('leave_type_id') && request('leave_type_id') !== '') {
            $query->where('leave_type_id', request('leave_type_id'));
        }

        if (request()->has('expiring_soon') && request('expiring_soon') == '1') {
            $thirtyDaysFromNow = Carbon::now()->addDays(30);
            $query->whereNotNull('carry_forward_expiry_date')
                ->where('carry_forward_expiry_date', '<=', $thirtyDaysFromNow);
        }

        if (request()->has('department_id') && request('department_id') !== '') {
            $query->whereHas('user', function ($q) {
                $q->where('department_id', request('department_id'));
            });
        }

        return $query->orderBy('user_id')->orderBy('leave_type_id');
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('leaveBalanceReportTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1)
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
            ['data' => 'leave_type', 'name' => 'leaveType.name', 'title' => __('Leave Type')],
            ['data' => 'entitled', 'name' => 'entitled_leaves', 'title' => __('Entitled')],
            ['data' => 'used', 'name' => 'used_leaves', 'title' => __('Used')],
            ['data' => 'available', 'name' => 'available_leaves', 'title' => __('Available')],
            ['data' => 'carried_forward', 'name' => 'carried_forward_leaves', 'title' => __('Carried Forward')],
            ['data' => 'expiry_date', 'name' => 'carry_forward_expiry_date', 'title' => __('Expiry Date')],
            ['data' => 'actions', 'name' => 'actions', 'title' => __('Actions'), 'orderable' => false, 'searchable' => false],
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
        return 'LeaveBalanceReport_'.date('YmdHis');
    }
}
