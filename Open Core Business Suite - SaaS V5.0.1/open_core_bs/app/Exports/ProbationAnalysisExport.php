<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProbationAnalysisExport implements WithMultipleSheets
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            new ProbationStatisticsSheet($this->filters),
            new CurrentProbationSheet($this->filters),
            new ProbationOutcomesSheet($this->filters),
            new ProbationHistorySheet($this->filters),
        ];
    }
}
