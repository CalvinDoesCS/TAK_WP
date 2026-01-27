<?php

namespace App\Exports;

use App\Models\ExpenseType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExpenseTypeExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * Export data as a collection.
     */
    public function collection(): Collection
    {
        return ExpenseType::withCount('expenseRequests')->get();
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
            'Default Amount',
            'Max Amount',
            'Is Proof Required',
            'Status',
            'Expense Requests Count',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * Map data for export.
     */
    public function map($expenseType): array
    {
        return [
            $expenseType->id,
            $expenseType->name,
            $expenseType->code,
            $expenseType->notes,
            $expenseType->default_amount,
            $expenseType->max_amount,
            $expenseType->is_proof_required ? 'Yes' : 'No',
            $expenseType->status?->value ?? '',
            $expenseType->expense_requests_count,
            $expenseType->created_at?->format('Y-m-d H:i:s'),
            $expenseType->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
