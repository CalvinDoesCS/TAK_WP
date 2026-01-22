<?php

namespace App\Exports;

use App\Models\Designation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DesignationExport implements FromCollection, WithHeadings
{
    /**
     * Export data as a collection.
     *
     * @return Collection
     */
    public function collection()
    {
        return Designation::select(
            'id',
            'name',
            'code',
            'notes',
            'status',
            'level',
            'department_id',
            'parent_id',
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
            'Code',
            'Notes',
            'Status',
            'Level',
            'Department ID',
            'Parent ID',
            'Created At',
            'Updated At',
        ];
    }
}
