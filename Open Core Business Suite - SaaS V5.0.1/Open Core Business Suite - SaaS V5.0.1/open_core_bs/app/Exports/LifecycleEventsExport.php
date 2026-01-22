<?php

namespace App\Exports;

use App\Models\EmployeeLifecycleEvent;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LifecycleEventsExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = EmployeeLifecycleEvent::query()
            ->with(['user.department', 'user.designation', 'triggeredBy'])
            ->orderByDesc('event_date');

        // Apply filters
        if (! empty($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }

        if (! empty($this->filters['event_type'])) {
            $query->where('event_type', $this->filters['event_type']);
        }

        if (! empty($this->filters['category'])) {
            $query->ofCategory($this->filters['category']);
        }

        if (! empty($this->filters['date_from'])) {
            $query->whereDate('event_date', '>=', $this->filters['date_from']);
        }

        if (! empty($this->filters['date_to'])) {
            $query->whereDate('event_date', '<=', $this->filters['date_to']);
        }

        return $query;
    }

    public function map($event): array
    {
        $metadata = $event->metadata ?? [];
        $metadataString = collect($metadata)
            ->map(fn ($value, $key) => ucwords(str_replace('_', ' ', $key)).': '.$value)
            ->join('; ');

        return [
            $event->user->code ?? '',
            $event->user->getFullName(),
            $event->user->department->name ?? '',
            $event->user->designation->name ?? '',
            $event->event_type->label(),
            $event->event_type->category(),
            $event->event_date->format('d-m-Y H:i'),
            $event->triggeredBy ? $event->triggeredBy->getFullName() : 'System',
            $event->notes ?? '',
            $metadataString,
        ];
    }

    public function headings(): array
    {
        return [
            __('Employee Code'),
            __('Employee Name'),
            __('Department'),
            __('Designation'),
            __('Event Type'),
            __('Category'),
            __('Event Date'),
            __('Triggered By'),
            __('Notes'),
            __('Details'),
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

                // Add filter information
                $filterInfo = $this->getFilterInfo();
                if ($filterInfo) {
                    $event->sheet->setCellValue('A'.($highestRow + 3), __('Filters Applied:'));
                    $event->sheet->setCellValue('B'.($highestRow + 3), $filterInfo);
                }
            },
        ];
    }

    protected function getFilterInfo(): string
    {
        $info = [];

        if (! empty($this->filters['date_from'])) {
            $info[] = __('From: :date', ['date' => $this->filters['date_from']]);
        }

        if (! empty($this->filters['date_to'])) {
            $info[] = __('To: :date', ['date' => $this->filters['date_to']]);
        }

        if (! empty($this->filters['category'])) {
            $info[] = __('Category: :category', ['category' => $this->filters['category']]);
        }

        if (! empty($this->filters['event_type'])) {
            $info[] = __('Event Type: :type', ['type' => $this->filters['event_type']]);
        }

        return implode(', ', $info);
    }
}
