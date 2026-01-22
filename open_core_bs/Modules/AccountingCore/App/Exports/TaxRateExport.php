<?php

namespace Modules\AccountingCore\App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\AccountingCore\App\Models\TaxRate;

class TaxRateExport implements FromCollection, WithHeadings
{
    /**
     * Export data as a collection.
     *
     * @return Collection
     */
    public function collection()
    {
        return TaxRate::select(
            'id',
            'name',
            'rate',
            'type',
            'is_default',
            'is_active',
            'description',
            'tax_authority',
            'created_at',
            'updated_at'
        )->get();
    }

    /**
     * Define column headings.
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Rate',
            'Type',
            'Is Default',
            'Is Active',
            'Description',
            'Tax Authority',
            'Created At',
            'Updated At',
        ];
    }
}
