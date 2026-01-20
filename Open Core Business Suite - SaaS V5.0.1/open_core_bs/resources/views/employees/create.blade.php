@php
  use App\Enums\Gender;
  $title = __('Create Employee');
@endphp

@extends('layouts/layoutMaster')

@section('title', $title)

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
    'resources/assets/vendor/libs/dropzone/dropzone.scss',
    'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
  ])
  <style>
    .dropzone {
      min-height: 180px;
      border: 2px dashed var(--bs-border-color);
      background: var(--bs-body-bg);
      border-radius: 6px;
      padding: 20px;
    }
    .dropzone .dz-message {
      text-align: center;
      margin: 2em 0;
    }
    .dropzone .dz-message .text-muted {
      opacity: 0.7;
    }
    .dropzone .dz-preview .dz-image {
      border-radius: 6px;
      width: 120px;
      height: 120px;
    }
    .dropzone:hover {
      border-color: var(--bs-primary);
      background: var(--bs-body-bg);
    }
    .review-section {
      padding: 1rem;
      background: var(--bs-gray-100);
      border: 1px solid var(--bs-border-color);
      border-radius: 6px;
      margin-bottom: 1rem;
    }
    .review-section h6 {
      margin-bottom: 0.75rem;
      font-weight: 600;
      color: var(--bs-heading-color);
    }
    .review-item {
      display: flex;
      justify-content: space-between;
      padding: 0.5rem 0;
      border-bottom: 1px solid var(--bs-border-color);
    }
    .review-item:last-child {
      border-bottom: none;
    }
    .review-item-label {
      font-weight: 500;
      color: var(--bs-secondary-color);
    }
    .review-item-value {
      color: var(--bs-body-color);
      text-align: right;
    }

    /* Dark mode specific adjustments */
    [data-bs-theme="dark"] .review-section {
      background: var(--bs-gray-dark);
    }
  </style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    'resources/assets/vendor/libs/dropzone/dropzone.js',
    'resources/assets/vendor/libs/bs-stepper/bs-stepper.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
  ])
@endsection

