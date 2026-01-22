<?php

namespace App\Imports;

use App\Enums\Status;
use App\Models\ExpenseType;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ExpenseTypeImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * Create a new ExpenseType instance for each row.
     */
    public function model(array $row): ?ExpenseType
    {
        // Skip if required fields are missing
        if (empty($row['name']) || empty($row['code'])) {
            return null;
        }

        return new ExpenseType([
            'name' => $row['name'],
            'code' => $row['code'],
            'notes' => $row['notes'] ?? null,
            'default_amount' => $row['default_amount'] ?? null,
            'max_amount' => $row['max_amount'] ?? null,
            'is_proof_required' => $this->parseBoolean($row['is_proof_required'] ?? false),
            'status' => $this->parseStatus($row['status'] ?? 'active'),
            'created_by_id' => Auth::id(),
            'updated_by_id' => Auth::id(),
        ]);
    }

    /**
     * Define validation rules.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:expense_types,code',
            'default_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Parse status value to enum.
     */
    private function parseStatus(mixed $value): Status
    {
        if ($value instanceof Status) {
            return $value;
        }

        $statusMap = [
            'active' => Status::ACTIVE,
            'inactive' => Status::INACTIVE,
        ];

        return $statusMap[strtolower((string) $value)] ?? Status::ACTIVE;
    }

    /**
     * Parse boolean value from various formats.
     */
    private function parseBoolean(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_string($value)) {
            return \in_array(strtolower($value), ['yes', 'true', '1', 'active']);
        }

        return (bool) $value;
    }
}
