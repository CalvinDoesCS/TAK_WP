@extends('layouts/layoutMaster')

@section('title', __('Leave Analytics Dashboard'))

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/apex-charts/apexcharts.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js'
])
@endsection

@section('page-script')
@vite(['resources/assets/js/leave/leave-reports.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Breadcrumb --}}
  <x-breadcrumb
    :title="__('Leave Analytics Dashboard')"
    :breadcrumbs="[
      ['name' => __('Leave Management'), 'url' => route('hrcore.leaves.index')],
      ['name' => __('Reports'), 'url' => '']
    ]"
  />

  {{-- Filter Section --}}
  <div class="card mb-4">
    <div class="card-body">
      <div class="row g-3">
        {{-- Date Range --}}
        <div class="col-md-4">
          <label for="dateRange" class="form-label">{{ __('Date Range') }}</label>
          <input
            type="text"
            id="dateRange"
            name="dateRange"
            class="form-control"
            placeholder="{{ __('Select date range') }}"
          >
        </div>

        {{-- Department Filter --}}
        <div class="col-md-3">
          <label for="departmentFilter" class="form-label">{{ __('Department') }}</label>
          <select id="departmentFilter" name="departmentFilter" class="form-select select2">
            <option value="">{{ __('All Departments') }}</option>
            @foreach($departments as $dept)
              <option value="{{ $dept->id }}">{{ $dept->name }}</option>
            @endforeach
          </select>
        </div>

        {{-- Leave Type Filter --}}
        <div class="col-md-3">
          <label for="leaveTypeFilter" class="form-label">{{ __('Leave Type') }}</label>
          <select id="leaveTypeFilter" name="leaveTypeFilter" class="form-select select2">
            <option value="">{{ __('All Leave Types') }}</option>
            @foreach($leaveTypes as $type)
              <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
          </select>
        </div>

        {{-- Action Buttons --}}
        <div class="col-md-2 d-flex align-items-end gap-2">
          <button type="button" id="applyFilters" class="btn btn-primary flex-fill">
            <i class="bx bx-filter-alt me-1"></i>{{ __('Apply') }}
          </button>
          <button type="button" id="resetFilters" class="btn btn-label-secondary">
            <i class="bx bx-reset"></i>
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Overview Statistics Cards --}}
  <div class="row g-4 mb-4">
    {{-- Total Leaves Taken --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('Total Leaves Taken') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="totalLeavesTaken">0</h3>
                <span class="text-success small">({{ __('This Year') }})</span>
              </div>
              <small class="text-muted" id="totalLeavesChange"></small>
            </div>
            <span class="badge bg-label-primary rounded p-2">
              <i class="bx bx-calendar-check bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    {{-- Pending Approvals --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('Pending Approvals') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="pendingApprovals">0</h3>
              </div>
              <small class="text-muted">{{ __('Requires action') }}</small>
            </div>
            <span class="badge bg-label-warning rounded p-2">
              <i class="bx bx-time-five bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    {{-- Average Balance --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('Average Balance') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="avgBalance">0</h3>
                <span class="text-muted small">{{ __('days') }}</span>
              </div>
              <small class="text-muted">{{ __('Per employee') }}</small>
            </div>
            <span class="badge bg-label-success rounded p-2">
              <i class="bx bx-briefcase bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    {{-- On Leave Today --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('On Leave Today') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="onLeaveToday">0</h3>
                <span class="text-muted small">{{ __('employees') }}</span>
              </div>
              <small class="text-muted" id="onLeaveTodayPercent"></small>
            </div>
            <span class="badge bg-label-info rounded p-2">
              <i class="bx bx-user-minus bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Charts Row 1 --}}
  <div class="row g-4 mb-4">
    {{-- Monthly Trend Chart --}}
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">{{ __('Monthly Leave Trend') }}</h5>
            <small class="text-muted">{{ __('Leave requests over the last 12 months') }}</small>
          </div>
          <i class="bx bx-trending-up text-primary"></i>
        </div>
        <div class="card-body">
          <div id="monthlyTrendChart"></div>
        </div>
      </div>
    </div>

    {{-- Leave Type Distribution --}}
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">{{ __('Leave Type Distribution') }}</h5>
            <small class="text-muted">{{ __('By number of requests') }}</small>
          </div>
          <i class="bx bx-pie-chart-alt text-success"></i>
        </div>
        <div class="card-body">
          <div id="leaveTypeChart"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Charts Row 2 --}}
  <div class="row g-4">
    {{-- Department Utilization --}}
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">{{ __('Department Utilization') }}</h5>
            <small class="text-muted">{{ __('Average leave days per employee') }}</small>
          </div>
          <i class="bx bx-bar-chart-alt-2 text-warning"></i>
        </div>
        <div class="card-body">
          <div id="departmentChart"></div>
        </div>
      </div>
    </div>

    {{-- Status Distribution --}}
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">{{ __('Request Status Distribution') }}</h5>
            <small class="text-muted">{{ __('Current period status breakdown') }}</small>
          </div>
          <i class="bx bx-doughnut-chart text-info"></i>
        </div>
        <div class="card-body">
          <div id="statusChart"></div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Page Data for JavaScript --}}
@php
$months = [
  __('Jan'), __('Feb'), __('Mar'), __('Apr'), __('May'), __('Jun'),
  __('Jul'), __('Aug'), __('Sep'), __('Oct'), __('Nov'), __('Dec')
];
@endphp
<script>
const pageData = {
  urls: {
    dashboardData: @json(route('hrcore.leave-reports.dashboard.data')),
  },
  labels: {
    months: @json($months),
    approved: @json(__('Approved')),
    pending: @json(__('Pending')),
    rejected: @json(__('Rejected')),
    cancelled: @json(__('Cancelled')),
    loading: @json(__('Loading...')),
    noData: @json(__('No data available')),
    error: @json(__('Error loading data')),
    days: @json(__('days')),
    requests: @json(__('requests')),
    employees: @json(__('employees')),
    leaveRequests: @json(__('Leave Requests')),
    total: @json(__('Total')),
  },
  leaveTypes: @json($leaveTypes ? $leaveTypes->pluck('name', 'id')->toArray() : []),
  departments: @json($departments ? $departments->pluck('name', 'id')->toArray() : []),
  currentYear: {{ date('Y') }},
};
</script>
@endsection
