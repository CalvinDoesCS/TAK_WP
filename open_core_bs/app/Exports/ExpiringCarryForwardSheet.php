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

class ExpiringCarryForwardSheet implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
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
            ->whereNotNull('carry_forward_expiry_date')
            ->where('carried_forward_leaves', '>', 0)
            ->orderBy('carry_forward_expiry_date', 'asc');

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

        // Default: show expiring in next 90 days or already expired in last 30 days
        if (! empty($this->filters['days_threshold'])) {
            $daysThreshold = (int) $this->filters['days_threshold'];
        } else {
            $daysThreshold = 90;
        }

        $query->where('carry_forward_expiry_date', '<=', Carbon::now()->addDays($daysThreshold));

        return $query;
    }

    public function headings(): array
    {
        return [
            __('Employee Code'),
            __('Employee Name'),
            __('Department'),
            __('Leave Type'),
            __('CF Balance'),
            __('Expiry Date'),
            __('Days Until Expiry'),
            __('Urgency'),
        ];
    }

    public function map($balance): array
    {
        $daysUntilExpiry = $balance->carry_forward_expiry_date->diffInDays(Carbon::now(), false);
        $urgency = $this->getUrgency($daysUntilExpiry);

        return [
            $balance->user->code ?? '',
            $balance->user->getFullName(),
            $balance->user->department->name ?? '',
            $balance->leaveType->name,
            number_format($balance->carried_forward_leaves, 2),
            $balance->carry_forward_expiry_date->format('Y-m-d'),
            $daysUntilExpiry,
            $urgency,
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
        return __('Expiring Carry Forward');
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

                // Apply color coding based on urgency
                for ($row = 2; $row <= $highestRow; $row++) {
                    $urgency = $event->sheet->getCell('H'.$row)->getValue();

                    if ($urgency === __('Expired')) {
                        $event->sheet->getStyle('A'.$row.':'.$highestColumn.$row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFB3B3'],
                            ],
                        ]);
                    } elseif ($urgency === __('Urgent')) {
                        $event->sheet->getStyle('A'.$row.':'.$highestColumn.$row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFD9B3'],
                            ],
                        ]);
                    } elseif ($urgency === __('Warning')) {
                        $event->sheet->getStyle('A'.$row.':'.$highestColumn.$row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFFFB3'],
                            ],
                        ]);
                    }
                }

                // Center align numeric columns
                $event->sheet->getStyle('E2:G'.$highestRow)->getAlignment()->setHorizontal('center');
            },
        ];
    }

    protected function getUrgency($daysUntilExpiry): string
    {
        if ($daysUntilExpiry < 0) {
            return __('Expired');
        }

        if ($daysUntilExpiry <= 7) {
            return __('Urgent');
        }

        if ($daysUntilExpiry <= 30) {
            return __('Warning');
        }

        return __('Normal');
    }
}
