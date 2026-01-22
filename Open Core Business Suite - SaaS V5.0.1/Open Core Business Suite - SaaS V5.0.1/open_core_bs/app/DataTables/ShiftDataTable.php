<?php

namespace App\DataTables;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\DataTableAbstract;
use Yajra\DataTables\Services\DataTable;

class ShiftDataTable extends DataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable(QueryBuilder $query): DataTableAbstract
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->addColumn('id', function ($model) {
                return $model->id;
            })
            ->addColumn('name', function ($model) {
                return '<span class="fw-medium">'.$model->name.'</span>';
            })
            ->addColumn('code', function ($model) {
                return '<span class="badge bg-label-secondary">'.$model->code.'</span>';
            })
            ->addColumn('shift_days', function ($model) {
                $daysHtml = '<div class="d-flex justify-content-start flex-wrap gap-1">';
                $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                foreach ($days as $day) {
                    $label = ucfirst(substr($day, 0, 3));
                    $class = $model->$day ? 'bg-label-success' : 'bg-label-secondary';
                    $daysHtml .= '<span class="badge '.$class.'">'.$label.'</span>';
                }
                $daysHtml .= '</div>';

                return $daysHtml;
            })
            ->addColumn('time', function ($model) {
                $start = $model->start_time ? $model->start_time->format('H:i') : 'N/A';
                $end = $model->end_time ? $model->end_time->format('H:i') : 'N/A';

                return '<div class="text-nowrap"><i class="bx bx-time me-1"></i>'.$start.' - '.$end.'</div>';
            })
            ->addColumn('status', function ($model) {
                $isChecked = $model->status->value == 'active' ? 'checked' : '';
                $statusUrl = route('shifts.toggleStatus', $model->id);

                return '<div class="d-flex justify-content-center">
                    <label class="switch mb-0">
                        <input type="checkbox" class="switch-input shift-status-toggle" data-url="'.$statusUrl.'" '.$isChecked.' />
                        <span class="switch-toggle-slider">
                            <span class="switch-on"><i class="bx bx-check"></i></span>
                            <span class="switch-off"><i class="bx bx-x"></i></span>
                        </span>
                    </label>
                </div>';
            })
            ->addColumn('actions', function ($model) {
                // Check if shift is assigned to any active users
                $isAssigned = User::where('shift_id', $model->id)->exists();

                $actions = [
                    [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'onclick' => "editShift({$model->id})",
                    ],
                ];

                if (! $isAssigned) {
                    $actions[] = [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'onclick' => "deleteShift({$model->id})",
                        'class' => 'text-danger',
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $model->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['name', 'code', 'shift_days', 'time', 'status', 'actions']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(Shift $model): QueryBuilder
    {
        return $model->newQuery()->select('shifts.*');
    }

    /**
     * Optional: Get columns.
     */
    public function getColumns(): array
    {
        return [
            'id' => ['title' => __('ID'), 'visible' => false],
            'name' => ['title' => __('Name'), 'orderable' => true],
            'code' => ['title' => __('Code'), 'orderable' => true],
            'shift_days' => ['title' => __('Working Days'), 'orderable' => false, 'searchable' => false],
            'time' => ['title' => __('Time'), 'orderable' => false],
            'status' => ['title' => __('Status'), 'orderable' => true, 'className' => 'text-center'],
            'actions' => ['title' => __('Actions'), 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'Shifts_'.date('YmdHis');
    }
}
