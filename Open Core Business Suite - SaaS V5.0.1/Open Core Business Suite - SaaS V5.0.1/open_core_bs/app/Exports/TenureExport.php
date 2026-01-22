<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TenureExport implements WithMultipleSheets
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            new TenureOverviewSheet($this->filters),
            new TenureByDepartmentSheet($this->filters),
            new TenureDistributionSheet($this->filters),
            new LongestServingSheet($this->filters),
            new NewestEmployeesSheet($this->filters),
        ];
    }
}
