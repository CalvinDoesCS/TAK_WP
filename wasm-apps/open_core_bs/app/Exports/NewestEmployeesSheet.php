<?php

namespace App\Exports;

use App\Enums\UserAccountStatus;
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

class NewestEmployeesSheet implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
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
            ->where('status', UserAccountStatus::ACTIVE)
            ->whereNotNull('date_of_joining')
            ->orderBy('date_of_joining', 'desc')
            ->limit(50);
    }

    public function map($user): array
    {
        $tenureMonths = $user->date_of_joining
            ? $user->date_of_joining->diffInMonths(now())
            : 0;
        $years = floor($tenureMonths / 12);
        $months = $tenureMonths % 12;

        return [
            $user->code ?? '',
            $user->getFullName(),
            $user->department->name ?? '',
            $user->designation->name ?? '',
            $user->date_of_joining ? $user->date_of_joining->format('d-m-Y') : '',
            "{$years}y {$months}m",
            number_format($tenureMonths, 1),
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
            __('Tenure'),
            __('Tenure (Months)'),
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
        return __('Newest Employees');
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
