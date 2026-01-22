<?php

namespace App\Imports;

use App\Models\Designation;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DesignationImport implements ToModel, WithHeadingRow
{
    /**
     * Create a new Designation instance for each row.
     *
     * @return Designation|null
     */
    public function model(array $row)
    {
        return new Designation([
            'name' => $row['name'],
            'code' => $row['code'],
            'notes' => $row['notes'] ?? null,
            'status' => $row['status'] ?? 'active',
            'level' => $row['level'] ?? 0,
            'department_id' => $row['department_id'] ?? null,
            'parent_id' => $row['parent_id'] ?? null,
            'created_by_id' => Auth::id(),
            'updated_by_id' => Auth::id(),
        ]);
    }
}
