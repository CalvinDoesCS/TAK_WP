<?php

namespace Modules\MultiTenancyCore\App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ValidateRegistrationFieldRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public registration validation
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $field = $this->input('field');

        $fieldRules = [
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:tenants,email'],
            'subdomain' => ['required', 'string', 'max:63', 'unique:tenants,subdomain', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', Rule::in(['male', 'female', 'other'])],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'company_name' => ['required', 'string', 'max:255'],
        ];

        $rules = [
            'field' => ['required', 'string', Rule::in(array_keys($fieldRules))],
        ];

        // Add rules for the specified field if valid
        if ($field && array_key_exists($field, $fieldRules)) {
            $rules[$field] = $fieldRules[$field];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'field.required' => __('Field name is required for validation.'),
            'field.in' => __('Invalid field specified for validation.'),
            'email.required' => __('Email address is required.'),
            'email.email' => __('Please enter a valid email address.'),
            'email.unique' => __('This email address is already registered.'),
            'subdomain.required' => __('Subdomain is required.'),
            'subdomain.max' => __('Subdomain may not be greater than :max characters.'),
            'subdomain.regex' => __('The subdomain may only contain lowercase letters, numbers, and hyphens.'),
            'subdomain.unique' => __('This subdomain is already taken.'),
            'firstName.required' => __('First name is required.'),
            'firstName.max' => __('First name may not be greater than :max characters.'),
            'lastName.required' => __('Last name is required.'),
            'lastName.max' => __('Last name may not be greater than :max characters.'),
            'gender.required' => __('Gender is required.'),
            'gender.in' => __('Please select a valid gender.'),
            'phone.required' => __('Phone number is required.'),
            'phone.max' => __('Phone number may not be greater than :max characters.'),
            'phone.unique' => __('This phone number is already registered.'),
            'password.required' => __('Password is required.'),
            'password.min' => __('Password must be at least :min characters.'),
            'company_name.required' => __('Company name is required.'),
            'company_name.max' => __('Company name may not be greater than :max characters.'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $field = $this->input('field');

        // Normalize field values
        if ($field === 'subdomain' && $this->has('subdomain')) {
            $this->merge([
                'subdomain' => strtolower(trim($this->subdomain)),
            ]);
        }

        if ($field === 'email' && $this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->email)),
            ]);
        }
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'field' => $this->input('field'),
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
