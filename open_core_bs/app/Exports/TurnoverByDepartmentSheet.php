<?php

namespace App\Exports;

use Carbon\Carbon;
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

class TurnoverByDepartmentSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $currentYear = Carbon::now()->year;

        $data = DB::table('users')
            ->join('teams', 'users.team_id', '=', 'teams.id')
            ->whereNotNull('users.exit_date')
            ->whereYear('users.exit_date', $currentYear)
            ->whereNull('users.deleted_at')
            ->whereNull('teams.deleted_at')
            ->groupBy('teams.id', 'teams.name')
            ->select(
                'teams.name as department_name',
                DB::raw('COUNT(users.id) as terminations')
            )
            ->orderByDesc('terminations')
            ->get()
            ->map(function ($item) {
                return [
                    'department_name' => $item->department_name,
                    'terminations' => number_format($item->terminations),
                ];
            });

        return $data;
    }

    public function headings(): array
    {
        return [
            __('Department'),
            __('Terminations (YTD)'),
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

                // Center align terminations column
                $event->sheet->getStyle('B2:B'.$highestRow)->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
