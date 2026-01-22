@extends('layouts/layoutMaster')

@section('title', $pageTitle)

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/moment/moment.js',
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

<!-- Page Scripts -->
@section('page-script')
  @vite([
    'resources/js/main-helper.js',
    'resources/assets/js/app/employee-index.js',
    'resources/js/main-select2.js'
])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb --}}
    <x-breadcrumb
      :title="$pageTitle"
      :breadcrumbs="[
          ['name' => $pageTitle, 'url' => '']
      ]"
      :home-url="url('/')"
    />

    {{-- Statistics Cards --}}
    <div class="row mb-4">
      <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="avatar">
                <div class="avatar-initial bg-label-primary rounded">
                  <i class="bx bx-user bx-sm"></i>
                </div>
              </div>
              <div class="ms-3">
                <div class="small mb-1">{{ __('Total') }}</div>
                <h5 class="mb-0">{{$totalUser}}</h5>
                <small class="text-muted">{{ __('All Employees') }}</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="avatar">
                <div class="avatar-initial bg-label-success rounded">
                  <i class="bx bx-user-check bx-sm"></i>
                </div>
              </div>
              <div class="ms-3">
                <div class="small mb-1">{{ __('Active') }}</div>
                <h5 class="mb-0">{{$active}}</h5>
                <small class="text-muted">{{ __('Active Employees') }}</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="avatar">
                <div class="avatar-initial bg-label-secondary rounded">
                  <i class="bx bx-user-x bx-sm"></i>
                </div>
              </div>
              <div class="ms-3">
                <div class="small mb-1">{{ __('Inactive') }}</div>
                <h5 class="mb-0">{{$inactive}}</h5>
                <small class="text-muted">{{ __('Inactive Employees') }}</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="avatar">
                <div class="avatar-initial bg-label-danger rounded">
                  <i class="bx bx-user-minus bx-sm"></i>
                </div>
              </div>
              <div class="ms-3">
                <div class="small mb-1">{{ __('Exited') }}</div>
                <h5 class="mb-0">{{$relieved}}</h5>
                <small class="text-muted">{{ __('Relieved/Terminated') }}</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Filters Card --}}
    <div class="card mb-4">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="bx bx-filter-alt me-2"></i>{{ __('Filters') }}
          </h5>
          <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="true">
            <i class="bx bx-chevron-down"></i>
          </button>
        </div>
      </div>
      <div class="collapse show" id="filterCollapse">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <label for="statusFilter" class="form-label">{{ __('Status') }}</label>
              <select class="form-select" id="statusFilter">
                <option value="">{{ __('All Status') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
                <option value="onboarding">{{ __('Onboarding') }}</option>
                <option value="probation">{{ __('Probation') }}</option>
                <option value="suspended">{{ __('Suspended') }}</option>
                <option value="terminated">{{ __('Terminated') }}</option>
                <option value="relieved">{{ __('Relieved') }}</option>
                <option value="retired">{{ __('Retired') }}</option>
              </select>
            </div>

            <div class="col-md-3">
              <label for="roleFilter" class="form-label">{{ __('Role') }}</label>
              <select class="form-select select2" id="roleFilter">
                <option value="">{{ __('All Roles') }}</option>
                @foreach($roles as $role)
                  <option value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label for="teamFilter" class="form-label">{{ __('Team') }}</label>
              <select class="form-select select2" id="teamFilter">
                <option value="">{{ __('All Teams') }}</option>
                @foreach($teams as $team)
                  <option value="{{ $team->id }}">{{ $team->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label for="designationFilter" class="form-label">{{ __('Designation') }}</label>
              <select class="form-select select2" id="designationFilter">
                <option value="">{{ __('All Designations') }}</option>
                @foreach($designations as $designation)
                  <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-3">
              <label for="attendanceTypeFilter" class="form-label">{{ __('Attendance Type') }}</label>
              <select class="form-select" id="attendanceTypeFilter">
                <option value="">{{ __('All Types') }}</option>
                <option value="geofence">{{ __('Geofence') }}</option>
                <option value="qr">{{ __('QR Code') }}</option>
                <option value="ip">{{ __('IP Address') }}</option>
                <option value="face">{{ __('Face Recognition') }}</option>
                <option value="dynamic_qr">{{ __('Dynamic QR') }}</option>
                <option value="site">{{ __('Site') }}</option>
              </select>
            </div>

            <div class="col-md-3 d-flex align-items-end">
              <button type="button" class="btn btn-secondary w-100" id="resetFilters">
                <i class="bx bx-reset me-1"></i>
                {{ __('Reset Filters') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Employees List --}}
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ $pageTitle }}</h5>
        <a href="{{ route('employees.create') }}" class="btn btn-primary">
          <i class="bx bx-plus me-1"></i>
          {{ __('Add Employee') }}
        </a>
      </div>
      <div class="card-datatable table-responsive">
        <table class="datatables-users table">
          <thead>
            <tr>
              <th></th>
              <th>{{ __('ID') }}</th>
              <th>{{ __('Employee') }}</th>
              <th>{{ __('Phone') }}</th>
              <th>{{ __('Role') }}</th>
              <th>{{ __('Attendance Type') }}</th>
              <th>{{ __('Team') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
