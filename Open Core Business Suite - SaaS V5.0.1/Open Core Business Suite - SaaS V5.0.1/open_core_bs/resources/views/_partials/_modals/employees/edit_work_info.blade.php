@php use Carbon\Carbon; @endphp
  <!-- Edit Work Information Modal -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditWorkInfo"
     aria-labelledby="offcanvasEditWorkInfoLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasEditWorkInfoLabel" class="offcanvas-title">@lang('Edit Work Information')</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
    <form id="workInfoForm" action="{{route('employees.updateWorkInformation')}}" method="POST">
      @csrf
      <input type="hidden" name="id" id="id" value="{{ $user->id }}">

      <!-- Designation -->
      <div class="mb-4">
        <label class="form-label" for="designationId">@lang('Designation') <span class="text-danger">*</span></label>
        <select class="form-select select2" id="designationId" name="designationId">
          <option value="">Select Designation</option>
        </select>
      </div>

      <!-- Role Dropdown -->
      <div class="mb-4">
        <label class="form-label" for="role">@lang('Role') <span class="text-danger">*</span></label>
        <select class="form-select select2" id="role" name="role">
          <option value="">Select Role</option>
        </select>
      </div>

      <!-- Team Dropdown -->
      <div class="mb-4">
        <label class="form-label" for="teamId">@lang('Team') <span class="text-danger">*</span></label>
        <select class="form-select select2" id="teamId" name="teamId">
          <option value="">Select Team</option>
        </select>
      </div>

      <!-- Shift Dropdown -->
      <div class="mb-4">
        <label class="form-label" for="shiftId">@lang('Shift') <span class="text-danger">*</span></label>
        <select class="form-select select2" id="shiftId" name="shiftId">
          <option value="">Select Shift</option>
        </select>
      </div>

      <!-- Reporting To Dropdown -->
      <div class="mb-4">
        <label class="form-label" for="reportingToId">@lang('Reporting To') <span class="text-danger">*</span></label>
        <select class="form-select select2" id="reportingToId" name="reportingToId">
          <option value="">Select Reporting To</option>
        </select>
      </div>

      <div class="mb-4">
        <label class="form-label" for="doj">Date of Joining <span class="text-danger">*</span></label>
        <input type="date" name="doj" id="doj" class="form-control"
               value="{{ $user->date_of_joining != null ? Carbon::parse($user->date_of_joining)->format('Y-m-d') : '' }}"/>
      </div>

      <div class="mb-4">
        <label class="form-label" for="attendanceType"> @lang('Attendance Type') <span
            class="text-danger">*</span></label>
        <select class="form-control" id="attendanceType" name="attendanceType">
          <option value="open" {{ $user->attendance_type == 'open' ? 'selected' : '' }}>Open</option>
          @if($enabledModules['GeofenceSystem'] ?? false)
            <option value="geofence" {{ $user->attendance_type == 'geofence' ? 'selected' : '' }}>Geofence</option>
          @endif
          @if($enabledModules['IpAddressAttendance'] ?? false)
            <option value="ipAddress" {{ $user->attendance_type == 'ip_address' ? 'selected' : '' }}>IP Address</option>
          @endif
          @if($enabledModules['QrAttendance'] ?? false)
            <option value="staticqr" {{ $user->attendance_type == 'qr_code' ? 'selected' : '' }}>Static QR</option>
          @endif
          @if($enabledModules['DynamicQrAttendance'] ?? false)
            <option value="dynamicqr" {{ $user->attendance_type == 'dynamic_qr' ? 'selected' : '' }}>Dynamic QR</option>
          @endif
          @if($enabledModules['SiteAttendance'] ?? false)
            <option value="site" {{ $user->attendance_type == 'site' ? 'selected' : '' }}>Site</option>
          @endif
          @if($enabledModules['FaceAttendance'] ?? false)
            <option value="face" {{ $user->attendance_type == 'face_recognition' ? 'selected' : '' }}>Face</option>
          @endif
        </select>
      </div>
      <div class="mb-4 attendance-type-field" id="geofenceGroupDiv" style="display:none;">
        <label for="geofenceGroupId" class="control-label">@lang('Geofence Group')</label>
        <select id="geofenceGroupId" name="geofenceGroupId" class="form-select mb-3"></select>
        <span class="text-danger">{{ $errors->first('geofenceGroupId', ':message') }}</span>
      </div>
      <div class="mb-4 attendance-type-field" id="ipGroupDiv" style="display:none;">
        <label for="ipGroupId" class="control-label">@lang('IP Group')</label>
        <select id="ipGroupId" name="ipGroupId" class="form-select mb-3"></select>
        <span class="text-danger">{{ $errors->first('ipGroupId', ':message') }}</span>
      </div>
      <div class="mb-4 attendance-type-field" id="qrGroupDiv" style="display:none;">
        <label for="qrGroupId" class="control-label">@lang('QR Group')</label>
        <select id="qrGroupId" name="qrGroupId" class="form-select mb-3"></select>
        <span class="text-danger">{{ $errors->first('qrGroupId', ':message') }}</span>
      </div>
      <div class="mb-4 attendance-type-field" id="dynamicQrDiv" style="display:none;">
        <label for="dynamicQrId" class="control-label">@lang('QR Device')</label>
        <select id="dynamicQrId" name="dynamicQrId" class="form-select mb-3"></select>
        <span class="text-danger">{{ $errors->first('dynamicQrId', ':message') }}</span>
      </div>
      <div class="mb-4 attendance-type-field" id="siteDiv" style="display:none;">
        <label for="siteId" class="control-label">@lang('Site')</label>
        <select id="siteId" name="siteId" class="form-select mb-3"></select>
        <span class="text-danger">{{ $errors->first('siteId', ':message') }}</span>
      </div>

      <button type="submit" class="btn btn-primary me-3">@lang('Save Changes')</button>
      <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">@lang('Cancel')</button>
    </form>
  </div>
</div>

<!-- /Edit Work Information Modal -->
