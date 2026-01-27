<?php

namespace App\Exports;

use App\Models\UserAvailableLeave;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaveBalanceSheet implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = UserAvailableLeave::query()
            ->with(['user.department', 'user.designation', 'leaveType'])
            ->orderBy('user_id')
            ->orderBy('leave_type_id');

        // Apply filters
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

        return $query;
    }

    public function headings(): array
    {
        return [
            __('Employee Code'),
            __('Employee Name'),
            __('Department'),
            __('Designation'),
            __('Leave Type'),
            __('Year'),
            __('Entitled'),
            __('Carried Forward'),
            __('Additional'),
            __('Used'),
            __('Available'),
            __('CF Expiry Date'),
            __('Status'),
        ];
    }

    public function map($balance): array
    {
        return [
            $balance->user->code ?? '',
            $balance->user->getFullName(),
            $balance->user->department->name ?? '',
            $balance->user->designation->name ?? '',
            $balance->leaveType->name,
            $balance->year,
            number_format($balance->entitled_leaves, 2),
            number_format($balance->carried_forward_leaves ?? 0, 2),
            number_format($balance->additional_leaves ?? 0, 2),
            number_format($balance->used_leaves, 2),
            number_format($balance->available_leaves, 2),
            $balance->carry_forward_expiry_date ? $balance->carry_forward_expiry_date->format('Y-m-d') : '',
            $this->getExpiryStatus($balance),
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
        return __('Leave Balances');
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
                $event->sheet->getStyle('G2:K'.$highestRow)->getAlignment()->setHorizontal('center');
            },
        ];
    }

    protected function getExpiryStatus($balance): string
    {
        if (! $balance->carry_forward_expiry_date) {
            return '-';
        }

        if ($balance->carry_forward_expiry_date->isPast()) {
            return __('Expired');
        }

        if ($balance->carry_forward_expiry_date->diffInDays(now()) <= 30) {
            return __('Expiring Soon');
        }

        return __('Active');
    }
}
