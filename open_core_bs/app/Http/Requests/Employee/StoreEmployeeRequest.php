<?php

namespace App\Http\Requests\Employee;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\User::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Basic Information
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(array_column(Gender::cases(), 'value'))],
            'phone' => ['required', 'string', 'max:15', 'unique:users,phone'],
            'altPhone' => ['nullable', 'string', 'max:15'],
            'email' => ['required', 'email', 'unique:users,email'],
            'dob' => ['required', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],

            // Authentication
            'role' => ['required', 'exists:roles,name'],
            'useDefaultPassword' => ['nullable'],
            'password' => ['nullable', 'min:8'],
            'confirmPassword' => ['nullable', 'min:8', 'same:password'],

            // Work Information
            'code' => ['required', 'string', 'max:255', 'unique:users,code'],
            'designationId' => ['required', 'exists:designations,id'],
            'doj' => ['required', 'date'],
            'teamId' => ['required', 'exists:teams,id'],
            'shiftId' => ['required', 'exists:shifts,id'],
            'reportingToId' => ['required', 'exists:users,id'],

            // Attendance Type
            'attendanceType' => ['required', 'in:open,geofence,ipAddress,staticqr,dynamicqr,site,face'],
            'geofenceGroupId' => ['required_if:attendanceType,geofence', 'exists:geofence_groups,id'],
            'ipGroupId' => ['required_if:attendanceType,ipAddress', 'exists:ip_address_groups,id'],
            'qrGroupId' => ['required_if:attendanceType,staticqr', 'exists:qr_groups,id'],
            'siteId' => ['required_if:attendanceType,site', 'exists:sites,id'],
            'dynamicQrId' => ['required_if:attendanceType,dynamicqr', 'exists:dynamic_qr_devices,id'],

            // Compensation
            'baseSalary' => ['required', 'numeric', 'min:0'],
            'availableLeaveCount' => ['nullable', 'numeric', 'min:0'],

            // Probation
            'probation_period_months' => ['nullable', 'integer', 'min:1', 'max:24'],
            'probation_remarks' => ['nullable', 'string', 'max:1000'],
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
            'lastName.required' => __('Last name is required.'),
            'gender.required' => __('Gender is required.'),
            'gender.in' => __('Invalid gender selected.'),
            'phone.required' => __('Phone number is required.'),
            'phone.unique' => __('This phone number is already registered.'),
            'email.required' => __('Email is required.'),
            'email.email' => __('Please provide a valid email address.'),
            'email.unique' => __('This email is already registered.'),
            'dob.required' => __('Date of birth is required.'),
            'dob.date' => __('Please provide a valid date of birth.'),
            'file.image' => __('Profile picture must be an image.'),
            'file.mimes' => __('Profile picture must be a jpeg, png, or jpg file.'),
            'file.max' => __('Profile picture size must not exceed 2MB.'),

            'role.required' => __('Role is required.'),
            'role.exists' => __('Selected role does not exist.'),
            'password.min' => __('Password must be at least 6 characters.'),
            'confirmPassword.same' => __('Password confirmation does not match.'),

            'code.required' => __('Employee code is required.'),
            'code.unique' => __('This employee code is already in use.'),
            'designationId.required' => __('Designation is required.'),
            'designationId.exists' => __('Selected designation does not exist.'),
            'doj.required' => __('Date of joining is required.'),
            'doj.date' => __('Please provide a valid date of joining.'),
            'teamId.required' => __('Team is required.'),
            'teamId.exists' => __('Selected team does not exist.'),
            'shiftId.required' => __('Shift is required.'),
            'shiftId.exists' => __('Selected shift does not exist.'),
            'reportingToId.required' => __('Reporting manager is required.'),
            'reportingToId.exists' => __('Selected reporting manager does not exist.'),

            'attendanceType.required' => __('Attendance type is required.'),
            'attendanceType.in' => __('Invalid attendance type selected.'),
            'geofenceGroupId.required_if' => __('Geofence group is required for geofence attendance.'),
            'ipGroupId.required_if' => __('IP group is required for IP address attendance.'),
            'qrGroupId.required_if' => __('QR group is required for static QR attendance.'),
            'siteId.required_if' => __('Site is required for site-based attendance.'),
            'dynamicQrId.required_if' => __('Dynamic QR device is required for dynamic QR attendance.'),

            'baseSalary.required' => __('Base salary is required.'),
            'baseSalary.numeric' => __('Base salary must be a number.'),
            'baseSalary.min' => __('Base salary cannot be negative.'),
            'availableLeaveCount.numeric' => __('Available leave count must be a number.'),
            'availableLeaveCount.min' => __('Available leave count cannot be negative.'),

            'probation_period_months.integer' => __('Probation period must be a whole number.'),
            'probation_period_months.min' => __('Probation period must be at least 1 month.'),
            'probation_period_months.max' => __('Probation period cannot exceed 24 months.'),
            'probation_remarks.max' => __('Probation remarks cannot exceed 1000 characters.'),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Ensure reporting_to_id is not the same as the current user being created
            // This validation happens after creation, so we check in the controller instead

            // Validate attendance type dependencies
            $attendanceType = $this->input('attendanceType');

            if ($attendanceType === 'geofence' && ! $this->input('geofenceGroupId')) {
                $validator->errors()->add('geofenceGroupId', __('Geofence group is required for geofence attendance type.'));
            }

            if ($attendanceType === 'ipAddress' && ! $this->input('ipGroupId')) {
                $validator->errors()->add('ipGroupId', __('IP group is required for IP address attendance type.'));
            }

            if ($attendanceType === 'staticqr' && ! $this->input('qrGroupId')) {
                $validator->errors()->add('qrGroupId', __('QR group is required for static QR attendance type.'));
            }

            if ($attendanceType === 'site' && ! $this->input('siteId')) {
                $validator->errors()->add('siteId', __('Site is required for site-based attendance type.'));
            }

            if ($attendanceType === 'dynamicqr' && ! $this->input('dynamicQrId')) {
                $validator->errors()->add('dynamicQrId', __('Dynamic QR device is required for dynamic QR attendance type.'));
            }
        });
    }
}
