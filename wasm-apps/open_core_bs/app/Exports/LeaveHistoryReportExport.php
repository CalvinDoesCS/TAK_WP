<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LeaveHistoryReportExport implements WithMultipleSheets
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            new LeaveHistorySheet($this->filters),
            new LeaveHistoryEmployeeSummarySheet($this->filters),
            new LeaveHistoryTypeSummarySheet($this->filters),
        ];
    }
}
