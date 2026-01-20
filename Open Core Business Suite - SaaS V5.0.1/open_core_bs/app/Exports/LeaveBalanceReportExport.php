<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LeaveBalanceReportExport implements WithMultipleSheets
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            new LeaveBalanceSheet($this->filters),
            new LeaveBalanceSummarySheet($this->filters),
        ];
    }
}
