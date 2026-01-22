<?php

namespace Modules\AccountingCore\App\Imports;

use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\AccountingCore\App\Models\TaxRate;

class TaxRateImport implements ToModel, WithHeadingRow
{
    /**
     * Create a new TaxRate instance for each row.
     *
     * @return TaxRate|null
     */
    public function model(array $row)
    {
        return new TaxRate([
            'name' => $row['name'],
            'rate' => $row['rate'],
            'type' => $row['type'] ?? 'percentage',
            'is_default' => $row['is_default'] ?? false,
            'is_active' => $row['is_active'] ?? true,
            'description' => $row['description'] ?? null,
            'tax_authority' => $row['tax_authority'] ?? null,
            'created_by_id' => Auth::id(),
            'updated_by_id' => Auth::id(),
        ]);
    }
}
