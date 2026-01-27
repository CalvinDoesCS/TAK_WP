<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TurnoverExport implements WithMultipleSheets
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            new TurnoverSummarySheet($this->filters),
            new TurnoverTrendSheet($this->filters),
            new TurnoverByDepartmentSheet($this->filters),
            new TerminationsListSheet($this->filters),
        ];
    }
}
