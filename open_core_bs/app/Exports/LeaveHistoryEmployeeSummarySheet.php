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

class LeaveHistoryEmployeeSummarySheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $query = LeaveRequest::query()
            ->with(['user']);

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

        // Group by employee and calculate summary
        $summary = $leaves->groupBy('user_id')->map(function ($group) {
            $totalRequests = $group->count();
            $approved = $group->where('status', LeaveRequestStatus::APPROVED)->count();
            $rejected = $group->where('status', LeaveRequestStatus::REJECTED)->count();
            $cancelled = $group->where('status', LeaveRequestStatus::CANCELLED)->count();
            $totalDays = $group->where('status', LeaveRequestStatus::APPROVED)->sum('total_days');

            return [
                $group->first()->user->code ?? '',
                $group->first()->user->getFullName(),
                $totalRequests,
                $approved,
                $rejected,
                $cancelled,
                number_format($totalDays, 2),
            ];
        });

        return $summary->values();
    }

    public function headings(): array
    {
        return [
            __('Employee Code'),
            __('Employee Name'),
            __('Total Requests'),
            __('Approved'),
            __('Rejected'),
            __('Cancelled'),
            __('Total Days Taken'),
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
        return __('Employee Summary');
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
                $event->sheet->getStyle('C2:G'.$highestRow)->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
