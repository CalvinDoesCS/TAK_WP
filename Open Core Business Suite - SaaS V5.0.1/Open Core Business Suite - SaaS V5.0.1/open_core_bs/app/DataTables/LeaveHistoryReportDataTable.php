<?php

namespace App\DataTables;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\Services\DataTable;

class LeaveHistoryReportDataTable extends DataTable
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
                $fromDate = is_string($model->from_date) ? Carbon::parse($model->from_date) : $model->from_date;
                $toDate = is_string($model->to_date) ? Carbon::parse($model->to_date) : $model->to_date;

                if ($model->is_half_day) {
                    $halfDayType = $model->half_day_type === 'first_half' ? __('First Half') : __('Second Half');

                    return $fromDate->format('M d, Y')." <span class='badge bg-label-info'>{$halfDayType}</span>";
                }

                if ($fromDate->eq($toDate)) {
                    return $fromDate->format('M d, Y');
                }

                return $fromDate->format('M d, Y').' - '.$toDate->format('M d, Y');
            })
            ->addColumn('total_days', function ($model) {
                return '<span class="fw-medium">'.number_format($model->total_days, 1).' '.__('days').'</span>';
            })
            ->addColumn('status', function ($model) {
                $statusColors = [
                    'pending' => 'bg-label-warning',
                    'approved' => 'bg-label-success',
                    'rejected' => 'bg-label-danger',
                    'cancelled' => 'bg-label-secondary',
                    'cancelled_by_admin' => 'bg-label-dark',
                ];

                $statusLabels = [
                    'pending' => __('Pending'),
                    'approved' => __('Approved'),
                    'rejected' => __('Rejected'),
                    'cancelled' => __('Cancelled'),
                    'cancelled_by_admin' => __('Cancelled by Admin'),
                ];

                $statusValue = is_string($model->status) ? $model->status : $model->status->value;
                $class = $statusColors[$statusValue] ?? 'bg-label-secondary';
                $label = $statusLabels[$statusValue] ?? ucfirst($statusValue);

                return "<span class='badge {$class}'>{$label}</span>";
            })
            ->addColumn('requested_on', function ($model) {
                return $model->created_at->format('M d, Y');
            })
            ->addColumn('action_by', function ($model) {
                if ($model->approved_by_id) {
                    return view('components.datatable-user', ['user' => $model->approvedBy])->render();
                } elseif ($model->rejected_by_id) {
                    return view('components.datatable-user', ['user' => $model->rejectedBy])->render();
                } elseif ($model->cancelled_by_id) {
                    return view('components.datatable-user', ['user' => $model->cancelledBy])->render();
                }

                return '<span class="text-muted">-</span>';
            })
            ->addColumn('actions', function ($model) {
                return view('components.datatable-actions', [
                    'id' => $model->id,
                    'actions' => [
                        [
                            'label' => __('View Details'),
                            'icon' => 'bx bx-show',
                            'onclick' => "viewLeaveDetails({$model->id})",
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
            ->rawColumns(['employee', 'date_range', 'total_days', 'status', 'action_by', 'actions']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(LeaveRequest $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['user.department', 'leaveType', 'approvedBy', 'rejectedBy', 'cancelledBy'])
            ->select('leave_requests.*');

        // Apply role-based filtering
        if (! auth()->user()->hasRole('Super Admin|HR Manager')) {
            // Manager can see their department employees
            if (auth()->user()->hasRole('Manager')) {
                $query->whereHas('user', function ($q) {
                    $q->where('department_id', auth()->user()->department_id);
                });
            } else {
                // Regular users see only their own leaves
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

        if (request()->has('status') && request('status') !== '') {
            $query->where('status', request('status'));
        }

        if (request()->has('date_from') && request('date_from') !== '') {
            $query->where('from_date', '>=', request('date_from'));
        }

        if (request()->has('date_to') && request('date_to') !== '') {
            $query->where('to_date', '<=', request('date_to'));
        }

        if (request()->has('department_id') && request('department_id') !== '') {
            $query->whereHas('user', function ($q) {
                $q->where('department_id', request('department_id'));
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('leaveHistoryReportTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
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
            ['data' => 'date_range', 'name' => 'from_date', 'title' => __('Date Range')],
            ['data' => 'total_days', 'name' => 'total_days', 'title' => __('Total Days')],
            ['data' => 'status', 'name' => 'status', 'title' => __('Status')],
            ['data' => 'requested_on', 'name' => 'created_at', 'title' => __('Requested On')],
            ['data' => 'action_by', 'name' => 'approvedBy.first_name', 'title' => __('Action By'), 'orderable' => false],
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
        return 'LeaveHistoryReport_'.date('YmdHis');
    }
}
