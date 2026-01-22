@php use Carbon\Carbon; @endphp
@extends('layouts/layoutMaster')

@section('title', 'User Profile')

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss'
  ])
@endsection

<!-- Page Styles -->
@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-profile.scss'])
  <style>
    .profile-picture-container {
      position: relative;
      width: 120px;
      height: 120px;
    }

    .profile-picture-container:hover .profile-overlay {
      display: flex;
      background: rgba(0, 0, 0, 0.5);
      border-radius: 50%;
      color: #fff;
      cursor: pointer;
    }

    .profile-overlay {
      display: none;
    }
  </style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
  @vite(['resources/assets/js/app-user-view-account.js'])
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const profilePictureInput = document.getElementById('file');
      const changeProfilePictureButton = document.getElementById('changeProfilePictureButton');

      changeProfilePictureButton.addEventListener('click', function() {
        profilePictureInput.click();
      });

      profilePictureInput.addEventListener('change', function() {
        console.log('Profile Picture Changed');
        if (profilePictureInput.files.length > 0) {
          document.getElementById('profilePictureForm').submit();
        }
      });

      // Change Password Form Validation
      const changePasswordForm = document.getElementById('changePasswordForm');
      if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function(e) {
          const newPassword = document.getElementById('newPassword').value;
          const confirmPassword = document.getElementById('confirmPassword').value;

          if (newPassword !== confirmPassword) {
            e.preventDefault();
            Swal.fire({
              icon: 'error',
              title: '@lang("Error")',
              text: '@lang("New password and confirm password do not match")',
              confirmButtonText: '@lang("OK")'
            });
            return false;
          }
        });
      }
    });
  </script>
@endsection

