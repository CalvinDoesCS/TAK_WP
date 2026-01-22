<?php

namespace App\Exports;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaveHistorySheet implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = LeaveRequest::query()
            ->with(['user.department', 'user.designation', 'leaveType', 'approvedBy', 'rejectedBy', 'cancelledBy'])
            ->orderBy('created_at', 'desc');

        // Apply filters
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

        return $query;
    }

    public function headings(): array
    {
        return [
            __('Employee Code'),
            __('Employee Name'),
            __('Department'),
            __('Leave Type'),
            __('From Date'),
            __('To Date'),
            __('Total Days'),
            __('Half Day'),
            __('Status'),
            __('Requested On'),
            __('Approved By'),
            __('Approved On'),
            __('Rejected By'),
            __('Rejection Reason'),
            __('Cancelled By'),
            __('Cancellation Reason'),
            __('Emergency Contact'),
            __('Abroad Location'),
        ];
    }

    public function map($leave): array
    {
        $statusValue = $leave->status instanceof LeaveRequestStatus ? $leave->status->value : $leave->status;

        return [
            $leave->user->code ?? '',
            $leave->user->getFullName(),
            $leave->user->department->name ?? '',
            $leave->leaveType->name,
            $leave->from_date->format('Y-m-d'),
            $leave->to_date->format('Y-m-d'),
            number_format($leave->total_days, 2),
            $leave->is_half_day ? ($leave->half_day_type === 'first_half' ? __('First Half') : __('Second Half')) : '-',
            ucfirst($statusValue),
            $leave->created_at->format('Y-m-d H:i'),
            $leave->approvedBy ? $leave->approvedBy->getFullName() : '',
            $leave->approved_at ? $leave->approved_at->format('Y-m-d H:i') : '',
            $leave->rejectedBy ? $leave->rejectedBy->getFullName() : '',
            $leave->approval_notes && $statusValue === 'rejected' ? $leave->approval_notes : '',
            $leave->cancelledBy ? $leave->cancelledBy->getFullName() : '',
            $leave->cancel_reason ?? '',
            $leave->emergency_contact ?? '',
            $leave->is_abroad ? ($leave->abroad_location ?? '') : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8E8E8'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return __('Leave History');
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

                // Apply conditional formatting to status column (I)
                $statusColumn = 'I';
                for ($row = 2; $row <= $highestRow; $row++) {
                    $cellValue = $event->sheet->getCell($statusColumn.$row)->getValue();

                    if (strtolower($cellValue) === 'approved') {
                        $event->sheet->getStyle($statusColumn.$row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'C6EFCE'],
                            ],
                            'font' => ['color' => ['rgb' => '006100']],
                        ]);
                    } elseif (strtolower($cellValue) === 'rejected') {
                        $event->sheet->getStyle($statusColumn.$row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFC7CE'],
                            ],
                            'font' => ['color' => ['rgb' => '9C0006']],
                        ]);
                    } elseif (strtolower($cellValue) === 'pending') {
                        $event->sheet->getStyle($statusColumn.$row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFEB9C'],
                            ],
                            'font' => ['color' => ['rgb' => '9C6500']],
                        ]);
                    }
                }

                // Center align numeric and date columns
                $event->sheet->getStyle('E2:G'.$highestRow)->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle('I2:L'.$highestRow)->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