<!-- Page Scripts -->
@section('page-script')
  @vite(['resources/assets/js/app/employee-builder.js'])
  <script>
    // Pass data to JavaScript using pageData pattern
    const employeeData = {
      urls: {
        store: "{{ route('employees.store') }}",
        index: "{{ route('employees.index') }}",
        validateEmail: "{{ route('employees.validate.email') }}",
        validatePhone: "{{ route('employees.validate.phone') }}",
        validateCode: "{{ route('employees.validate.code') }}",
        getDynamicQrDevices: "{{ route('employee.getDynamicQrDevices') }}",
        getGeofenceGroups: "{{ route('employee.getGeofenceGroups') }}",
        getIpGroups: "{{ route('employee.getIpGroups') }}",
        getQrGroups: "{{ route('employee.getQrGroups') }}",
        getSites: "{{ route('employee.getSites') }}"
      },
      labels: {
        // Dropzone
        dropzoneMessage: "{{ __('Drop profile picture here or click to upload') }}",
        remove: "{{ __('Remove') }}",
        maxFilesExceeded: "{{ __('Only one file allowed') }}",
        invalidFileType: "{{ __('Only image files are allowed') }}",
        fileTooBig: "{{ __('File is too big (Max: 5MB)') }}",

        // Validation messages
        invalidEmail: "{{ __('Invalid email format') }}",
        emailAvailable: "{{ __('Email is available') }}",
        emailExists: "{{ __('Email already exists') }}",
        phoneAvailable: "{{ __('Phone is available') }}",
        phoneExists: "{{ __('Phone already exists') }}",
        codeAvailable: "{{ __('Employee code is available') }}",
        codeExists: "{{ __('Employee code already exists') }}",
        validationError: "{{ __('Validation error occurred') }}",

        // Required field messages
        firstNameRequired: "{{ __('First name is required') }}",
        lastNameRequired: "{{ __('Last name is required') }}",
        emailRequired: "{{ __('Email is required') }}",
        emailNotValid: "{{ __('Email is not valid or already exists') }}",
        phoneRequired: "{{ __('Phone number is required') }}",
        phoneNotValid: "{{ __('Phone number is not valid or already exists') }}",
        codeRequired: "{{ __('Employee code is required') }}",
        codeNotValid: "{{ __('Employee code is not valid or already exists') }}",
        genderRequired: "{{ __('Gender is required') }}",
        dobRequired: "{{ __('Date of birth is required') }}",
        dojRequired: "{{ __('Date of joining is required') }}",
        designationRequired: "{{ __('Designation is required') }}",
        teamRequired: "{{ __('Team is required') }}",
        reportingManagerRequired: "{{ __('Reporting manager is required') }}",
        shiftRequired: "{{ __('Shift is required') }}",
        roleRequired: "{{ __('Role is required') }}",
        passwordRequired: "{{ __('Password is required') }}",
        passwordMinLength: "{{ __('Password must be at least 6 characters') }}",
        passwordMismatch: "{{ __('Passwords do not match') }}",
        attendanceTypeRequired: "{{ __('Attendance type is required') }}",
        geofenceGroupRequired: "{{ __('Geofence group is required') }}",
        ipGroupRequired: "{{ __('IP group is required') }}",
        qrGroupRequired: "{{ __('QR group is required') }}",
        siteRequired: "{{ __('Site is required') }}",
        dynamicQrDeviceRequired: "{{ __('Dynamic QR device is required') }}",

        // Attendance type messages
        createDynamicQrDevice: "{{ __('Please create a dynamic QR device first') }}",
        selectDynamicQrDevice: "{{ __('Select a dynamic QR device') }}",
        createGeofenceGroup: "{{ __('Please create a geofence group first') }}",
        selectGeofenceGroup: "{{ __('Select a geofence group') }}",
        createIpGroup: "{{ __('Please create an IP group first') }}",
        selectIpGroup: "{{ __('Select an IP group') }}",
        createQrGroup: "{{ __('Please create a QR group first') }}",
        selectQrGroup: "{{ __('Select a QR group') }}",
        createSite: "{{ __('Please create a site first') }}",
        selectSite: "{{ __('Select a site') }}",

        // General messages
        success: "{{ __('Success!') }}",
        error: "{{ __('Error!') }}",
        validationWarning: "{{ __('Validation Warning') }}",
        employeeCreated: "{{ __('Employee created successfully') }}",
        createFailed: "{{ __('Failed to create employee') }}",
        creating: "{{ __('Creating...') }}",
        createEmployee: "{{ __('Create Employee') }}",
        defaultPassword: "{{ __('Default Password') }}",
        customPassword: "{{ __('Custom Password') }}",
        noRestrictions: "{{ __('No restrictions') }}",
        noProbation: "{{ __('No probation period') }}",
        months: "{{ __('month(s)') }}"
      }
    };
  </script>
@endsection

