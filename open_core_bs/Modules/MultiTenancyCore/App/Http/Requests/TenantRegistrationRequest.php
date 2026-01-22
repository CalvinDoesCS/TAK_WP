<?php

namespace Modules\MultiTenancyCore\App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class TenantRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public registration form
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // User information
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', Rule::in(['male', 'female', 'other'])],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:tenants,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // Company information
            'company_name' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'max:63', 'unique:tenants,subdomain', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],

            // Terms acceptance
            'terms' => ['required', 'accepted'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'firstName' => __('first name'),
            'lastName' => __('last name'),
            'gender' => __('gender'),
            'phone' => __('phone number'),
            'email' => __('email address'),
            'password' => __('password'),
            'company_name' => __('company name'),
            'subdomain' => __('subdomain'),
            'terms' => __('terms and conditions'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'firstName.required' => __('First name is required.'),
            'firstName.max' => __('First name may not be greater than :max characters.'),
            'lastName.required' => __('Last name is required.'),
            'lastName.max' => __('Last name may not be greater than :max characters.'),
            'gender.required' => __('Gender is required.'),
            'gender.in' => __('Please select a valid gender.'),
            'phone.required' => __('Phone number is required.'),
            'phone.max' => __('Phone number may not be greater than :max characters.'),
            'phone.unique' => __('This phone number is already registered.'),
            'email.required' => __('Email address is required.'),
            'email.email' => __('Please enter a valid email address.'),
            'email.unique' => __('This email address is already registered. Please use a different email or login to your existing account.'),
            'password.required' => __('Password is required.'),
            'password.min' => __('Password must be at least :min characters.'),
            'password.confirmed' => __('Password confirmation does not match.'),
            'company_name.required' => __('Company name is required.'),
            'company_name.max' => __('Company name may not be greater than :max characters.'),
            'subdomain.required' => __('Subdomain is required.'),
            'subdomain.max' => __('Subdomain may not be greater than :max characters.'),
            'subdomain.regex' => __('The subdomain may only contain lowercase letters, numbers, and hyphens.'),
            'subdomain.unique' => __('This subdomain is already taken. Please choose another one.'),
            'terms.required' => __('You must accept the terms and conditions.'),
            'terms.accepted' => __('You must accept the terms and conditions.'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize subdomain to lowercase
        if ($this->has('subdomain')) {
            $this->merge([
                'subdomain' => strtolower(trim($this->subdomain)),
            ]);
        }

        // Trim whitespace from string fields
        $this->merge([
            'firstName' => trim($this->firstName ?? ''),
            'lastName' => trim($this->lastName ?? ''),
            'phone' => trim($this->phone ?? ''),
            'email' => trim(strtolower($this->email ?? '')),
            'company_name' => trim($this->company_name ?? ''),
        ]);
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => __('Validation failed. Please check the form for errors.'),
                    'errors' => $validator->errors(),
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }
}
