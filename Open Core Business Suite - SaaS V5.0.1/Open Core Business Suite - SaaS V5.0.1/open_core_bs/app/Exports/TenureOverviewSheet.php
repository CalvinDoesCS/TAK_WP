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

class TenureOverviewSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        // Calculate average tenure in months
        $averageTenure = DB::table('users')
            ->where('status', UserAccountStatus::ACTIVE)
            ->whereNotNull('date_of_joining')
            ->whereNull('deleted_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MONTH, date_of_joining, CURDATE())) as avg_tenure_months')
            ->value('avg_tenure_months');

        $averageTenure = $averageTenure ? round($averageTenure, 1) : 0;
        $averageYears = floor($averageTenure / 12);
        $averageMonths = round($averageTenure % 12);

        // Get median tenure
        $tenures = DB::table('users')
            ->where('status', UserAccountStatus::ACTIVE)
            ->whereNotNull('date_of_joining')
            ->whereNull('deleted_at')
            ->selectRaw('TIMESTAMPDIFF(MONTH, date_of_joining, CURDATE()) as tenure_months')
            ->pluck('tenure_months')
            ->sort()
            ->values();

        $count = $tenures->count();
        $medianTenure = $count > 0
            ? ($count % 2 == 0
                ? ($tenures[$count / 2 - 1] + $tenures[$count / 2]) / 2
                : $tenures[floor($count / 2)])
            : 0;

        $medianYears = floor($medianTenure / 12);
        $medianMonths = round($medianTenure % 12);

        $data = collect([
            ['Metric', 'Value'],
            ['Average Tenure', "{$averageYears} years {$averageMonths} months ({$averageTenure} months)"],
            ['Median Tenure', "{$medianYears} years {$medianMonths} months (".round($medianTenure, 1).' months)'],
            ['Total Active Employees', number_format($count)],
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
        return __('Overview');
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
