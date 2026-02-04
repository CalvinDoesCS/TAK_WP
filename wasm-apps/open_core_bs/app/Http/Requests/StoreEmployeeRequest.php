<?php

namespace App\Http\Requests;

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
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Step 1: Personal Information
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:15|unique:users,phone',
            'code' => 'required|string|max:255|unique:users,code',
            'gender' => ['required', Rule::in(array_column(Gender::cases(), 'value'))],
            'dob' => 'required|date',
            'bloodGroup' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'altPhone' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:255',
            'emergencyContactName' => 'nullable|string|max:100',
            'emergencyContactRelationship' => 'nullable|string|max:50',
            'emergencyContactPhone' => 'nullable|string|max:20',
            'emergencyContactAddress' => 'nullable|string|max:500',
            'file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            // Step 2: Employment & Account Details
            'doj' => 'required|date',
            'designationId' => 'required|exists:designations,id',
            'teamId' => 'required|exists:teams,id',
            'shiftId' => 'required|exists:shifts,id',
            'reportingToId' => 'required|exists:users,id',
            'role' => 'required|exists:roles,name',
            'useDefaultPassword' => 'nullable',
            'password' => 'nullable|min:8',
            'confirmPassword' => 'nullable|min:8|same:password',

            // Step 2: Attendance Configuration
            'attendanceType' => 'required|in:open,geofence,ipAddress,staticqr,dynamicqr,site,face',
            'geofenceGroupId' => 'required_if:attendanceType,geofence|exists:geofence_groups,id',
            'ipGroupId' => 'required_if:attendanceType,ipAddress|exists:ip_address_groups,id',
            'qrGroupId' => 'required_if:attendanceType,staticqr|exists:qr_groups,id',
            'siteId' => 'required_if:attendanceType,site|exists:sites,id',
            'dynamicQrId' => 'required_if:attendanceType,dynamicqr|exists:dynamic_qr_devices,id',

            // Step 2: Probation Period (Optional)
            'probationPeriodMonths' => 'nullable|integer|min:1|max:12',
            'probationRemarks' => 'nullable|string|max:2000',
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
            // Personal Information
            'firstName.required' => __('First name is required'),
            'lastName.required' => __('Last name is required'),
            'email.required' => __('Email address is required'),
            'email.email' => __('Please enter a valid email address'),
            'email.unique' => __('This email address is already registered'),
            'phone.required' => __('Phone number is required'),
            'phone.unique' => __('This phone number is already registered'),
            'code.required' => __('Employee code is required'),
            'code.unique' => __('This employee code is already in use'),
            'gender.required' => __('Gender is required'),
            'gender.in' => __('Please select a valid gender'),
            'dob.required' => __('Date of birth is required'),
            'dob.date' => __('Please enter a valid date'),
            'file.image' => __('Profile picture must be an image'),
            'file.mimes' => __('Profile picture must be a JPEG, JPG, or PNG file'),
            'file.max' => __('Profile picture must not exceed 2MB'),

            // Employment Details
            'doj.required' => __('Date of joining is required'),
            'doj.date' => __('Please enter a valid date'),
            'designationId.required' => __('Designation is required'),
            'designationId.exists' => __('Selected designation is invalid'),
            'teamId.required' => __('Team is required'),
            'teamId.exists' => __('Selected team is invalid'),
            'shiftId.required' => __('Shift is required'),
            'shiftId.exists' => __('Selected shift is invalid'),
            'reportingToId.required' => __('Reporting manager is required'),
            'reportingToId.exists' => __('Selected reporting manager is invalid'),

            // Account Settings
            'role.required' => __('Role is required'),
            'role.exists' => __('Selected role is invalid'),
            'password.min' => __('Password must be at least 6 characters'),
            'confirmPassword.min' => __('Password must be at least 6 characters'),
            'confirmPassword.same' => __('Passwords do not match'),

            // Attendance Configuration
            'attendanceType.required' => __('Attendance type is required'),
            'attendanceType.in' => __('Selected attendance type is invalid'),
            'geofenceGroupId.required_if' => __('Geofence group is required for geofence attendance'),
            'ipGroupId.required_if' => __('IP group is required for IP-based attendance'),
            'qrGroupId.required_if' => __('QR group is required for static QR attendance'),
            'siteId.required_if' => __('Site is required for site-based attendance'),
            'dynamicQrId.required_if' => __('Dynamic QR device is required for dynamic QR attendance'),

            // Probation Period
            'probationPeriodMonths.integer' => __('Probation period must be a number'),
            'probationPeriodMonths.min' => __('Probation period must be at least 1 month'),
            'probationPeriodMonths.max' => __('Probation period cannot exceed 12 months'),
            'probationRemarks.max' => __('Probation notes cannot exceed 2000 characters'),
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
            'email' => __('email'),
            'phone' => __('phone number'),
            'code' => __('employee code'),
            'gender' => __('gender'),
            'dob' => __('date of birth'),
            'doj' => __('date of joining'),
            'designationId' => __('designation'),
            'teamId' => __('team'),
            'shiftId' => __('shift'),
            'reportingToId' => __('reporting manager'),
            'role' => __('role'),
            'attendanceType' => __('attendance type'),
            'probationPeriodMonths' => __('probation period'),
            'probationRemarks' => __('probation notes'),
        ];
    }
}
