<?php

namespace App\Http\Requests\Employee;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeBasicInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $employee = $this->route('user') ?? $this->route('employee');

        return $this->user()->can('update', $employee);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $employee = $this->route('user') ?? $this->route('employee');

        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')
                    ->ignore($employee->id)
                    ->whereNull('deleted_at'),
            ],
            'phone' => ['required', 'string', 'max:15'],
            'dob' => ['required', 'date'],
            'gender' => ['required', Rule::in(array_column(Gender::cases(), 'value'))],
            'address' => ['nullable', 'string', 'max:255'],
            'alternateNumber' => ['nullable', 'string', 'max:15'],
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
            'firstName.required' => __('First name is required.'),
            'firstName.max' => __('First name cannot exceed 255 characters.'),
            'lastName.required' => __('Last name is required.'),
            'lastName.max' => __('Last name cannot exceed 255 characters.'),
            'email.required' => __('Email is required.'),
            'email.email' => __('Please provide a valid email address.'),
            'email.unique' => __('This email is already registered.'),
            'phone.required' => __('Phone number is required.'),
            'phone.max' => __('Phone number cannot exceed 15 characters.'),
            'dob.required' => __('Date of birth is required.'),
            'dob.date' => __('Please provide a valid date of birth.'),
            'gender.required' => __('Gender is required.'),
            'gender.in' => __('Invalid gender selected.'),
            'address.max' => __('Address cannot exceed 255 characters.'),
            'alternateNumber.max' => __('Alternate number cannot exceed 15 characters.'),
        ];
    }
}
