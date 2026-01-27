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

class PolicyAlertsSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $alerts = collect();

        $query = UserAvailableLeave::query()
            ->with(['user.department', 'leaveType']);

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

        $balances = $query->get();

        foreach ($balances as $balance) {
            // Alert 1: Expiring carry forward within 30 days
            if ($balance->carry_forward_expiry_date &&
                $balance->carried_forward_leaves > 0 &&
                $balance->carry_forward_expiry_date->diffInDays(Carbon::now(), false) <= 30 &&
                $balance->carry_forward_expiry_date->isFuture()) {
                $alerts->push([
                    $balance->user->code ?? '',
                    $balance->user->getFullName(),
                    __('Expiring CF'),
                    __(':days days of carry forward leave expiring on :date', [
                        'days' => number_format($balance->carried_forward_leaves, 2),
                        'date' => $balance->carry_forward_expiry_date->format('Y-m-d'),
                    ]),
                    $balance->carry_forward_expiry_date->format('Y-m-d'),
                    __('Warning'),
                ]);
            }

            // Alert 2: Expired carry forward (not yet forfeited)
            if ($balance->carry_forward_expiry_date &&
                $balance->carried_forward_leaves > 0 &&
                $balance->carry_forward_expiry_date->isPast()) {
                $alerts->push([
                    $balance->user->code ?? '',
                    $balance->user->getFullName(),
                    __('Expired CF'),
                    __(':days days of carry forward leave expired on :date', [
                        'days' => number_format($balance->carried_forward_leaves, 2),
                        'date' => $balance->carry_forward_expiry_date->format('Y-m-d'),
                    ]),
                    $balance->carry_forward_expiry_date->format('Y-m-d'),
                    __('Critical'),
                ]);
            }

            // Alert 3: High unused leave balance (>80% unused)
            $totalAllowed = $balance->entitled_leaves + ($balance->carried_forward_leaves ?? 0) + ($balance->additional_leaves ?? 0);
            if ($totalAllowed > 0) {
                $unusedPercentage = (($totalAllowed - $balance->used_leaves) / $totalAllowed * 100);

                if ($unusedPercentage >= 80) {
                    $alerts->push([
                        $balance->user->code ?? '',
                        $balance->user->getFullName(),
                        __('High Unused Balance'),
                        __(':percent% of :type leave unused (:days days)', [
                            'percent' => number_format($unusedPercentage, 0),
                            'type' => $balance->leaveType->name,
                            'days' => number_format($balance->available_leaves, 2),
                        ]),
                        Carbon::now()->format('Y-m-d'),
                        __('Info'),
                    ]);
                }
            }

            // Alert 4: Negative balance (overused)
            if ($balance->available_leaves < 0) {
                $alerts->push([
                    $balance->user->code ?? '',
                    $balance->user->getFullName(),
                    __('Negative Balance'),
                    __(':days days negative balance for :type', [
                        'days' => number_format(abs($balance->available_leaves), 2),
                        'type' => $balance->leaveType->name,
                    ]),
                    Carbon::now()->format('Y-m-d'),
                    __('Critical'),
                ]);
            }

            // Alert 5: Encashment opportunity (if allowed and high balance)
            if ($balance->leaveType->allow_encashment &&
                $balance->available_leaves > 0 &&
                $balance->available_leaves >= ($balance->leaveType->max_encashment_days ?? 0)) {
                $alerts->push([
                    $balance->user->code ?? '',
                    $balance->user->getFullName(),
                    __('Encashment Eligible'),
                    __('Eligible for encashment of up to :days days of :type', [
                        'days' => number_format($balance->leaveType->max_encashment_days, 2),
                        'type' => $balance->leaveType->name,
                    ]),
                    Carbon::now()->format('Y-m-d'),
                    __('Info'),
                ]);
            }
        }

        // Sort by priority: Critical > Warning > Info
        $priorityOrder = [
            __('Critical') => 1,
            __('Warning') => 2,
            __('Info') => 3,
        ];

        return $alerts->sortBy(function ($alert) use ($priorityOrder) {
            return $priorityOrder[$alert[5]] ?? 999;
        })->values();
    }

    public function headings(): array
    {
        return [
            __('Employee Code'),
            __('Employee Name'),
            __('Alert Type'),
            __('Description'),
            __('Date'),
            __('Priority'),
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
        return __('Policy Alerts');
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

                // Color code rows based on priority
                for ($row = 2; $row <= $highestRow; $row++) {
                    $priority = $event->sheet->getCell('F'.$row)->getValue();

                    if ($priority === __('Critical')) {
                        $event->sheet->getStyle('A'.$row.':'.$highestColumn.$row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFC7CE'],
                            ],
                            'font' => ['color' => ['rgb' => '9C0006']],
                        ]);
                    } elseif ($priority === __('Warning')) {
                        $event->sheet->getStyle('A'.$row.':'.$highestColumn.$row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFEB9C'],
                            ],
                            'font' => ['color' => ['rgb' => '9C6500']],
                        ]);
                    } elseif ($priority === __('Info')) {
                        $event->sheet->getStyle('A'.$row.':'.$highestColumn.$row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'C6EFCE'],
                            ],
                            'font' => ['color' => ['rgb' => '006100']],
                        ]);
                    }
                }

                // Wrap text in description column
                $event->sheet->getStyle('D2:D'.$highestRow)->getAlignment()->setWrapText(true);
            },
        ];
    }
}
