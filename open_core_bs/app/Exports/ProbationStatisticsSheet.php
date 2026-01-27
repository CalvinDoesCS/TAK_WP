<?php

namespace App\Exports;

use App\Enums\UserAccountStatus;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProbationStatisticsSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        // Current probation employees
        $currentProbation = User::where('status', UserAccountStatus::ACTIVE)
            ->whereNotNull('probation_end_date')
            ->whereNull('probation_confirmed_at')
            ->where('probation_end_date', '>', now())
            ->count();

        // Confirmed employees
        $confirmed = User::where('status', UserAccountStatus::ACTIVE)
            ->whereNotNull('probation_confirmed_at')
            ->count();

        // Pending confirmation (probation ended but not confirmed)
        $pendingConfirmation = User::where('status', UserAccountStatus::ACTIVE)
            ->whereNotNull('probation_end_date')
            ->whereNull('probation_confirmed_at')
            ->where('probation_end_date', '<', now())
            ->count();

        // Total active employees
        $totalActive = User::where('status', UserAccountStatus::ACTIVE)->count();

        // Success rate (confirmed / total who went through probation)
        $totalProcessed = $confirmed + $pendingConfirmation;
        $successRate = $totalProcessed > 0
            ? round(($confirmed / $totalProcessed) * 100, 2)
            : 0;

        $data = collect([
            ['Metric', 'Value', 'Percentage'],
            ['Total Active Employees', number_format($totalActive), '100.00%'],
            ['', '', ''],
            ['Probation Status', '', ''],
            ['Currently Under Probation', number_format($currentProbation), $totalActive > 0 ? number_format(($currentProbation / $totalActive) * 100, 2).'%' : '0%'],
            ['Confirmed', number_format($confirmed), $totalActive > 0 ? number_format(($confirmed / $totalActive) * 100, 2).'%' : '0%'],
            ['Pending Confirmation', number_format($pendingConfirmation), $totalActive > 0 ? number_format(($pendingConfirmation / $totalActive) * 100, 2).'%' : '0%'],
            ['', '', ''],
            ['Success Metrics', '', ''],
            ['Probation Success Rate', $successRate.'%', ''],
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
            2 => ['font' => ['bold' => true, 'size' => 14]],
            4 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F5F5F5'],
                ],
            ],
            9 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F5F5F5'],
                ],
            ],
            10 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return __('Statistics');
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
