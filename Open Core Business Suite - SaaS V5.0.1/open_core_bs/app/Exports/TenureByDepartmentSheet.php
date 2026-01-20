<?php

namespace App\Exports;

use App\Enums\UserAccountStatus;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TenureByDepartmentSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $data = DB::table('users')
            ->join('teams', 'users.team_id', '=', 'teams.id')
            ->where('users.status', UserAccountStatus::ACTIVE)
            ->whereNotNull('users.date_of_joining')
            ->whereNull('users.deleted_at')
            ->whereNull('teams.deleted_at')
            ->groupBy('teams.id', 'teams.name')
            ->select(
                'teams.name as department_name',
                DB::raw('COUNT(users.id) as employee_count'),
                DB::raw('AVG(TIMESTAMPDIFF(MONTH, users.date_of_joining, CURDATE())) as avg_tenure_months')
            )
            ->orderBy('department_name')
            ->get()
            ->map(function ($item) {
                $avgTenure = round($item->avg_tenure_months, 1);
                $years = floor($avgTenure / 12);
                $months = round($avgTenure % 12);

                return [
                    'department_name' => $item->department_name,
                    'employee_count' => number_format($item->employee_count),
                    'avg_tenure' => "{$years}y {$months}m",
                    'avg_tenure_months' => number_format($avgTenure, 1),
                ];
            });

        return $data;
    }

    public function headings(): array
    {
        return [
            __('Department'),
            __('Employees'),
            __('Avg Tenure'),
            __('Avg (Months)'),
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
        return __('By Department');
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

                // Center align numeric columns
                $event->sheet->getStyle('B2:D'.$highestRow)->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
