<?php

namespace App\Exports;

use App\Models\UserAvailableLeave;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaveBalanceSummarySheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $query = UserAvailableLeave::query()
            ->with(['leaveType']);

        // Apply same filters as main sheet
        if (! empty($this->filters['employee_id'])) {
            $query->where('user_id', $this->filters['employee_id']);
        }

        if (! empty($this->filters['leave_type_id'])) {
            $query->where('leave_type_id', $this->filters['leave_type_id']);
        }

        if (! empty($this->filters['year'])) {
            $query->where('year', $this->filters['year']);
        } else {
            $query->where('year', date('Y'));
        }

        if (! empty($this->filters['expiring_soon'])) {
            $query->where('carry_forward_expiry_date', '<=', Carbon::now()->addDays(30))
                ->where('carry_forward_expiry_date', '>=', Carbon::now())
                ->where('carried_forward_leaves', '>', 0);
        }

        $balances = $query->get();

        // Group by leave type and calculate summary
        $summary = $balances->groupBy('leave_type_id')->map(function ($group) {
            $totalEntitled = $group->sum('entitled_leaves');
            $totalUsed = $group->sum('used_leaves');
            $avgUtilization = $totalEntitled > 0 ? ($totalUsed / $totalEntitled * 100) : 0;

            return [
                $group->first()->leaveType->name,
                $group->count(),
                number_format($totalEntitled, 2),
                number_format($totalUsed, 2),
                number_format($group->sum('available_leaves'), 2),
                number_format($avgUtilization, 2).'%',
            ];
        });

        return $summary->values();
    }

    public function headings(): array
    {
        return [
            __('Leave Type'),
            __('Total Employees'),
            __('Total Entitled'),
            __('Total Used'),
            __('Total Available'),
            __('Avg Utilization %'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9E1F2'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return __('Summary');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Add borders to all cells
                $highestRow = $event->sheet->getHighestRow();
                $highestColumn = $event->sheet->getHighestColumn();
                $event->sheet->getStyle('A1:'.$highestColumn.$highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'DDDDDD'],
                        ],
                    ],
                ]);

                // Center align numeric columns
                $event->sheet->getStyle('B2:F'.$highestRow)->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
