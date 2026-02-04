<?php

namespace App\Exports;

use App\Models\User;
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

class ProbationHistorySheet implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return User::query()
            ->with(['department', 'designation'])
            ->whereNotNull('probation_end_date')
            ->orderByDesc('created_at')
            ->limit(200);
    }

    public function map($user): array
    {
        $status = 'N/A';
        if ($user->probation_confirmed_at) {
            $status = 'Confirmed';
        } elseif ($user->probation_end_date && $user->probation_end_date->isPast()) {
            $status = 'Pending Confirmation';
        } elseif ($user->probation_end_date && $user->probation_end_date->isFuture()) {
            $status = 'Under Probation';
        }

        return [
            $user->code ?? '',
            $user->getFullName(),
            $user->department->name ?? '',
            $user->designation->name ?? '',
            $user->date_of_joining ? $user->date_of_joining->format('d-m-Y') : '',
            $user->probation_end_date ? $user->probation_end_date->format('d-m-Y') : '',
            $user->probation_confirmed_at ? $user->probation_confirmed_at->format('d-m-Y') : '',
            $status,
        ];
    }

    public function headings(): array
    {
        return [
            __('Employee Code'),
            __('Employee Name'),
            __('Department'),
            __('Designation'),
            __('Joining Date'),
            __('Probation End Date'),
            __('Confirmation Date'),
            __('Status'),
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
        return __('History');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
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
            },
        ];
    }
}
