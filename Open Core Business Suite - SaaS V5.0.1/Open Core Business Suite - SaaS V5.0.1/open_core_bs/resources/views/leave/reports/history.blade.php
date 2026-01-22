@php use App\Enums\LeaveRequestStatus; @endphp
@extends('layouts/layoutMaster')

@section('title', __('Leave History Report'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
  ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js'
  ])
@endsection

@section('page-script')
  @vite(['resources/assets/js/app/leave-history-report.js'])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb Component --}}
    <x-breadcrumb
      :title="__('Leave History Report')"
      :breadcrumbs="[
        ['name' => __('Leave Management'), 'url' => route('hrcore.leaves.index')],
        ['name' => __('Reports'), 'url' => '']
      ]"
    />

    {{-- Summary Cards --}}
    <div class="row mb-4">
      <div class="col-lg-3 col-sm-6 mb-4">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div class="card-info">
                <p class="card-text text-muted">{{ __('Total Requests') }}</p>
                <div class="d-flex align-items-end mb-2">
                  <h4 class="card-title mb-0 me-2" id="totalRequests">-</h4>
                </div>
              </div>
              <div class="card-icon">
                <span class="badge bg-label-primary rounded p-2">
                  <i class="bx bx-file bx-sm"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-sm-6 mb-4">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div class="card-info">
                <p class="card-text text-muted">{{ __('Approved') }}</p>
                <div class="d-flex align-items-end mb-2">
                  <h4 class="card-title mb-0 me-2" id="approvedCount">-</h4>
                </div>
              </div>
              <div class="card-icon">
                <span class="badge bg-label-success rounded p-2">
                  <i class="bx bx-check-circle bx-sm"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-sm-6 mb-4">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div class="card-info">
                <p class="card-text text-muted">{{ __('Pending') }}</p>
                <div class="d-flex align-items-end mb-2">
                  <h4 class="card-title mb-0 me-2" id="pendingCount">-</h4>
                </div>
              </div>
              <div class="card-icon">
                <span class="badge bg-label-warning rounded p-2">
                  <i class="bx bx-time-five bx-sm"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-sm-6 mb-4">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div class="card-info">
                <p class="card-text text-muted">{{ __('Rejected') }}</p>
                <div class="d-flex align-items-end mb-2">
                  <h4 class="card-title mb-0 me-2" id="rejectedCount">-</h4>
                </div>
              </div>
              <div class="card-icon">
                <span class="badge bg-label-danger rounded p-2">
                  <i class="bx bx-x-circle bx-sm"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Filters Card --}}
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title mb-3">{{ __('Filters') }}</h5>
        <div class="row g-3">
          {{-- Date Range Filter --}}
          <div class="col-md-3">
            <label for="dateFromFilter" class="form-label">{{ __('From Date') }}</label>
            <input type="text" id="dateFromFilter" name="dateFromFilter" class="form-control" placeholder="{{ __('Select date') }}">
          </div>

          <div class="col-md-3">
            <label for="dateToFilter" class="form-label">{{ __('To Date') }}</label>
            <input type="text" id="dateToFilter" name="dateToFilter" class="form-control" placeholder="{{ __('Select date') }}">
          </div>

          {{-- Employee Filter --}}
          <div class="col-md-3">
            <label for="employeeFilter" class="form-label">{{ __('Employee') }}</label>
            <select id="employeeFilter" name="employeeFilter" class="form-select" style="width: 100%;">
              <option value="" selected>{{ __('All Employees') }}</option>
            </select>
          </div>

          {{-- Department Filter --}}
          <div class="col-md-3">
            <label for="departmentFilter" class="form-label">{{ __('Department') }}</label>
            <select id="departmentFilter" name="departmentFilter" class="form-select">
              <option value="" selected>{{ __('All Departments') }}</option>
              @foreach($departments ?? [] as $dept)
                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
              @endforeach
            </select>
          </div>

          {{-- Leave Type Filter --}}
          <div class="col-md-3">
            <label for="leaveTypeFilter" class="form-label">{{ __('Leave Type') }}</label>
            <select id="leaveTypeFilter" name="leaveTypeFilter" class="form-select">
              <option value="" selected>{{ __('All Types') }}</option>
              @foreach($leaveTypes ?? [] as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
              @endforeach
            </select>
          </div>

          {{-- Status Filter --}}
          <div class="col-md-3">
            <label for="statusFilter" class="form-label">{{ __('Status') }}</label>
            <select id="statusFilter" name="statusFilter" class="form-select">
              <option value="" selected>{{ __('All Statuses') }}</option>
              @foreach(LeaveRequestStatus::cases() as $status)
                <option value="{{ $status->value }}">{{ __(ucfirst(str_replace('_', ' ', $status->name))) }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="row mt-3">
          <div class="col-12">
            <button type="button" id="applyFilters" class="btn btn-primary">
              <i class="bx bx-filter me-1"></i>{{ __('Apply Filters') }}
            </button>
            <button type="button" id="resetFilters" class="btn btn-label-secondary">
              <i class="bx bx-reset me-1"></i>{{ __('Reset') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    {{-- Leave History Table --}}
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">{{ __('Leave History Details') }}</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="leaveHistoryTable" class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('#') }}</th>
                <th>{{ __('Employee') }}</th>
                <th>{{ __('Leave Type') }}</th>
                <th>{{ __('Date Range') }}</th>
                <th>{{ __('Total Days') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Requested On') }}</th>
                <th>{{ __('Action By') }}</th>
                <th>{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- Page Data for JavaScript --}}
  <script>
    const pageData = {
      urls: {
        datatable: @json(route('hrcore.leave-reports.history.data')),
        employeeSearch: @json(route('employees.search')),
        statistics: @json(route('hrcore.leave-reports.history.statistics')),
        leaveShow: @json(route('hrcore.leaves.show', ['id' => ':id']))
      },
      labels: {
        search: @json(__('Search')),
        processing: @json(__('Processing...')),
        lengthMenu: @json(__('Show _MENU_ entries')),
        info: @json(__('Showing _START_ to _END_ of _TOTAL_ entries')),
        infoEmpty: @json(__('Showing 0 to 0 of 0 entries')),
        emptyTable: @json(__('No history records found')),
        paginate: {
          first: @json(__('First')),
          last: @json(__('Last')),
          next: @json(__('Next')),
          previous: @json(__('Previous'))
        },
        viewDetails: @json(__('View Details')),
        employee: @json(__('Employee'))
      }
    };
  </script>
@endsection
