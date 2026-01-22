<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LeaveComplianceReportExport implements WithMultipleSheets
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            new ExpiringCarryForwardSheet($this->filters),
            new EncashmentEligibleSheet($this->filters),
            new PolicyAlertsSheet($this->filters),
        ];
    }
}
