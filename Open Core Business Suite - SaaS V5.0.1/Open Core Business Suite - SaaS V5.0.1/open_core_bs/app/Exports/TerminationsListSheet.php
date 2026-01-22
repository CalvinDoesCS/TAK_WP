<?php

namespace App\Exports;

use App\Models\User;
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

class TerminationsListSheet implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $currentYear = Carbon::now()->year;

        return User::query()
            ->with(['department', 'designation'])
            ->whereNotNull('exit_date')
            ->whereYear('exit_date', $currentYear)
            ->orderByDesc('exit_date');
    }

    public function map($user): array
    {
        return [
            $user->code ?? '',
            $user->getFullName(),
            $user->email ?? '',
            $user->department->name ?? '',
            $user->designation->name ?? '',
            $user->date_of_joining ? $user->date_of_joining->format('d-m-Y') : '',
            $user->exit_date ? $user->exit_date->format('d-m-Y') : '',
            $user->termination_type ? ucfirst(str_replace('_', ' ', $user->termination_type)) : '',
            $user->termination_reason ?? '',
        ];
    }

    public function headings(): array
    {
        return [
            __('Employee Code'),
            __('Employee Name'),
            __('Email'),
            __('Department'),
            __('Designation'),
            __('Joining Date'),
            __('Exit Date'),
            __('Termination Type'),
            __('Reason'),
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
        return __('Terminations List');
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
