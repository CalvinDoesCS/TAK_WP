<?php

namespace App\Http\Requests\Api\Holiday;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHolidayRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $holidayId = $this->route('id');

        return [
            'name' => 'sometimes|required|string|max:191',
            'date' => 'sometimes|required|date',
            'code' => 'sometimes|required|string|max:50|unique:holidays,code,'.$holidayId,
            'type' => 'sometimes|required|in:public,religious,regional,optional,company,special',
            'category' => 'nullable|in:national,state,cultural,festival,company_event,other',
            'is_optional' => 'nullable|boolean',
            'is_restricted' => 'nullable|boolean',
            'is_recurring' => 'nullable|boolean',
            'applicable_for' => 'sometimes|required|in:all,department,location,employee_type,custom',
            'departments' => 'nullable|array',
            'departments.*' => 'integer|exists:departments,id',
            'locations' => 'nullable|array',
            'locations.*' => 'string',
            'employee_types' => 'nullable|array',
            'employee_types.*' => 'string',
            'branches' => 'nullable|array',
            'branches.*' => 'integer',
            'specific_employees' => 'nullable|array',
            'specific_employees.*' => 'integer|exists:users,id',
            'description' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
            'image' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
            'sort_order' => 'nullable|integer|min:0',
            'is_compensatory' => 'nullable|boolean',
            'compensatory_date' => 'nullable|date|required_if:is_compensatory,true',
            'is_half_day' => 'nullable|boolean',
            'half_day_type' => 'nullable|in:morning,afternoon|required_if:is_half_day,true',
            'half_day_start_time' => 'nullable|date_format:H:i|required_if:is_half_day,true',
            'half_day_end_time' => 'nullable|date_format:H:i|required_if:is_half_day,true|after:half_day_start_time',
            'is_active' => 'nullable|boolean',
            'is_visible_to_employees' => 'nullable|boolean',
            'send_notification' => 'nullable|boolean',
            'notification_days_before' => 'nullable|integer|min:0|max:365',
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
            'name.required' => 'Holiday name is required',
            'date.required' => 'Holiday date is required',
            'code.required' => 'Holiday code is required',
            'code.unique' => 'This holiday code already exists',
            'type.required' => 'Holiday type is required',
            'type.in' => 'Invalid holiday type',
            'category.in' => 'Invalid holiday category',
            'applicable_for.required' => 'Applicability is required',
            'applicable_for.in' => 'Invalid applicability option',
            'departments.*.exists' => 'One or more departments do not exist',
            'specific_employees.*.exists' => 'One or more employees do not exist',
            'color.regex' => 'Color must be a valid hex code (e.g., #FF5733)',
            'compensatory_date.required_if' => 'Compensatory date is required when holiday is compensatory',
            'half_day_type.required_if' => 'Half day type is required when holiday is a half day',
            'half_day_start_time.required_if' => 'Start time is required for half day holidays',
            'half_day_end_time.required_if' => 'End time is required for half day holidays',
            'half_day_end_time.after' => 'End time must be after start time',
        ];
    }
}
