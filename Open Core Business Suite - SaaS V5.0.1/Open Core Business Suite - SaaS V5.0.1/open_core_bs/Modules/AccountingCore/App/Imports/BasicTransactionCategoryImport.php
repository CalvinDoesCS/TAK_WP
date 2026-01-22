<?php

namespace Modules\AccountingCore\App\Imports;

use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\AccountingCore\App\Models\BasicTransactionCategory;

class BasicTransactionCategoryImport implements ToModel, WithHeadingRow
{
    /**
     * Create a new BasicTransactionCategory instance for each row.
     *
     * @return BasicTransactionCategory|null
     */
    public function model(array $row)
    {
        // Lookup parent category by name if provided
        $parentId = null;
        if (isset($row['parent_name']) && $row['parent_name']) {
            $parent = BasicTransactionCategory::where('name', $row['parent_name'])->first();
            $parentId = $parent?->id;
        } elseif (isset($row['parent_id'])) {
            $parentId = $row['parent_id'];
        }

        return new BasicTransactionCategory([
            'name' => $row['name'],
            'type' => $row['type'],
            'parent_id' => $parentId,
            'icon' => $row['icon'] ?? null,
            'color' => $row['color'] ?? null,
            'is_active' => $row['is_active'] ?? true,
            'created_by_id' => Auth::id(),
            'updated_by_id' => Auth::id(),
        ]);
    }
}