@section('content')
  <!-- Header -->
  <div class="row">
    <div class="col-12">
      <div class="card mb-6">
        <div class="user-profile-header-banner">
          <img src="{{ asset('assets/img/pages/profile-banner.png') }}" alt="Banner image" class="rounded-top">
        </div>
        <div class="user-profile-header d-flex flex-column flex-lg-row text-sm-start text-center mb-8">
          <div class="flex-shrink-0 mt-1 mx-sm-0 mx-auto">
            <div class="user-avatar-section text-center position-relative">
              <!-- Profile Picture with Rounded Background -->
              <div class="profile-picture-container position-relative d-inline-block"
                   style="width: 150px; height: 150px;">
                <!-- Rounded Background -->
                <div class="rounded-circle bg-label-primary position-absolute top-50 start-50 translate-middle"
                     style="width: 120px; height: 120px;">
                </div>

                <!-- Profile Image -->
                @if($user->profile_picture)
                  <img class="img-fluid rounded-circle position-absolute top-50 start-50 translate-middle"
                       src="{{$user->getProfilePicture()}}"
                       height="120" width="120" alt="User avatar" id="userProfilePicture" />
                @else
                  <h2
                    class="text-white position-absolute top-50 start-50 translate-middle">{{$user->getInitials()}}</h2>
                @endif
                <!-- Overlay on Hover -->
                <div
                  class="profile-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-end justify-content-center"
                  style="display: none;">
                  <button class="btn btn-outline-light" id="changeProfilePictureButton">
                    <i class="bx bx-camera"></i> Change
                  </button>
                </div>
              </div>
            </div>
            <!-- Hidden File Input for Profile Picture Upload -->
            <form id="profilePictureForm" action="{{route('employees.changeEmployeeProfilePicture')}}" method="POST"
                  enctype="multipart/form-data" style="display: none;">
              @csrf
              <input type="hidden" name="userId" id="userId" value="{{ auth()->user()->id }}">
              <input type="file" id="file" name="file" accept="image/*">
            </form>
          </div>
          <div class="flex-grow-1 mt-3 mt-lg-5">
            <div
              class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-4">
              <div class="user-profile-info">
                <h4 class="mb-2 mt-lg-7">{{ $user['first_name'] }} {{ $user['last_name'] }}</h4>
                <ul
                  class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4 mt-4">
                  <li class="list-inline-item">
                    <i class='bx bx-envelope me-2 align-top'></i><span class="fw-medium">{{ $user['email'] }}</span>
                  </li>
                  <li class="list-inline-item">
                    <i class='bx bx-calendar me-2 align-top'></i><span
                      class="fw-medium">Joined: {{ Carbon::parse($user['created_at'])->format('M d, Y') }}</span>
                  </li>
                  <li class="list-inline-item">
                    <i class='bx bx-flag me-2 align-top'></i><span
                      class="fw-medium">Status: {{ $user['status'] }}</span>
                  </li>
                </ul>
              </div>
              <a href="javascript:void(0)" class="btn btn-primary mb-1">
                <i class='bx bx-user-check bx-sm me-2'></i>Connected
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!--/ Header -->

  <!-- Navbar pills -->
  <div class="row">
    <div class="col-md-12">
      <div class="nav-align-top">
        <ul class="nav nav-pills flex-column flex-sm-row mb-6" role="tablist">
          <li class="nav-item">
            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#profile-tab" aria-controls="profile-tab" aria-selected="true">
              <i class='bx bx-user bx-sm me-1_5'></i> @lang('Profile')
            </button>
          </li>
          <li class="nav-item">
            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#security-tab" aria-controls="security-tab" aria-selected="false">
              <i class='bx bx-lock bx-sm me-1_5'></i> @lang('Security')
            </button>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <!--/ Navbar pills -->

  <!-- Tab Content -->
  <div class="tab-content">
    <!-- Profile Tab -->
    <div class="tab-pane fade show active" id="profile-tab" role="tabpanel">
      <div class="row">
        <div class="col-xl-4 col-lg-5 col-md-5">
          <!-- About User -->
          <div class="card mb-6">
            <div class="card-body">
              <small class="card-text text-uppercase text-muted small fw-semibold">@lang('Personal Information')</small>
              <ul class="list-unstyled my-3 py-1">
                <li class="d-flex align-items-center mb-3">
                  <i class="bx bx-user text-heading"></i>
                  <span class="fw-medium mx-2">@lang('Full Name'):</span>
                  <span>{{ $user['first_name'] }} {{ $user['last_name'] }}</span>
                </li>
                @if($user->dob)
                <li class="d-flex align-items-center mb-3">
                  <i class="bx bx-cake text-heading"></i>
                  <span class="fw-medium mx-2">@lang('Date of Birth'):</span>
                  <span>{{ Carbon::parse($user->dob)->format('M d, Y') }}</span>
                </li>
                @endif
                @if($user->gender)
                <li class="d-flex align-items-center mb-3">
                  <i class="bx bx-{{ $user->gender === 'male' ? 'male' : 'female' }} text-heading"></i>
                  <span class="fw-medium mx-2">@lang('Gender'):</span>
                  <span class="text-capitalize">{{ $user->gender }}</span>
                </li>
                @endif
                <li class="d-flex align-items-center mb-3">
                  <i class="bx bx-globe text-heading"></i>
                  <span class="fw-medium mx-2">@lang('Language'):</span>
                  <span>{{ strtoupper($user['language']) }}</span>
                </li>
              </ul>

              <small class="card-text text-uppercase text-muted small fw-semibold">@lang('Contact Information')</small>
              <ul class="list-unstyled my-3 py-1">
                <li class="d-flex align-items-center mb-3">
                  <i class="bx bx-envelope text-heading"></i>
                  <span class="fw-medium mx-2">@lang('Email'):</span>
                  <span>{{ $user['email'] }}</span>
                </li>
                @if($user->phone)
                <li class="d-flex align-items-center mb-3">
                  <i class="bx bx-phone text-heading"></i>
                  <span class="fw-medium mx-2">@lang('Phone'):</span>
                  <span>{{ $user['phone'] }}</span>
                </li>
                @endif
                @if($user->alternate_number)
                <li class="d-flex align-items-center mb-3">
                  <i class="bx bx-phone-call text-heading"></i>
                  <span class="fw-medium mx-2">@lang('Alternate'):</span>
                  <span>{{ $user->alternate_number }}</span>
                </li>
                @endif
              </ul>

              <small class="card-text text-uppercase text-muted small fw-semibold">@lang('System Info')</small>
              <ul class="list-unstyled my-3 py-1">
                <li class="d-flex align-items-center mb-3">
                  <i class="bx bx-user-pin text-heading"></i>
                  <span class="fw-medium mx-2">@lang('Role'):</span>
                  <span class="badge bg-label-primary">{{ $role->name }}</span>
                </li>
                <li class="d-flex align-items-center mb-3">
                  <i class="bx bx-check-circle text-heading"></i>
                  <span class="fw-medium mx-2">@lang('Status'):</span>
                  <span class="badge bg-label-success">{{ $user['status'] }}</span>
                </li>
                @if($user->code)
                <li class="d-flex align-items-center mb-3">
                  <i class="bx bx-barcode text-heading"></i>
                  <span class="fw-medium mx-2">@lang('Employee Code'):</span>
                  <span class="badge bg-label-info">{{ $user->code }}</span>
                </li>
                @endif
              </ul>
            </div>
          </div>
          <!--/ About User -->

          @if($user->date_of_joining || $user->anniversary_date)
          <!-- Work Information -->
          <div class="card mb-6">
            <div class="card-body">
              <small class="card-text text-uppercase text-muted small fw-semibold">@lang('Work Information')</small>
              <ul class="list-unstyled my-3 py-1">
                @if($user->date_of_joining)
                <li class="d-flex align-items-center mb-3">
                  <i class="bx bx-calendar-check text-heading"></i>
                  <span class="fw-medium mx-2">@lang('Joining Date'):</span>
                  <span>{{ Carbon::parse($user->date_of_joining)->format('M d, Y') }}</span>
                </li>
                <li class="d-flex align-items-center mb-3">
                  <i class="bx bx-time text-heading"></i>
                  <span class="fw-medium mx-2">@lang('Experience'):</span>
                  <span>{{ Carbon::parse($user->date_of_joining)->diffForHumans(null, true) }}</span>
                </li>
                @endif
                @if($user->anniversary_date)
                <li class="d-flex align-items-center mb-3">
                  <i class="bx bx-star text-heading"></i>
                  <span class="fw-medium mx-2">@lang('Anniversary'):</span>
                  <span>{{ Carbon::parse($user->anniversary_date)->format('M d, Y') }}</span>
                </li>
                @endif
                @if($user->base_salary)
                <li class="d-flex align-items-center mb-3">
                  <i class="bx bx-dollar text-heading"></i>
                  <span class="fw-medium mx-2">@lang('Base Salary'):</span>
                  <span class="fw-semibold">{{ number_format($user->base_salary, 2) }}</span>
                </li>
                @endif
              </ul>
            </div>
          </div>
          <!--/ Work Information -->
          @endif
        </div>
        <div class="col-xl-8 col-lg-7 col-md-7">
          <!-- Quick Stats -->
          <div class="row mb-6">
            <div class="col-md-3 col-sm-6 mb-md-0 mb-4">
              <div class="card h-100">
                <div class="card-body text-center">
                  <div class="avatar mx-auto mb-3">
                    <span class="avatar-initial rounded-circle bg-label-primary">
                      <i class="bx bx-time bx-lg"></i>
                    </span>
                  </div>
                  <h4 class="mb-0">{{ Carbon::parse($user->created_at)->diffForHumans(null, true) }}</h4>
                  <p class="mb-0 text-muted">@lang('Member Since')</p>
                </div>
              </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-md-0 mb-4">
              <div class="card h-100">
                <div class="card-body text-center">
                  <div class="avatar mx-auto mb-3">
                    <span class="avatar-initial rounded-circle bg-label-success">
                      <i class="bx bx-check-circle bx-lg"></i>
                    </span>
                  </div>
                  <h4 class="mb-0">{{ $user->email_verified_at ? __('Verified') : __('Pending') }}</h4>
                  <p class="mb-0 text-muted">@lang('Email Status')</p>
                </div>
              </div>
            </div>
            <div class="col-md-3 col-sm-6">
              <div class="card h-100">
                <div class="card-body text-center">
                  <div class="avatar mx-auto mb-3">
                    <span class="avatar-initial rounded-circle bg-label-info">
                      <i class="bx bx-calendar bx-lg"></i>
                    </span>
                  </div>
                  <h4 class="mb-0">{{ Carbon::parse($user->created_at)->format('M d, Y') }}</h4>
                  <p class="mb-0 text-muted">@lang('Joined On')</p>
                </div>
              </div>
            </div>
            <div class="col-md-3 col-sm-6">
              <div class="card h-100">
                <div class="card-body text-center">
                  <div class="avatar mx-auto mb-3">
                    <span class="avatar-initial rounded-circle bg-label-warning">
                      <i class="bx bx-history bx-lg"></i>
                    </span>
                  </div>
                  <h4 class="mb-0">{{ $auditLogs->count() }}</h4>
                  <p class="mb-0 text-muted">@lang('Activities')</p>
                </div>
              </div>
            </div>
          </div>
          <!--/ Quick Stats -->

          <!-- Account Details -->
          <div class="card mb-6">
            <div class="card-header">
              <h5 class="card-title mb-0">@lang('Account Details')</h5>
            </div>
            <div class="card-body">
              <div class="row g-4">
                <div class="col-md-6">
                  <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm flex-shrink-0 me-3">
                      <span class="avatar-initial rounded bg-label-primary">
                        <i class="bx bx-user"></i>
                      </span>
                    </div>
                    <div class="flex-grow-1">
                      <p class="mb-0 text-muted small">@lang('Username')</p>
                      <h6 class="mb-0">{{ $user->email }}</h6>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm flex-shrink-0 me-3">
                      <span class="avatar-initial rounded bg-label-success">
                        <i class="bx bx-shield"></i>
                      </span>
                    </div>
                    <div class="flex-grow-1">
                      <p class="mb-0 text-muted small">@lang('Role')</p>
                      <h6 class="mb-0">{{ $role->name }}</h6>
                    </div>
                  </div>
                </div>
                @if($user->date_of_joining)
                <div class="col-md-6">
                  <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm flex-shrink-0 me-3">
                      <span class="avatar-initial rounded bg-label-info">
                        <i class="bx bx-briefcase"></i>
                      </span>
                    </div>
                    <div class="flex-grow-1">
                      <p class="mb-0 text-muted small">@lang('Joining Date')</p>
                      <h6 class="mb-0">{{ Carbon::parse($user->date_of_joining)->format('M d, Y') }}</h6>
                    </div>
                  </div>
                </div>
                @endif
                <div class="col-md-6">
                  <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm flex-shrink-0 me-3">
                      <span class="avatar-initial rounded bg-label-warning">
                        <i class="bx bx-globe"></i>
                      </span>
                    </div>
                    <div class="flex-grow-1">
                      <p class="mb-0 text-muted small">@lang('Language Preference')</p>
                      <h6 class="mb-0">{{ strtoupper($user->language) }}</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!--/ Account Details -->

          <!-- Activity Timeline -->
          <div class="card card-action mb-6">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h5 class="card-title mb-0">
                <i class='bx bx-bar-chart-alt-2 me-2'></i>@lang('Recent Activities')
              </h5>
              <span class="badge bg-label-primary">{{ $auditLogs->count() }} @lang('Total')</span>
            </div>
            <div class="card-body pt-3" style="max-height: 400px; overflow-y: auto;">
              @if($auditLogs->count() > 0)
              <ul class="timeline mb-0">
                @foreach ($auditLogs->take(10) as $log)
                  <li class="timeline-item timeline-item-transparent">
                    <span class="timeline-point timeline-point-{{ $log['event'] === 'created' ? 'success' : ($log['event'] === 'updated' ? 'primary' : 'danger') }}"></span>
                    <div class="timeline-event">
                      <div class="timeline-header mb-2">
                        <h6 class="mb-0">
                          <span class="badge bg-label-{{ $log['event'] === 'created' ? 'success' : ($log['event'] === 'updated' ? 'primary' : 'danger') }}">
                            {{ ucfirst($log['event']) }}
                          </span>
                        </h6>
                        <small class="text-muted">{{ Carbon::parse($log['created_at'])->diffForHumans() }}</small>
                      </div>
                      <p class="mb-0 small">
                        <i class="bx bx-link-external"></i> {{ $log['url'] }}
                      </p>
                      <small class="text-muted">
                        <i class="bx bx-map"></i> {{ $log['ip_address'] }}
                      </small>
                    </div>
                  </li>
                @endforeach
              </ul>
              @else
              <div class="text-center py-5">
                <div class="avatar avatar-xl mx-auto mb-3">
                  <span class="avatar-initial rounded-circle bg-label-secondary">
                    <i class="bx bx-time bx-lg"></i>
                  </span>
                </div>
                <p class="text-muted mb-0">@lang('No recent activities found')</p>
              </div>
              @endif
            </div>
          </div>
          <!--/ Activity Timeline -->
        </div>
      </div>
    </div>
    <!--/ Profile Tab -->

    <!-- Security Tab -->
    <div class="tab-pane fade" id="security-tab" role="tabpanel">
      <div class="row">
        <div class="col-12">
          <!-- Change Password Card -->
          <div class="card mb-6">
            <div class="card-header">
              <h5 class="card-title mb-0">@lang('Change Password')</h5>
              <p class="card-subtitle text-muted mt-2">@lang('Ensure your account is using a strong password to stay secure')</p>
            </div>
            <div class="card-body">
              <form id="changePasswordForm" action="{{route('account.changePassword')}}" method="POST">
                @csrf
                @method('POST')
                <div class="row g-4">
                  <div class="col-md-6">
                    <label for="oldPassword" class="form-label">@lang('Current Password')</label>
                    <input type="password" class="form-control" id="oldPassword" name="oldPassword"
                           placeholder="@lang('Enter current password')" required/>
                  </div>
                  <div class="col-md-6"></div>
                  <div class="col-md-6">
                    <label for="newPassword" class="form-label">@lang('New Password')</label>
                    <input type="password" class="form-control" id="newPassword" name="newPassword"
                           placeholder="@lang('Enter new password')" required minlength="6"/>
                    <div class="form-text">@lang('Minimum 6 characters')</div>
                  </div>
                  <div class="col-md-6">
                    <label for="confirmPassword" class="form-label">@lang('Confirm New Password')</label>
                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword"
                           placeholder="@lang('Confirm new password')" required minlength="6"/>
                  </div>
                  <div class="col-12">
                    <button type="submit" class="btn btn-primary me-2">
                      <i class="bx bx-save me-1"></i>@lang('Update Password')
                    </button>
                    <button type="reset" class="btn btn-label-secondary">@lang('Reset')</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
          <!--/ Change Password Card -->
        </div>
      </div>
    </div>
    <!--/ Security Tab -->
  </div>
  <!--/ Tab Content -->
@endsection
