<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class HeadcountExport implements WithMultipleSheets
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            new HeadcountOverviewSheet($this->filters),
            new HeadcountByDepartmentSheet($this->filters),
            new HeadcountByDesignationSheet($this->filters),
            new HeadcountTrendSheet($this->filters),
        ];
    }
}
