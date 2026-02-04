@extends('layouts/layoutMaster')

@section('title', __('Leave Compliance Report'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  ])
@endsection

@section('page-script')
  @vite(['resources/assets/js/app/leave-compliance-report.js'])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb Component --}}
    <x-breadcrumb
      :title="__('Leave Compliance Report')"
      :breadcrumbs="[
        ['name' => __('Leave Management'), 'url' => route('hrcore.leaves.index')],
        ['name' => __('Reports'), 'url' => '']
      ]"
    />

    {{-- Summary Alert Cards --}}
    <div class="row mb-4">
      <div class="col-md-4 mb-3">
        <div class="card border-start border-danger border-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="card-title text-danger mb-1">{{ __('Expiring Soon') }}</h6>
                <h3 class="mb-0" id="expiringCount">-</h3>
                <small class="text-muted">{{ __('Carry forward leaves expiring within 30 days') }}</small>
              </div>
              <div>
                <i class="bx bx-error-circle bx-lg text-danger"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4 mb-3">
        <div class="card border-start border-warning border-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="card-title text-warning mb-1">{{ __('Encashment Eligible') }}</h6>
                <h3 class="mb-0" id="encashmentCount">-</h3>
                <small class="text-muted">{{ __('Employees with high unused leave balance') }}</small>
              </div>
              <div>
                <i class="bx bx-money bx-lg text-warning"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4 mb-3">
        <div class="card border-start border-info border-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="card-title text-info mb-1">{{ __('Policy Alerts') }}</h6>
                <h3 class="mb-0" id="alertsCount">-</h3>
                <small class="text-muted">{{ __('Leaves requiring attention') }}</small>
              </div>
              <div>
                <i class="bx bx-bell bx-lg text-info"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Section 1: Expiring Carry Forward Leaves --}}
    <div class="card mb-4">
      <div class="card-header bg-label-danger d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0 text-danger">
          <i class="bx bx-time-five me-1"></i>{{ __('Expiring Carry Forward Leaves') }}
        </h5>
        <span class="badge bg-danger" id="expiringBadge">0</span>
      </div>
      <div class="card-body">
        <p class="text-muted mb-3">
          {{ __('The following employees have carry forward leaves that will expire within 30 days. Immediate action recommended.') }}
        </p>
        <div class="table-responsive">
          <table id="expiringBalanceTable" class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('#') }}</th>
                <th>{{ __('Employee') }}</th>
                <th>{{ __('Leave Type') }}</th>
                <th>{{ __('CF Leaves') }}</th>
                <th>{{ __('Expiry Date') }}</th>
                <th>{{ __('Urgency') }}</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Section 2: Encashment Eligible Employees --}}
    <div class="card mb-4">
      <div class="card-header bg-label-warning d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0 text-warning">
          <i class="bx bx-money me-1"></i>{{ __('Encashment Eligible Employees') }}
        </h5>
        <span class="badge bg-warning" id="encashmentBadge">0</span>
      </div>
      <div class="card-body">
        <p class="text-muted mb-3">
          {{ __('Employees with high unused leave balance eligible for encashment. Consider leave encashment policy application.') }}
        </p>
        <div class="table-responsive">
          <table id="encashmentEligibleTable" class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('#') }}</th>
                <th>{{ __('Employee') }}</th>
                <th>{{ __('Leave Type') }}</th>
                <th>{{ __('Available Leaves') }}</th>
                <th>{{ __('Max Encashment') }}</th>
                <th>{{ __('Eligible for Encashment') }}</th>
                <th>{{ __('Status') }}</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Section 3: Policy Alerts --}}
    <div class="card mb-4">
      <div class="card-header bg-label-info d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0 text-info">
          <i class="bx bx-error-circle me-1"></i>{{ __('Policy Alerts & Violations') }}
        </h5>
        <span class="badge bg-info" id="policyBadge">0</span>
      </div>
      <div class="card-body">
        <p class="text-muted mb-3">
          {{ __('Leave requests with potential policy violations including overlaps, insufficient balance, and extended leaves.') }}
        </p>
        <div class="table-responsive">
          <table id="policyAlertsTable" class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('#') }}</th>
                <th>{{ __('Employee') }}</th>
                <th>{{ __('Leave Type') }}</th>
                <th>{{ __('Date Range') }}</th>
                <th>{{ __('Days') }}</th>
                <th>{{ __('Alerts') }}</th>
                <th>{{ __('Status') }}</th>
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
        statistics: @json(route('hrcore.leave-reports.compliance.statistics')),
        expiringDatatable: @json(route('hrcore.leave-reports.compliance.expiring')),
        encashmentDatatable: @json(route('hrcore.leave-reports.compliance.encashment')),
        alertsDatatable: @json(route('hrcore.leave-reports.compliance.alerts'))
      },
      labels: {
        search: @json(__('Search')),
        processing: @json(__('Processing...')),
        lengthMenu: @json(__('Show _MENU_ entries')),
        info: @json(__('Showing _START_ to _END_ of _TOTAL_ entries')),
        infoEmpty: @json(__('Showing 0 to 0 of 0 entries')),
        emptyTable: @json(__('No records found')),
        paginate: {
          first: @json(__('First')),
          last: @json(__('Last')),
          next: @json(__('Next')),
          previous: @json(__('Previous'))
        }
      }
    };
  </script>
@endsection
