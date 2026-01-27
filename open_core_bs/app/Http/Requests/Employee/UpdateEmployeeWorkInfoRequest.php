<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeWorkInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $employee = $this->route('user') ?? $this->route('employee');

        // Only admin and hr can update work info
        return $this->user()->hasAnyRole(['admin', 'hr']);
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
            'code' => ['required', 'string', 'max:255', 'unique:users,code,'.$employee->id],
            'designation_id' => ['required', 'exists:designations,id'],
            'team_id' => ['required', 'exists:teams,id'],
            'shift_id' => ['required', 'exists:shifts,id'],
            'reporting_to_id' => ['required', 'exists:users,id'],

            // Attendance Type
            'attendanceType' => ['required', 'in:open,geofence,ipAddress,staticqr,dynamicqr,site,face'],
            'geofenceGroupId' => ['required_if:attendanceType,geofence', 'exists:geofence_groups,id'],
            'ipGroupId' => ['required_if:attendanceType,ipAddress', 'exists:ip_address_groups,id'],
            'qrGroupId' => ['required_if:attendanceType,staticqr', 'exists:qr_groups,id'],
            'siteId' => ['required_if:attendanceType,site', 'exists:sites,id'],
            'dynamicQrId' => ['required_if:attendanceType,dynamicqr', 'exists:dynamic_qr_devices,id'],
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
            'code.required' => __('Employee code is required.'),
            'code.unique' => __('This employee code is already in use.'),
            'designation_id.required' => __('Designation is required.'),
            'designation_id.exists' => __('Selected designation does not exist.'),
            'team_id.required' => __('Team is required.'),
            'team_id.exists' => __('Selected team does not exist.'),
            'shift_id.required' => __('Shift is required.'),
            'shift_id.exists' => __('Selected shift does not exist.'),
            'reporting_to_id.required' => __('Reporting manager is required.'),
            'reporting_to_id.exists' => __('Selected reporting manager does not exist.'),

            'attendanceType.required' => __('Attendance type is required.'),
            'attendanceType.in' => __('Invalid attendance type selected.'),
            'geofenceGroupId.required_if' => __('Geofence group is required for geofence attendance.'),
            'ipGroupId.required_if' => __('IP group is required for IP address attendance.'),
            'qrGroupId.required_if' => __('QR group is required for static QR attendance.'),
            'siteId.required_if' => __('Site is required for site-based attendance.'),
            'dynamicQrId.required_if' => __('Dynamic QR device is required for dynamic QR attendance.'),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $employee = $this->route('user') ?? $this->route('employee');

            // Ensure reporting_to_id is not the employee themselves
            if ($this->input('reporting_to_id') == $employee->id) {
                $validator->errors()->add('reporting_to_id', __('An employee cannot report to themselves.'));
            }

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
