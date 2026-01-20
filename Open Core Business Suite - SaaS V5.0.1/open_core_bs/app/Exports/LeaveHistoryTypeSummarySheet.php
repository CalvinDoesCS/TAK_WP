<?php

namespace App\Exports;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
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

class LeaveHistoryTypeSummarySheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $query = LeaveRequest::query()
            ->with(['leaveType']);

        // Apply same filters as main sheet
        if (! empty($this->filters['employee_id'])) {
            $query->where('user_id', $this->filters['employee_id']);
        }

        if (! empty($this->filters['leave_type_id'])) {
            $query->where('leave_type_id', $this->filters['leave_type_id']);
        }

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['from_date'])) {
            $query->whereDate('from_date', '>=', $this->filters['from_date']);
        }

        if (! empty($this->filters['to_date'])) {
            $query->whereDate('to_date', '<=', $this->filters['to_date']);
        }

        if (! empty($this->filters['year'])) {
            $query->whereYear('from_date', $this->filters['year']);
        }

        $leaves = $query->get();

        // Group by leave type and calculate summary
        $summary = $leaves->groupBy('leave_type_id')->map(function ($group) {
            $totalRequests = $group->count();
            $totalDays = $group->where('status', LeaveRequestStatus::APPROVED)->sum('total_days');
            $avgDays = $totalRequests > 0 ? $totalDays / $totalRequests : 0;

            return [
                $group->first()->leaveType->name,
                $totalRequests,
                number_format($totalDays, 2),
                number_format($avgDays, 2),
            ];
        });

        return $summary->values();
    }

    public function headings(): array
    {
        return [
            __('Leave Type'),
            __('Total Requests'),
            __('Total Days'),
            __('Average Days per Request'),
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
        return __('Leave Type Summary');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $highestRow = $event->sheet->getHighestRow();
                $highestColumn = $event->sheet->getHighestColumn();

                // Add borders
                $event->sheet->getStyle('A1:'.$highestColumn.$highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'DDDDDD'],
                        ],
                    ],
                ]);

                // Center align numeric columns
                $event->sheet->getStyle('B2:D'.$highestRow)->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
