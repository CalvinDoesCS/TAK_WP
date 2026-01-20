<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHolidayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('hrcore.create-holidays');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:191',
            'code' => 'required|string|max:50|unique:holidays,code',
            'date' => 'required|date',
            'type' => 'required|in:public,religious,regional,optional,company,special',
            'category' => 'nullable|in:national,state,cultural,festival,company_event,other',
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:500',
            'is_optional' => 'boolean',
            'is_restricted' => 'boolean',
            'is_recurring' => 'boolean',
            'is_half_day' => 'boolean',
            'half_day_type' => 'nullable|required_if:is_half_day,1|in:morning,afternoon',
            'half_day_start_time' => 'nullable|required_if:is_half_day,1|date_format:H:i',
            'half_day_end_time' => 'nullable|required_if:is_half_day,1|date_format:H:i',
            'is_compensatory' => 'boolean',
            'compensatory_date' => 'nullable|required_if:is_compensatory,1|date|after:date',
            'applicable_for' => 'required|in:all,department,location,employee_type,branch,custom',
            'departments' => 'nullable|required_if:applicable_for,department|array',
            'departments.*' => 'nullable|exists:departments,id',
            'locations' => 'nullable|required_if:applicable_for,location|array',
            'employee_types' => 'nullable|required_if:applicable_for,employee_type|array',
            'branches' => 'nullable|required_if:applicable_for,branch|array',
            'specific_employees' => 'nullable|required_if:applicable_for,custom|array',
            'specific_employees.*' => 'nullable|exists:users,id',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_visible_to_employees' => 'boolean',
            'send_notification' => 'boolean',
            'notification_days_before' => 'nullable|integer|min:0|max:30',
            'sort_order' => 'nullable|integer|min:0',
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
            'name.required' => __('Holiday name is required'),
            'name.max' => __('Holiday name cannot exceed 191 characters'),
            'code.required' => __('Holiday code is required'),
            'code.unique' => __('This holiday code is already in use'),
            'date.required' => __('Holiday date is required'),
            'date.date' => __('Please provide a valid date'),
            'type.required' => __('Holiday type is required'),
            'type.in' => __('Please select a valid holiday type'),
            'half_day_type.required_if' => __('Half day type is required when half day is enabled'),
            'half_day_start_time.required_if' => __('Start time is required when half day is enabled'),
            'half_day_end_time.required_if' => __('End time is required when half day is enabled'),
            'compensatory_date.required_if' => __('Compensatory date is required when compensatory is enabled'),
            'compensatory_date.after' => __('Compensatory date must be after the holiday date'),
            'applicable_for.required' => __('Please specify who this holiday applies to'),
            'departments.required_if' => __('Please select at least one department'),
            'locations.required_if' => __('Please select at least one location'),
            'employee_types.required_if' => __('Please select at least one employee type'),
            'branches.required_if' => __('Please select at least one branch'),
            'specific_employees.required_if' => __('Please select at least one employee'),
            'color.regex' => __('Color must be a valid hex color code'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert checkbox values from AJAX submission
        $this->merge([
            'is_optional' => $this->has('is_optional') ? filter_var($this->is_optional, FILTER_VALIDATE_BOOLEAN) : false,
            'is_restricted' => $this->has('is_restricted') ? filter_var($this->is_restricted, FILTER_VALIDATE_BOOLEAN) : false,
            'is_recurring' => $this->has('is_recurring') ? filter_var($this->is_recurring, FILTER_VALIDATE_BOOLEAN) : false,
            'is_half_day' => $this->has('is_half_day') ? filter_var($this->is_half_day, FILTER_VALIDATE_BOOLEAN) : false,
            'is_compensatory' => $this->has('is_compensatory') ? filter_var($this->is_compensatory, FILTER_VALIDATE_BOOLEAN) : false,
            'is_visible_to_employees' => $this->has('is_visible_to_employees') ? filter_var($this->is_visible_to_employees, FILTER_VALIDATE_BOOLEAN) : true,
            'send_notification' => $this->has('send_notification') ? filter_var($this->send_notification, FILTER_VALIDATE_BOOLEAN) : false,
        ]);
    }
}
