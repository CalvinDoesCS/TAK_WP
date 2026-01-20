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

class TenureDistributionSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
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
            ->whereNotNull('date_of_joining')
            ->whereNull('deleted_at')
            ->count();

        $data = DB::table('users')
            ->where('status', UserAccountStatus::ACTIVE)
            ->whereNotNull('date_of_joining')
            ->whereNull('deleted_at')
            ->select(
                DB::raw('CASE
                    WHEN TIMESTAMPDIFF(MONTH, date_of_joining, CURDATE()) < 6 THEN "0-6 months"
                    WHEN TIMESTAMPDIFF(MONTH, date_of_joining, CURDATE()) BETWEEN 6 AND 11 THEN "6-12 months"
                    WHEN TIMESTAMPDIFF(YEAR, date_of_joining, CURDATE()) BETWEEN 1 AND 2 THEN "1-2 years"
                    WHEN TIMESTAMPDIFF(YEAR, date_of_joining, CURDATE()) BETWEEN 3 AND 5 THEN "3-5 years"
                    WHEN TIMESTAMPDIFF(YEAR, date_of_joining, CURDATE()) > 5 THEN "5+ years"
                    ELSE "Unknown"
                END as tenure_group'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('tenure_group')
            ->orderByRaw('FIELD(tenure_group, "0-6 months", "6-12 months", "1-2 years", "3-5 years", "5+ years", "Unknown")')
            ->get()
            ->map(function ($item) use ($totalActive) {
                $percentage = $totalActive > 0
                    ? number_format(($item->count / $totalActive) * 100, 2)
                    : '0.00';

                return [
                    'tenure_group' => $item->tenure_group,
                    'count' => number_format($item->count),
                    'percentage' => $percentage.'%',
                ];
            });

        return $data;
    }

    public function headings(): array
    {
        return [
            __('Tenure Range'),
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
        return __('Distribution');
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
