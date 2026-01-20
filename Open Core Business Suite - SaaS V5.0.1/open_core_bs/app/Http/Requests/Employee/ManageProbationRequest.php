<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class ManageProbationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $employee = $this->route('user') ?? $this->route('employee');

        return $this->user()->can('manageProbation', $employee);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $action = $this->input('action');

        $rules = [
            'action' => ['required', 'in:confirm,extend,fail'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];

        // Additional rules based on action
        if ($action === 'extend') {
            $rules['extension_months'] = ['required', 'integer', 'min:1', 'max:24'];
        }

        if ($action === 'fail') {
            $rules['failure_reason'] = ['required', 'string', 'max:1000'];
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => __('Probation action is required.'),
            'action.in' => __('Invalid probation action. Must be confirm, extend, or fail.'),
            'remarks.max' => __('Remarks cannot exceed 1000 characters.'),
            'extension_months.required' => __('Extension period is required when extending probation.'),
            'extension_months.integer' => __('Extension period must be a whole number.'),
            'extension_months.min' => __('Extension period must be at least 1 month.'),
            'extension_months.max' => __('Extension period cannot exceed 24 months.'),
            'failure_reason.required' => __('Failure reason is required when probation fails.'),
            'failure_reason.max' => __('Failure reason cannot exceed 1000 characters.'),
        ];
    }
}