@section('content')
  {{-- Breadcrumbs --}}
  <x-breadcrumb
    :title="$title"
    :breadcrumbs="[
        ['name' => __('Employees'), 'url' => route('employees.index')],
        ['name' => __('Create Employee')]
    ]"
    :home-url="url('/')"
  />

  <div class="row">
    <div class="col-12">
      {{-- Employee Builder with Stepper --}}
      <div class="card">
        <div class="card-body">
          <div class="bs-stepper wizard-modern wizard-modern-example" id="employeeBuilderStepper">
            <div class="bs-stepper-header">
              <div class="step" data-target="#step-personal-info">
                <button type="button" class="step-trigger">
                  <span class="bs-stepper-circle">
                    <i class='bx bx-user'></i>
                  </span>
                  <span class="bs-stepper-label">
                    <span class="bs-stepper-title">{{ __('Personal Information') }}</span>
                    <span class="bs-stepper-subtitle">{{ __('Basic details & validation') }}</span>
                  </span>
                </button>
              </div>
              <div class="line"></div>
              <div class="step" data-target="#step-employment-account">
                <button type="button" class="step-trigger">
                  <span class="bs-stepper-circle">
                    <i class='bx bx-briefcase'></i>
                  </span>
                  <span class="bs-stepper-label">
                    <span class="bs-stepper-title">{{ __('Employment & Account') }}</span>
                    <span class="bs-stepper-subtitle">{{ __('Work details & access') }}</span>
                  </span>
                </button>
              </div>
              <div class="line"></div>
              <div class="step" data-target="#step-review">
                <button type="button" class="step-trigger">
                  <span class="bs-stepper-circle">
                    <i class='bx bx-check-circle'></i>
                  </span>
                  <span class="bs-stepper-label">
                    <span class="bs-stepper-title">{{ __('Review & Create') }}</span>
                    <span class="bs-stepper-subtitle">{{ __('Confirm details') }}</span>
                  </span>
                </button>
              </div>
            </div>

            <div class="bs-stepper-content">
              <form id="employeeBuilderForm">
                @csrf
              {{-- Step 1: Personal Information --}}
              <div id="step-personal-info" class="content">
                <div class="content-header mb-4">
                  <h4 class="mb-1">{{ __('Personal Information') }}</h4>
                  <p class="text-muted">{{ __('Enter basic employee details. Email, phone, and employee code will be validated in real-time.') }}</p>
                </div>

                <div class="row g-3">
                  {{-- Profile Picture --}}
                  <div class="col-12">
                    <label class="form-label">{{ __('Profile Picture') }}</label>
                    <div id="profilePictureDropzone" class="dropzone needsclick">
                      <div class="dz-message needsclick">
                        <i class="bx bx-cloud-upload display-4 text-muted mb-3"></i>
                        <h6 class="mb-2">{{ __('Drop profile picture here or click to upload') }}</h6>
                        <small class="text-muted d-block">{{ __('Only .jpg, .jpeg, .png files up to 5MB') }}</small>
                      </div>
                    </div>
                  </div>

                  {{-- Employee Code --}}
                  <div class="col-md-4">
                    <label class="form-label" for="code">{{ __('Employee Code') }} <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="code" class="form-control" placeholder="{{ __('Enter employee code') }}" required/>
                    <small class="text-muted">{{ __('Unique identifier for the employee') }}</small>
                  </div>

                  {{-- Email --}}
                  <div class="col-md-4">
                    <label class="form-label" for="email">{{ __('Email') }} <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="{{ __('Enter email address') }}" required/>
                    <small class="text-muted">{{ __('Used for login and notifications') }}</small>
                  </div>

                  {{-- Phone --}}
                  <div class="col-md-4">
                    <label class="form-label" for="phone">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                    <input type="number" name="phone" id="phone" class="form-control" placeholder="{{ __('Enter phone number') }}" required/>
                    <small class="text-muted">{{ __('Primary contact number') }}</small>
                  </div>

                  {{-- First Name --}}
                  <div class="col-md-6">
                    <label class="form-label" for="firstName">{{ __('First Name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="firstName" id="firstName" class="form-control" placeholder="{{ __('Enter first name') }}" required/>
                  </div>

                  {{-- Last Name --}}
                  <div class="col-md-6">
                    <label class="form-label" for="lastName">{{ __('Last Name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="lastName" id="lastName" class="form-control" placeholder="{{ __('Enter last name') }}" required/>
                  </div>

                  {{-- Gender --}}
                  <div class="col-md-6">
                    <label class="form-label" for="gender">{{ __('Gender') }} <span class="text-danger">*</span></label>
                    <select class="form-select" id="gender" name="gender" required>
                      <option value="">{{ __('Select Gender') }}</option>
                      @foreach(Gender::cases() as $gender)
                        <option value="{{$gender->value}}">{{ucfirst($gender->value)}}</option>
                      @endforeach
                    </select>
                  </div>

                  {{-- Date of Birth --}}
                  <div class="col-md-6">
                    <label class="form-label" for="dob">{{ __('Date of Birth') }} <span class="text-danger">*</span></label>
                    <input type="text" name="dob" id="dob" class="form-control" placeholder="{{ __('Select date of birth') }}" required/>
                  </div>

                  {{-- Blood Group --}}
                  <div class="col-md-6">
                    <label class="form-label" for="bloodGroup">{{ __('Blood Group') }}</label>
                    <select class="form-select" id="bloodGroup" name="bloodGroup">
                      <option value="">{{ __('Select Blood Group') }}</option>
                      <option value="A+">A+</option>
                      <option value="A-">A-</option>
                      <option value="B+">B+</option>
                      <option value="B-">B-</option>
                      <option value="AB+">AB+</option>
                      <option value="AB-">AB-</option>
                      <option value="O+">O+</option>
                      <option value="O-">O-</option>
                    </select>
                  </div>

                  {{-- Alternative Phone --}}
                  <div class="col-md-6">
                    <label class="form-label" for="altPhone">{{ __('Alternative Phone') }}</label>
                    <input type="number" name="altPhone" id="altPhone" class="form-control" placeholder="{{ __('Enter alternate number') }}"/>
                  </div>

                  {{-- Address --}}
                  <div class="col-12">
                    <label class="form-label" for="address">{{ __('Address') }}</label>
                    <textarea name="address" id="address" class="form-control" rows="2" placeholder="{{ __('Enter complete address') }}"></textarea>
                  </div>

                  {{-- Emergency Contact Section --}}
                  <div class="col-12 mt-4">
                    <h6 class="text-muted mb-3">
                      <i class='bx bx-phone-call me-2'></i>{{ __('Emergency Contact Information') }}
                    </h6>
                  </div>

                  {{-- Emergency Contact Name --}}
                  <div class="col-md-6">
                    <label class="form-label" for="emergencyContactName">{{ __('Contact Name') }}</label>
                    <input type="text" name="emergencyContactName" id="emergencyContactName" class="form-control" placeholder="{{ __('Enter emergency contact name') }}"/>
                  </div>

                  {{-- Emergency Contact Relationship --}}
                  <div class="col-md-6">
                    <label class="form-label" for="emergencyContactRelationship">{{ __('Relationship') }}</label>
                    <select class="form-select" id="emergencyContactRelationship" name="emergencyContactRelationship">
                      <option value="">{{ __('Select Relationship') }}</option>
                      <option value="Spouse">{{ __('Spouse') }}</option>
                      <option value="Parent">{{ __('Parent') }}</option>
                      <option value="Sibling">{{ __('Sibling') }}</option>
                      <option value="Child">{{ __('Child') }}</option>
                      <option value="Friend">{{ __('Friend') }}</option>
                      <option value="Other">{{ __('Other') }}</option>
                    </select>
                  </div>

                  {{-- Emergency Contact Phone --}}
                  <div class="col-md-6">
                    <label class="form-label" for="emergencyContactPhone">{{ __('Contact Phone') }}</label>
                    <input type="number" name="emergencyContactPhone" id="emergencyContactPhone" class="form-control" placeholder="{{ __('Enter emergency contact phone') }}"/>
                  </div>

                  {{-- Emergency Contact Address --}}
                  <div class="col-md-6">
                    <label class="form-label" for="emergencyContactAddress">{{ __('Contact Address') }}</label>
                    <textarea name="emergencyContactAddress" id="emergencyContactAddress" class="form-control" rows="1" placeholder="{{ __('Enter emergency contact address') }}"></textarea>
                  </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                  <a href="{{ route('employees.index') }}" class="btn btn-label-secondary">
                    <i class='bx bx-arrow-back me-1'></i>{{ __('Back to List') }}
                  </a>
                  <button type="button" class="btn btn-primary" id="btnNextStep1" disabled>
                    {{ __('Next') }}<i class='bx bx-arrow-back bx-rotate-180 ms-1'></i>
                  </button>
                </div>
              </div>

              {{-- Step 2: Employment & Account Details --}}
              <div id="step-employment-account" class="content">
                <div class="content-header mb-4">
                  <h4 class="mb-1">{{ __('Employment & Account Details') }}</h4>
                  <p class="text-muted">{{ __('Configure work-related information, account access, and attendance settings.') }}</p>
                </div>

                <div class="row g-3">
                  {{-- Employment Details Section --}}
                  <div class="col-12">
                    <h6 class="text-primary mb-3"><i class="bx bx-briefcase me-1"></i>{{ __('Employment Details') }}</h6>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label" for="doj">{{ __('Date of Joining') }} <span class="text-danger">*</span></label>
                    <input type="text" id="doj" name="doj" class="form-control" placeholder="{{ __('Select joining date') }}" required/>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label" for="designationId">{{ __('Designation') }} <span class="text-danger">*</span></label>
                    <select class="form-select select2" id="designationId" name="designationId" required>
                      <option value="">{{ __('Select a designation') }}</option>
                      @foreach ($designations as $designation)
                        <option value="{{$designation->id}}">{{$designation->name}}</option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label" for="teamId">{{ __('Team') }} <span class="text-danger">*</span></label>
                    <select class="form-select select2" id="teamId" name="teamId" required>
                      <option value="">{{ __('Select a team') }}</option>
                      @foreach ($teams as $team)
                        <option value="{{$team->id}}">{{$team->code}} - {{$team->name}}</option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label" for="reportingToId">{{ __('Reporting Manager') }} <span class="text-danger">*</span></label>
                    <select class="form-select select2" id="reportingToId" name="reportingToId" required>
                      <option value="">{{ __('Select reporting manager') }}</option>
                      @foreach ($users as $user)
                        <option value="{{$user->id}}">{{$user->code}}: {{$user->first_name.' '.$user->last_name}}</option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label" for="shiftId">{{ __('Shift') }} <span class="text-danger">*</span></label>
                    <select class="form-select select2" id="shiftId" name="shiftId" required>
                      <option value="">{{ __('Select a shift') }}</option>
                      @foreach ($shifts as $shift)
                        <option value="{{$shift->id}}">{{$shift->code}} - {{$shift->name}}</option>
                      @endforeach
                    </select>
                  </div>

                  {{-- Account Settings Section --}}
                  <div class="col-12 mt-4">
                    <h6 class="text-primary mb-3"><i class="bx bx-lock-alt me-1"></i>{{ __('Account Settings') }}</h6>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label" for="role">{{ __('Role') }} <span class="text-danger">*</span></label>
                    <select class="form-select select2" id="role" name="role" required>
                      <option value="">{{ __('Select Role') }}</option>
                      @foreach ($roles as $role)
                        <option value="{{$role->name}}">{{$role->name}}</option>
                      @endforeach
                    </select>
                    <small class="text-muted">{{ __('Defines user permissions and access level') }}</small>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label d-block">&nbsp;</label>
                    <div class="form-check form-switch mt-2">
                      <input class="form-check-input" type="checkbox" id="useDefaultPassword" name="useDefaultPassword" checked>
                      <label class="form-check-label" for="useDefaultPassword">{{ __('Use Default Password') }}</label>
                    </div>
                    @if($settings->is_helper_text_enabled)
                      <small class="text-muted">{{ __('Default password:') }} <strong>{{$settings->default_password}}</strong></small>
                    @endif
                  </div>

                  <div class="col-12" id="passwordDiv" style="display: none;">
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label" for="password">{{ __('Password') }} <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="{{ __('Enter password') }}"/>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label" for="confirmPassword">{{ __('Confirm Password') }} <span class="text-danger">*</span></label>
                        <input type="password" name="confirmPassword" id="confirmPassword" class="form-control" placeholder="{{ __('Re-enter password') }}"/>
                      </div>
                    </div>
                  </div>

                  {{-- Attendance Configuration Section --}}
                  <div class="col-12 mt-4">
                    <h6 class="text-primary mb-3"><i class="bx bx-calendar-check me-1"></i>{{ __('Attendance Configuration') }}</h6>
                  </div>

                  <div class="col-12">
                    <label class="form-label" for="attendanceType">{{ __('Attendance Type') }} <span class="text-danger">*</span></label>
                    <select class="form-select" id="attendanceType" name="attendanceType" required>
                      <option value="open" selected>{{ __('Open - No Restrictions') }}</option>
                      @if($enabledModules['GeofenceSystem'] ?? false)
                        <option value="geofence">{{ __('Geofence - Location Based') }}</option>
                      @endif
                      @if($enabledModules['IpAddressAttendance'] ?? false)
                        <option value="ipAddress">{{ __('IP Address - Network Based') }}</option>
                      @endif
                      @if($enabledModules['QrAttendance'] ?? false)
                        <option value="staticqr">{{ __('Static QR Code') }}</option>
                      @endif
                      @if($enabledModules['DynamicQrAttendance'] ?? false)
                        <option value="dynamicqr">{{ __('Dynamic QR Code') }}</option>
                      @endif
                      @if($enabledModules['SiteAttendance'] ?? false)
                        <option value="site">{{ __('Site - Multi-method Site Based') }}</option>
                      @endif
                      @if($enabledModules['FaceAttendance'] ?? false)
                        <option value="face">{{ __('Face Recognition') }}</option>
                      @endif
                    </select>
                    <small class="text-muted">{{ __('Choose how this employee will mark attendance') }}</small>
                  </div>

                  {{-- Dynamic Attendance Type Fields --}}
                  <div class="col-12 attendance-type-field" id="geofenceGroupDiv" style="display:none;">
                    <label for="geofenceGroupId" class="form-label">{{ __('Geofence Group') }} <span class="text-danger">*</span></label>
                    <select id="geofenceGroupId" name="geofenceGroupId" class="form-select"></select>
                  </div>

                  <div class="col-12 attendance-type-field" id="ipGroupDiv" style="display:none;">
                    <label for="ipGroupId" class="form-label">{{ __('IP Address Group') }} <span class="text-danger">*</span></label>
                    <select id="ipGroupId" name="ipGroupId" class="form-select"></select>
                  </div>

                  <div class="col-12 attendance-type-field" id="qrGroupDiv" style="display:none;">
                    <label for="qrGroupId" class="form-label">{{ __('QR Code Group') }} <span class="text-danger">*</span></label>
                    <select id="qrGroupId" name="qrGroupId" class="form-select"></select>
                  </div>

                  <div class="col-12 attendance-type-field" id="dynamicQrDiv" style="display:none;">
                    <label for="dynamicQrId" class="form-label">{{ __('QR Device') }} <span class="text-danger">*</span></label>
                    <select id="dynamicQrId" name="dynamicQrId" class="form-select"></select>
                  </div>

                  <div class="col-12 attendance-type-field" id="siteDiv" style="display:none;">
                    <label for="siteId" class="form-label">{{ __('Site') }} <span class="text-danger">*</span></label>
                    <select id="siteId" name="siteId" class="form-select"></select>
                  </div>

                  {{-- Probation Period Section --}}
                  <div class="col-12 mt-4">
                    <h6 class="text-primary mb-3"><i class="bx bx-time-five me-1"></i>{{ __('Probation Period') }} <span class="badge bg-label-secondary">{{ __('Optional') }}</span></h6>
                  </div>

                  <div class="col-md-4">
                    <label for="probationPeriodMonths" class="form-label">{{ __('Probation Duration') }}</label>
                    <select class="form-select" id="probationPeriodMonths" name="probationPeriodMonths">
                      <option value="">{{ __('No Probation Period') }}</option>
                      @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ $i }} {{ __('Month(s)') }}</option>
                      @endfor
                    </select>
                  </div>

                  <div class="col-md-8">
                    <label for="probationEndDate" class="form-label">{{ __('Probation End Date') }}</label>
                    <input type="text" class="form-control" id="probationEndDate" readonly placeholder="{{ __('Auto-calculated') }}">
                    <small class="text-muted">{{ __('Based on joining date and duration') }}</small>
                  </div>

                  <div class="col-12">
                    <label for="probationRemarks" class="form-label">{{ __('Probation Notes') }}</label>
                    <textarea class="form-control" id="probationRemarks" name="probationRemarks" rows="2" placeholder="{{ __('Optional notes about probation period') }}"></textarea>
                  </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                  <button type="button" class="btn btn-label-secondary" id="btnPrevStep2">
                    <i class='bx bx-arrow-back me-1'></i>{{ __('Previous') }}
                  </button>
                  <button type="button" class="btn btn-primary" id="btnNextStep2">
                    {{ __('Review') }}<i class='bx bx-arrow-back bx-rotate-180 ms-1'></i>
                  </button>
                </div>
              </div>

              {{-- Step 3: Review & Create --}}
              <div id="step-review" class="content">
                <div class="content-header mb-4">
                  <h4 class="mb-1">{{ __('Review & Create') }}</h4>
                  <p class="text-muted">{{ __('Review all employee information before creating the account.') }}</p>
                </div>

                {{-- Personal Information Review --}}
                <div class="review-section">
                  <h6><i class="bx bx-user me-1"></i>{{ __('Personal Information') }}</h6>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('First Name') }}</span>
                    <span class="review-item-value" id="reviewFirstName">-</span>
                  </div>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Last Name') }}</span>
                    <span class="review-item-value" id="reviewLastName">-</span>
                  </div>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Employee Code') }}</span>
                    <span class="review-item-value" id="reviewCode">-</span>
                  </div>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Email') }}</span>
                    <span class="review-item-value" id="reviewEmail">-</span>
                  </div>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Phone') }}</span>
                    <span class="review-item-value" id="reviewPhone">-</span>
                  </div>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Alternative Phone') }}</span>
                    <span class="review-item-value" id="reviewAltPhone">-</span>
                  </div>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Gender') }}</span>
                    <span class="review-item-value" id="reviewGender">-</span>
                  </div>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Date of Birth') }}</span>
                    <span class="review-item-value" id="reviewDob">-</span>
                  </div>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Address') }}</span>
                    <span class="review-item-value" id="reviewAddress">-</span>
                  </div>
                </div>

                {{-- Employment Details Review --}}
                <div class="review-section">
                  <h6><i class="bx bx-briefcase me-1"></i>{{ __('Employment Details') }}</h6>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Date of Joining') }}</span>
                    <span class="review-item-value" id="reviewDoj">-</span>
                  </div>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Designation') }}</span>
                    <span class="review-item-value" id="reviewDesignation">-</span>
                  </div>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Team') }}</span>
                    <span class="review-item-value" id="reviewTeam">-</span>
                  </div>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Reporting Manager') }}</span>
                    <span class="review-item-value" id="reviewReportingManager">-</span>
                  </div>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Shift') }}</span>
                    <span class="review-item-value" id="reviewShift">-</span>
                  </div>
                </div>

                {{-- Account Settings Review --}}
                <div class="review-section">
                  <h6><i class="bx bx-lock-alt me-1"></i>{{ __('Account Settings') }}</h6>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Role') }}</span>
                    <span class="review-item-value" id="reviewRole">-</span>
                  </div>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Password') }}</span>
                    <span class="review-item-value" id="reviewPassword">-</span>
                  </div>
                </div>

                {{-- Attendance Configuration Review --}}
                <div class="review-section">
                  <h6><i class="bx bx-calendar-check me-1"></i>{{ __('Attendance Configuration') }}</h6>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Attendance Type') }}</span>
                    <span class="review-item-value" id="reviewAttendanceType">-</span>
                  </div>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Configuration') }}</span>
                    <span class="review-item-value" id="reviewAttendanceDetails">-</span>
                  </div>
                </div>

                {{-- Probation Period Review --}}
                <div class="review-section">
                  <h6><i class="bx bx-time-five me-1"></i>{{ __('Probation Period') }}</h6>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('Duration') }}</span>
                    <span class="review-item-value" id="reviewProbation">-</span>
                  </div>
                  <div class="review-item">
                    <span class="review-item-label">{{ __('End Date') }}</span>
                    <span class="review-item-value" id="reviewProbationEndDate">-</span>
                  </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                  <button type="button" class="btn btn-label-secondary" id="btnPrevStep3">
                    <i class='bx bx-arrow-back me-1'></i>{{ __('Previous') }}
                  </button>
                  <button type="submit" class="btn btn-success" id="btnCreateEmployee">
                    <i class='bx bx-save me-1'></i>{{ __('Create Employee') }}
                  </button>
                </div>
              </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
