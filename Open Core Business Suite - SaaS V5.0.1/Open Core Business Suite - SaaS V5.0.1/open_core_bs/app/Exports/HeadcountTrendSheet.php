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

class HeadcountTrendSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $trend = [];
        $now = Carbon::now();

        for ($i = 11; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i)->endOfMonth();

            // Count active employees at end of that month
            $count = User::where('status', UserAccountStatus::ACTIVE)
                ->where(function ($query) use ($date) {
                    $query->whereNull('date_of_joining')
                        ->orWhereDate('date_of_joining', '<=', $date);
                })
                ->where(function ($query) use ($date) {
                    $query->whereNull('exit_date')
                        ->orWhereDate('exit_date', '>', $date);
                })
                ->count();

            $trend[] = [
                'month' => $date->format('M Y'),
                'count' => number_format($count),
            ];
        }

        return collect($trend);
    }

    public function headings(): array
    {
        return [
            __('Month'),
            __('Employee Count'),
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
        return __('12-Month Trend');
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

                // Center align count column
                $event->sheet->getStyle('B2:B'.$highestRow)->getAlignment()->setHorizontal('center');
            },
        ];
    }
}
