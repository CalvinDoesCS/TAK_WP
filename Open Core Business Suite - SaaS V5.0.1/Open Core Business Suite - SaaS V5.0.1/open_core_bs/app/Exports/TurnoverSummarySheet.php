<?php

namespace App\Exports;

use App\Enums\UserAccountStatus;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TurnoverSummarySheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $currentYear = Carbon::now()->year;
        $startOfYear = Carbon::createFromDate($currentYear, 1, 1)->startOfDay();
        $now = Carbon::now();

        // Total active employees
        $totalActive = User::where('status', UserAccountStatus::ACTIVE)->count();

        // Terminations this year
        $terminationsThisYear = User::whereNotNull('exit_date')
            ->whereYear('exit_date', $currentYear)
            ->count();

        // Terminations this month
        $terminationsThisMonth = User::whereNotNull('exit_date')
            ->whereYear('exit_date', $currentYear)
            ->whereMonth('exit_date', $now->month)
            ->count();

        // Average headcount for the year (simplified)
        $averageHeadcount = ($totalActive + $terminationsThisYear) / 2;

        // Turnover rate calculation
        $turnoverRate = $averageHeadcount > 0
            ? round(($terminationsThisYear / $averageHeadcount) * 100, 2)
            : 0;

        // Monthly turnover rate
        $monthlyTurnoverRate = $totalActive > 0
            ? round(($terminationsThisMonth / $totalActive) * 100, 2)
            : 0;

        $data = collect([
            ['Metric', 'Value'],
            ['Current Active Employees', number_format($totalActive)],
            ['Terminations (Year to Date)', number_format($terminationsThisYear)],
            ['Terminations (This Month)', number_format($terminationsThisMonth)],
            ['Average Headcount (YTD)', number_format($averageHeadcount, 2)],
            ['Annual Turnover Rate', $turnoverRate.'%'],
            ['Monthly Turnover Rate', $monthlyTurnoverRate.'%'],
            ['', ''],
            ['Report Period', 'January '.$currentYear.' - '.$now->format('F Y')],
        ]);

        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8E8E8'],
                ],
            ],
            6 => ['font' => ['bold' => true]],
            7 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return __('Summary');
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

                // Add timestamp
                $event->sheet->setCellValue('A'.($highestRow + 2), __('Generated on:'));
                $event->sheet->setCellValue('B'.($highestRow + 2), now()->format('d-m-Y H:i:s'));
            },
        ];
    }
}
