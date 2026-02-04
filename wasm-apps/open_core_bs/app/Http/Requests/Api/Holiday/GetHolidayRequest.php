<?php

namespace App\Http\Requests\Api\Holiday;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GetHolidayRequest extends FormRequest
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
        return [
            'skip' => 'nullable|numeric|min:0',
            'take' => 'nullable|numeric|min:1|max:100',
            'year' => 'nullable|numeric|digits:4|min:2000|max:2100',
            'type' => 'nullable|in:public,religious,regional,optional,company,special',
            'is_active' => 'nullable|boolean',
            'upcoming' => 'nullable|boolean',
            'visible_to_employees' => 'nullable|boolean',
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
            'skip.numeric' => 'Skip value must be a number',
            'skip.min' => 'Skip value must be at least 0',
            'take.numeric' => 'Take value must be a number',
            'take.min' => 'Take value must be at least 1',
            'take.max' => 'Take value cannot exceed 100',
            'year.numeric' => 'Year must be a number',
            'year.digits' => 'Year must be a 4-digit number',
            'year.min' => 'Year must be 2000 or later',
            'year.max' => 'Year cannot exceed 2100',
            'type.in' => 'Invalid holiday type',
        ];
    }
}
