<?php

namespace Modules\AccountingCore\App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\AccountingCore\App\Models\BasicTransactionCategory;

class BasicTransactionCategoryExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * Export data as a collection.
     *
     * @return Collection
     */
    public function collection()
    {
        return BasicTransactionCategory::with(['parent'])->get();
    }

    /**
     * Map data for export.
     */
    public function map($category): array
    {
        return [
            $category->id,
            $category->name,
            $category->type,
            $category->parent?->name ?? '',
            $category->icon,
            $category->color,
            $category->is_active ? 'Yes' : 'No',
            $category->created_at?->format('Y-m-d H:i:s'),
            $category->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Define column headings.
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Type',
            'Parent Category',
            'Icon',
            'Color',
            'Is Active',
            'Created At',
            'Updated At',
        ];
    }
}
