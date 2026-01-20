<?php

namespace App\Http\Requests;

use App\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Expense type name is required'),
            'name.max' => __('Expense type name cannot exceed 255 characters'),
            'description.max' => __('Description cannot exceed 1000 characters'),
            'status.required' => __('Status is required'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('status') && in_array($this->status, ['0', '1', 0, 1])) {
            $this->merge([
                'status' => $this->status == '1' || $this->status == 1 ? Status::ACTIVE->value : Status::INACTIVE->value,
            ]);
        }
    }
}
