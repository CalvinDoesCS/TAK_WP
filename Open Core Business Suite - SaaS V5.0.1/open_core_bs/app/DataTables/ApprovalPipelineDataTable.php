<?php

namespace App\DataTables;

use App\Models\ExpenseRequest;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\Services\DataTable;

class ApprovalPipelineDataTable extends DataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable(QueryBuilder $query): DataTableAbstract
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->addColumn('request_id', function ($model) {
                return '<span class="fw-medium">#'.$model->id.'</span>';
            })
            ->addColumn('user', function ($model) {
                return view('components.datatable-user', ['user' => $model->user])->render();
            })
            ->addColumn('expense_type', function ($model) {
                return '<span class="fw-medium">'.($model->expenseType->name ?? __('N/A')).'</span>';
            })
            ->addColumn('submitted_date', function ($model) {
                return '<span class="text-muted">'.($model->created_at ? $model->created_at->format('d M Y') : __('N/A')).'</span>';
            })
            ->addColumn('amount', function ($model) {
                $settings = \App\Models\Settings::first();
                $currency = $settings->currency_symbol ?? '$';

                return '<span class="fw-medium">'.$currency.number_format($model->amount, 2).'</span>';
            })
            ->addColumn('days_pending', function ($model) {
                $days = $model->days_pending ?? 0;

                if ($days < 7) {
                    $class = 'bg-label-info';
                } elseif ($days < 14) {
                    $class = 'bg-label-warning';
                } else {
                    $class = 'bg-label-danger';
                }

                return "<span class='badge {$class}'>{$days} ".__('days').'</span>';
            })
            ->addColumn('status', function ($model) {
                $statusBadges = [
                    'pending' => '<span class="badge bg-label-warning">'.__('Pending').'</span>',
                    'approved' => '<span class="badge bg-label-success">'.__('Approved').'</span>',
                    'rejected' => '<span class="badge bg-label-danger">'.__('Rejected').'</span>',
                    'cancelled' => '<span class="badge bg-label-secondary">'.__('Cancelled').'</span>',
                ];

                return $statusBadges[$model->status] ?? '<span class="badge bg-label-secondary">'.$model->status.'</span>';
            })
            ->addColumn('approver', function ($model) {
                if ($model->approvedBy) {
                    return view('components.datatable-user', ['user' => $model->approvedBy])->render();
                }

                return '<span class="text-muted">'.__('Not Assigned').'</span>';
            })
            ->addColumn('actions', function ($model) {
                return view('components.datatable-actions', [
                    'id' => $model->id,
                    'actions' => [
                        ['label' => __('View'), 'icon' => 'bx bx-show', 'onclick' => "viewRecord({$model->id})"],
                    ],
                ])->render();
            })
            ->rawColumns(['request_id', 'user', 'expense_type', 'submitted_date', 'amount', 'days_pending', 'status', 'approver', 'actions']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(ExpenseRequest $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['user', 'expenseType', 'approvedBy'])
            ->selectRaw('expense_requests.*, DATEDIFF(NOW(), expense_requests.created_at) as days_pending');

        // Date range filter
        $dateFrom = request('date_from', now()->subMonth()->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));
        $query->whereBetween('expense_requests.for_date', [$dateFrom, $dateTo]);

        // Status filter
        if (request()->has('status') && request('status') !== '') {
            $query->where('expense_requests.status', request('status'));
        } else {
            // Default to pending if no status selected
            $query->where('expense_requests.status', 'pending');
        }

        // Aging filter
        if (request()->has('aging') && request('aging') !== '') {
            switch (request('aging')) {
                case 'less_7':
                    $query->whereRaw('DATEDIFF(NOW(), expense_requests.created_at) < 7');
                    break;
                case '7_14':
                    $query->whereRaw('DATEDIFF(NOW(), expense_requests.created_at) BETWEEN 7 AND 14');
                    break;
                case '14_30':
                    $query->whereRaw('DATEDIFF(NOW(), expense_requests.created_at) BETWEEN 14 AND 30');
                    break;
                case 'over_30':
                    $query->whereRaw('DATEDIFF(NOW(), expense_requests.created_at) > 30');
                    break;
            }
        }

        // Approver filter
        if (request()->has('approver_id') && request('approver_id') !== '') {
            $query->where('expense_requests.approved_by_id', request('approver_id'));
        }

        return $query->orderBy('expense_requests.created_at', 'desc');
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html()
    {
        return $this->builder()
            ->setTableId('approvalPipelineTable')
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
            ['data' => 'request_id', 'name' => 'id', 'title' => __('Request ID')],
            ['data' => 'user', 'name' => 'user_id', 'title' => __('Employee'), 'orderable' => false],
            ['data' => 'expense_type', 'name' => 'expense_type_id', 'title' => __('Expense Type'), 'orderable' => false],
            ['data' => 'submitted_date', 'name' => 'created_at', 'title' => __('Submitted Date')],
            ['data' => 'amount', 'name' => 'amount', 'title' => __('Amount')],
            ['data' => 'days_pending', 'name' => 'days_pending', 'title' => __('Days Pending')],
            ['data' => 'status', 'name' => 'status', 'title' => __('Status')],
            ['data' => 'approver', 'name' => 'approved_by_id', 'title' => __('Assigned Approver'), 'orderable' => false],
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
        return 'ApprovalPipelineReport_'.date('YmdHis');
    }
}
