<?php

namespace App\Exports;

use App\Models\UserAvailableLeave;
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

class EncashmentEligibleSheet implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
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
            ->whereHas('leaveType', function ($q) {
                $q->where('allow_encashment', true);
            })
            ->where('available_leaves', '>', 0)
            ->orderBy('available_leaves', 'desc');

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

        // Optional: Filter by minimum encashment threshold
        if (! empty($this->filters['min_encashment_days'])) {
            $query->where('available_leaves', '>=', $this->filters['min_encashment_days']);
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
            __('Entitled'),
            __('Used'),
            __('Available'),
            __('Unused %'),
            __('Max Encashable'),
            __('Eligible Amount'),
        ];
    }

    public function map($balance): array
    {
        $totalAllowed = $balance->entitled_leaves + ($balance->carried_forward_leaves ?? 0) + ($balance->additional_leaves ?? 0);
        $unusedPercentage = $totalAllowed > 0 ? (($totalAllowed - $balance->used_leaves) / $totalAllowed * 100) : 0;

        // Calculate max encashable days based on leave type policy
        $maxEncashable = $balance->leaveType->max_encashment_days ?? 0;
        $eligibleDays = min($balance->available_leaves, $maxEncashable);

        return [
            $balance->user->code ?? '',
            $balance->user->getFullName(),
            $balance->user->department->name ?? '',
            $balance->leaveType->name,
            number_format($balance->entitled_leaves, 2),
            number_format($balance->used_leaves, 2),
            number_format($balance->available_leaves, 2),
            number_format($unusedPercentage, 2).'%',
            number_format($maxEncashable, 2),
            number_format($eligibleDays, 2),
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
        return __('Encashment Eligible');
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

                // Highlight rows with high unused percentage (>70%)
                for ($row = 2; $row <= $highestRow; $row++) {
                    $unusedPercentage = floatval(str_replace('%', '', $event->sheet->getCell('H'.$row)->getValue()));

                    if ($unusedPercentage >= 70) {
                        $event->sheet->getStyle('A'.$row.':'.$highestColumn.$row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'C6EFCE'],
                            ],
                        ]);
                    }
                }

                // Center align numeric columns
                $event->sheet->getStyle('E2:J'.$highestRow)->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
