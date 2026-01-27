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

class HeadcountByDepartmentSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $totalActive = DB::table('users')
            ->where('status', UserAccountStatus::ACTIVE)
            ->whereNull('deleted_at')
            ->count();

        $data = DB::table('users')
            ->join('teams', 'users.team_id', '=', 'teams.id')
            ->where('users.status', UserAccountStatus::ACTIVE)
            ->whereNull('users.deleted_at')
            ->whereNull('teams.deleted_at')
            ->groupBy('teams.id', 'teams.name')
            ->select(
                'teams.name as department_name',
                DB::raw('COUNT(users.id) as count')
            )
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) use ($totalActive) {
                $percentage = $totalActive > 0
                    ? number_format(($item->count / $totalActive) * 100, 2)
                    : '0.00';

                return [
                    'department_name' => $item->department_name,
                    'count' => number_format($item->count),
                    'percentage' => $percentage.'%',
                ];
            });

        return $data;
    }

    public function headings(): array
    {
        return [
            __('Department'),
            __('Employee Count'),
            __('Percentage'),
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

                // Center align count and percentage columns
                $event->sheet->getStyle('B2:C'.$highestRow)->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
