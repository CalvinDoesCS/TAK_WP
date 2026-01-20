<?php

namespace App\Exports;

use App\Enums\UserAccountStatus;
use App\Models\User;
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

class HeadcountOverviewSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        // Get total active employees
        $totalActiveEmployees = User::where('status', UserAccountStatus::ACTIVE)->count();

        // Get employment status breakdown
        $employmentStatus = DB::table('users')
            ->where('status', UserAccountStatus::ACTIVE)
            ->whereNull('deleted_at')
            ->select(
                DB::raw('CASE
                    WHEN probation_end_date IS NOT NULL AND probation_confirmed_at IS NULL AND probation_end_date > NOW() THEN "Under Probation"
                    WHEN probation_confirmed_at IS NOT NULL THEN "Confirmed"
                    ELSE "Regular"
                END as employment_status'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('employment_status')
            ->get();

        $data = collect([
            ['Metric', 'Value', 'Percentage'],
            ['Total Active Employees', $totalActiveEmployees, '100.00%'],
            ['', '', ''],
            ['By Employment Status', '', ''],
        ]);

        foreach ($employmentStatus as $status) {
            $percentage = $totalActiveEmployees > 0
                ? number_format(($status->count / $totalActiveEmployees) * 100, 2)
                : '0.00';
            $data->push([$status->employment_status, $status->count, $percentage.'%']);
        }

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
            2 => [
                'font' => ['bold' => true, 'size' => 14],
            ],
            4 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F5F5F5'],
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

                // Add timestamp at bottom
                $event->sheet->setCellValue('A'.($highestRow + 2), __('Generated on:'));
                $event->sheet->setCellValue('B'.($highestRow + 2), now()->format('d-m-Y H:i:s'));
            },
        ];
    }
}
