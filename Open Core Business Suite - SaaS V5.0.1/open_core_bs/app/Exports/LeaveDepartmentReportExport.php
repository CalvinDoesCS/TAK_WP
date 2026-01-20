<?php

namespace App\Exports;

use App\Enums\LeaveRequestStatus;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaveDepartmentReportExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $query = Department::query()
            ->with(['designations.users'])
            ->orderBy('name');

        $departments = $query->get();

        $summary = $departments->map(function ($department) {
            // Get all users in this department
            $users = User::whereHas('designation', function ($q) use ($department) {
                $q->where('department_id', $department->id);
            })->pluck('id');

            $totalEmployees = $users->count();

            if ($totalEmployees === 0) {
                return null;
            }

            // Get leave requests for this department
            $leaveQuery = LeaveRequest::whereIn('user_id', $users);

            // Apply filters
            if (! empty($this->filters['year'])) {
                $leaveQuery->whereYear('from_date', $this->filters['year']);
            } else {
                $leaveQuery->whereYear('from_date', date('Y'));
            }

            if (! empty($this->filters['from_date'])) {
                $leaveQuery->whereDate('from_date', '>=', $this->filters['from_date']);
            }

            if (! empty($this->filters['to_date'])) {
                $leaveQuery->whereDate('to_date', '<=', $this->filters['to_date']);
            }

            $leaves = $leaveQuery->get();
            $approvedLeaves = $leaves->where('status', LeaveRequestStatus::APPROVED);

            $totalLeavesTaken = $approvedLeaves->sum('total_days');
            $avgPerEmployee = $totalEmployees > 0 ? $totalLeavesTaken / $totalEmployees : 0;
            $pendingRequests = $leaves->where('status', LeaveRequestStatus::PENDING)->count();

            // Find most used leave type
            $mostUsedType = $approvedLeaves->groupBy('leave_type_id')
                ->map(function ($group) {
                    return [
                        'name' => $group->first()->leaveType->name ?? '',
                        'count' => $group->sum('total_days'),
                    ];
                })
                ->sortByDesc('count')
                ->first();

            // Calculate utilization rate (this would need entitled leaves data)
            // For now, we'll use a simple metric based on average days taken
            $utilizationRate = $totalEmployees > 0 ? ($totalLeavesTaken / ($totalEmployees * 12)) * 100 : 0;

            return [
                $department->name,
                $totalEmployees,
                number_format($totalLeavesTaken, 2),
                number_format($avgPerEmployee, 2),
                number_format($utilizationRate, 2).'%',
                $pendingRequests,
                $mostUsedType ? $mostUsedType['name'] : '-',
            ];
        })->filter()->values();

        // Add totals row
        if ($summary->count() > 0) {
            $totals = [
                __('TOTAL'),
                $summary->sum(function ($row) {
                    return is_numeric($row[1]) ? $row[1] : 0;
                }),
                number_format($summary->sum(function ($row) {
                    return floatval(str_replace(',', '', $row[2]));
                }), 2),
                number_format($summary->avg(function ($row) {
                    return floatval(str_replace(',', '', $row[3]));
                }), 2),
                number_format($summary->avg(function ($row) {
                    return floatval(str_replace(['%', ','], '', $row[4]));
                }), 2).'%',
                $summary->sum(function ($row) {
                    return is_numeric($row[5]) ? $row[5] : 0;
                }),
                '',
            ];

            $summary->push($totals);
        }

        return $summary;
    }

    public function headings(): array
    {
        return [
            __('Department'),
            __('Total Employees'),
            __('Total Leaves Taken'),
            __('Average per Employee'),
            __('Utilization Rate %'),
            __('Pending Requests'),
            __('Most Used Leave Type'),
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
        return __('Department Statistics');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $highestRow = $event->sheet->getHighestRow();
                $highestColumn = $event->sheet->getHighestColumn();

                // Add borders
                $event->sheet->getStyle('A1:'.$highestColumn.$highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'DDDDDD'],
                        ],
                    ],
                ]);

                // Center align numeric columns
                $event->sheet->getStyle('B2:F'.$highestRow)->getAlignment()->setHorizontal('center');

                // Make totals row bold
                $event->sheet->getStyle('A'.$highestRow.':'.$highestColumn.$highestRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9E1F2'],
                    ],
                ]);
            },
        ];
    }
}
