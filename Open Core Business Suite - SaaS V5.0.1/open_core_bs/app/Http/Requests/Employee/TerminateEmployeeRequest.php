<?php

namespace App\Http\Requests\Employee;

use App\Enums\TerminationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TerminateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $employee = $this->route('user') ?? $this->route('employee');

        return $this->user()->can('terminate', $employee);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'exit_date' => ['required', 'date'],
            'exit_reason' => ['required', 'string', 'max:1000'],
            'termination_type' => ['required', Rule::in(array_column(TerminationType::cases(), 'value'))],
            'last_working_day' => ['required', 'date', 'after_or_equal:exit_date'],
            'is_eligible_for_rehire' => ['nullable', 'boolean'],
            'notice_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'exit_date.required' => __('Exit date is required.'),
            'exit_date.date' => __('Please provide a valid exit date.'),
            'exit_reason.required' => __('Exit reason is required.'),
            'exit_reason.max' => __('Exit reason cannot exceed 1000 characters.'),
            'termination_type.required' => __('Termination type is required.'),
            'termination_type.in' => __('Invalid termination type selected.'),
            'last_working_day.required' => __('Last working day is required.'),
            'last_working_day.date' => __('Please provide a valid last working day.'),
            'last_working_day.after_or_equal' => __('Last working day must be on or after the exit date.'),
            'is_eligible_for_rehire.boolean' => __('Eligible for rehire must be yes or no.'),
            'notice_period_days.integer' => __('Notice period days must be a whole number.'),
            'notice_period_days.min' => __('Notice period days cannot be negative.'),
            'notice_period_days.max' => __('Notice period days cannot exceed 365 days.'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert checkbox values to boolean if needed
        if ($this->has('is_eligible_for_rehire')) {
            $this->merge([
                'is_eligible_for_rehire' => filter_var($this->is_eligible_for_rehire, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }
}
