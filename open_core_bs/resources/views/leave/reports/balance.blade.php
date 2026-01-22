@extends('layouts/layoutMaster')

@section('title', __('Leave Balance Report'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
  ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/select2/select2.js',
  ])
@endsection

@section('page-script')
  @vite(['resources/assets/js/app/leave-balance-report.js'])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb Component --}}
    <x-breadcrumb
      :title="__('Leave Balance Report')"
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
                <p class="card-text text-muted">{{ __('Total Employees') }}</p>
                <div class="d-flex align-items-end mb-2">
                  <h4 class="card-title mb-0 me-2" id="totalEmployees">-</h4>
                </div>
              </div>
              <div class="card-icon">
                <span class="badge bg-label-primary rounded p-2">
                  <i class="bx bx-user bx-sm"></i>
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
                <p class="card-text text-muted">{{ __('Total Entitled') }}</p>
                <div class="d-flex align-items-end mb-2">
                  <h4 class="card-title mb-0 me-2" id="totalEntitled">-</h4>
                  <small class="text-muted">{{ __('days') }}</small>
                </div>
              </div>
              <div class="card-icon">
                <span class="badge bg-label-info rounded p-2">
                  <i class="bx bx-calendar bx-sm"></i>
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
                <p class="card-text text-muted">{{ __('Total Used') }}</p>
                <div class="d-flex align-items-end mb-2">
                  <h4 class="card-title mb-0 me-2" id="totalUsed">-</h4>
                  <small class="text-muted">{{ __('days') }}</small>
                </div>
              </div>
              <div class="card-icon">
                <span class="badge bg-label-warning rounded p-2">
                  <i class="bx bx-calendar-minus bx-sm"></i>
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
                <p class="card-text text-muted">{{ __('Total Available') }}</p>
                <div class="d-flex align-items-end mb-2">
                  <h4 class="card-title mb-0 me-2" id="totalAvailable">-</h4>
                  <small class="text-muted">{{ __('days') }}</small>
                </div>
              </div>
              <div class="card-icon">
                <span class="badge bg-label-success rounded p-2">
                  <i class="bx bx-calendar-check bx-sm"></i>
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
          {{-- Year Filter --}}
          <div class="col-md-2">
            <label for="yearFilter" class="form-label">{{ __('Year') }}</label>
            <select id="yearFilter" name="yearFilter" class="form-select">
              @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>{{ $i }}</option>
              @endfor
            </select>
          </div>

          {{-- Employee Filter --}}
          <div class="col-md-3">
            <label for="employeeFilter" class="form-label">{{ __('Employee') }}</label>
            <select id="employeeFilter" name="employeeFilter" class="form-select" style="width: 100%;">
              <option value="" selected>{{ __('All Employees') }}</option>
            </select>
          </div>

          {{-- Department Filter --}}
          <div class="col-md-2">
            <label for="departmentFilter" class="form-label">{{ __('Department') }}</label>
            <select id="departmentFilter" name="departmentFilter" class="form-select">
              <option value="" selected>{{ __('All Departments') }}</option>
              @foreach($departments ?? [] as $dept)
                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
              @endforeach
            </select>
          </div>

          {{-- Leave Type Filter --}}
          <div class="col-md-2">
            <label for="leaveTypeFilter" class="form-label">{{ __('Leave Type') }}</label>
            <select id="leaveTypeFilter" name="leaveTypeFilter" class="form-select">
              <option value="" selected>{{ __('All Types') }}</option>
              @foreach($leaveTypes ?? [] as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
              @endforeach
            </select>
          </div>

          {{-- Expiring Soon Checkbox --}}
          <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="expiringSoonFilter" name="expiringSoonFilter">
              <label class="form-check-label" for="expiringSoonFilter">
                {{ __('Expiring Soon (within 30 days)') }}
              </label>
            </div>
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

    {{-- Leave Balance Table --}}
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">{{ __('Leave Balance Details') }}</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="leaveBalanceTable" class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('#') }}</th>
                <th>{{ __('Employee') }}</th>
                <th>{{ __('Leave Type') }}</th>
                <th>{{ __('Entitled') }}</th>
                <th>{{ __('Used') }}</th>
                <th>{{ __('Available') }}</th>
                <th>{{ __('Carried Forward') }}</th>
                <th>{{ __('Expiry Date') }}</th>
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

  {{-- Balance Details Modal --}}
  <div class="modal fade" id="balanceDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ __('Leave Balance Details') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="balanceDetailsContent">
          <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">{{ __('Loading...') }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Page Data for JavaScript --}}
  <script>
    const pageData = {
      urls: {
        datatable: @json(route('hrcore.leave-reports.balance.data')),
        employeeSearch: @json(route('employees.search')),
        statistics: @json(route('hrcore.leave-reports.balance.statistics')),
        balanceDetails: @json(route('hrcore.leave-reports.balance.details', ['user' => ':userId']))
      },
      labels: {
        search: @json(__('Search')),
        processing: @json(__('Processing...')),
        lengthMenu: @json(__('Show _MENU_ entries')),
        info: @json(__('Showing _START_ to _END_ of _TOTAL_ entries')),
        infoEmpty: @json(__('Showing 0 to 0 of 0 entries')),
        emptyTable: @json(__('No balance records found')),
        paginate: {
          first: @json(__('First')),
          last: @json(__('Last')),
          next: @json(__('Next')),
          previous: @json(__('Previous'))
        },
        viewDetails: @json(__('View Details')),
        employee: @json(__('Employee')),
        leaveType: @json(__('Leave Type')),
        entitled: @json(__('Entitled')),
        used: @json(__('Used')),
        available: @json(__('Available')),
        carriedForward: @json(__('Carried Forward')),
        expiryDate: @json(__('Expiry Date')),
        noData: @json(__('No data available')),
        days: @json(__('days'))
      }
    };
  </script>
@endsection
