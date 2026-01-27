<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShiftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert checkbox values to boolean
        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        foreach ($days as $day) {
            $this->merge([
                $day => filter_var($this->input($day, false), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $shiftId = $this->route('shift')->id;

        return [
            'name' => ['required', 'string', 'max:191'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('shifts', 'code')->ignore($shiftId),
            ],
            'shift_type' => ['nullable', 'string', 'in:regular,night'],
            'notes' => ['nullable', 'string', 'max:500'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'sunday' => ['nullable', 'boolean'],
            'monday' => ['nullable', 'boolean'],
            'tuesday' => ['nullable', 'boolean'],
            'wednesday' => ['nullable', 'boolean'],
            'thursday' => ['nullable', 'boolean'],
            'friday' => ['nullable', 'boolean'],
            'saturday' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('The shift name is required.'),
            'name.max' => __('The shift name may not be greater than 191 characters.'),
            'code.required' => __('The shift code is required.'),
            'code.unique' => __('This shift code is already in use.'),
            'code.max' => __('The shift code may not be greater than 50 characters.'),
            'start_time.required' => __('The start time is required.'),
            'start_time.date_format' => __('The start time must be in HH:MM format.'),
            'end_time.required' => __('The end time is required.'),
            'end_time.date_format' => __('The end time must be in HH:MM format.'),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Ensure at least one working day is selected
            $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            $anySelected = false;

            foreach ($days as $day) {
                if ($this->input($day)) {
                    $anySelected = true;
                    break;
                }
            }

            if (! $anySelected) {
                $validator->errors()->add('working_days', __('Please select at least one working day.'));
            }
        });
    }
}
