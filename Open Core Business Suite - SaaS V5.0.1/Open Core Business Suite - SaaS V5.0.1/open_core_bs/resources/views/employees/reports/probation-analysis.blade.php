@extends('layouts/layoutMaster')

@section('title', __('Probation Analysis'))

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/apex-charts/apexcharts.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
])
@endsection

@section('page-script')
@vite(['resources/assets/js/employees/probation-analysis.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Breadcrumb --}}
  <x-breadcrumb
    :title="__('Probation Analysis')"
    :breadcrumbs="$breadcrumbs"
  />

  {{-- Filter Section --}}
  <div class="card mb-4">
    <div class="card-body">
      <div class="row g-3">
        {{-- Department Filter --}}
        <div class="col-md-4">
          <label for="departmentFilter" class="form-label">{{ __('Department') }}</label>
          <select id="departmentFilter" name="departmentFilter" class="form-select select2">
            <option value="">{{ __('All Departments') }}</option>
            @foreach($departments as $dept)
              <option value="{{ $dept->id }}">{{ $dept->name }}</option>
            @endforeach
          </select>
        </div>

        {{-- Year Filter --}}
        <div class="col-md-3">
          <label for="yearFilter" class="form-label">{{ __('Year') }}</label>
          <select id="yearFilter" name="yearFilter" class="form-select">
            @for($year = date('Y'); $year >= date('Y') - 5; $year--)
              <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
            @endfor
          </select>
        </div>

        {{-- Action Buttons --}}
        <div class="col-md-5 d-flex align-items-end gap-2">
          <button type="button" id="applyFilters" class="btn btn-primary flex-fill">
            <i class="bx bx-filter-alt me-1"></i>{{ __('Apply Filters') }}
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
    {{-- Current on Probation --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('On Probation') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="currentOnProbation">0</h3>
                <span class="text-muted small">{{ __('employees') }}</span>
              </div>
              <small class="text-muted">{{ __('Currently active') }}</small>
            </div>
            <span class="badge bg-label-primary rounded p-2">
              <i class="bx bx-time-five bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    {{-- Success Rate --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('Success Rate') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="successRate">0</h3>
                <span class="text-success small">%</span>
              </div>
              <small class="text-muted">{{ __('Probation confirmed') }}</small>
            </div>
            <span class="badge bg-label-success rounded p-2">
              <i class="bx bx-check-circle bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    {{-- Failure Rate --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('Failure Rate') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="failureRate">0</h3>
                <span class="text-danger small">%</span>
              </div>
              <small class="text-muted">{{ __('Probation failed') }}</small>
            </div>
            <span class="badge bg-label-danger rounded p-2">
              <i class="bx bx-error-circle bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    {{-- Average Duration --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('Avg Duration') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="avgDuration">0</h3>
                <span class="text-muted small">{{ __('days') }}</span>
              </div>
              <small class="text-muted">{{ __('Until confirmation') }}</small>
            </div>
            <span class="badge bg-label-info rounded p-2">
              <i class="bx bx-calendar bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Charts Row --}}
  <div class="row g-4 mb-4">
    {{-- Monthly Probation Trend --}}
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">{{ __('Monthly Probation Outcomes') }}</h5>
            <small class="text-muted">{{ __('Probation completions over the year') }}</small>
          </div>
          <i class="bx bx-trending-up text-primary"></i>
        </div>
        <div class="card-body">
          <div id="monthlyProbationChart"></div>
        </div>
      </div>
    </div>

    {{-- Outcome Distribution --}}
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">{{ __('Outcome Distribution') }}</h5>
            <small class="text-muted">{{ __('All time statistics') }}</small>
          </div>
          <i class="bx bx-pie-chart-alt text-success"></i>
        </div>
        <div class="card-body">
          <div id="outcomeChart"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Department Outcomes --}}
  <div class="row g-4 mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">{{ __('Department-wise Outcomes') }}</h5>
            <small class="text-muted">{{ __('Probation outcomes by department') }}</small>
          </div>
          <i class="bx bx-bar-chart-alt-2 text-warning"></i>
        </div>
        <div class="card-body">
          <div id="departmentChart"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- DataTables Section --}}
  <div class="row g-4">
    {{-- Current Probation Employees --}}
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">{{ __('Employees Currently on Probation') }}</h5>
        </div>
        <div class="card-body">
          <table class="table" id="currentProbationTable">
            <thead>
              <tr>
                <th>{{ __('Employee') }}</th>
                <th>{{ __('Department') }}</th>
                <th>{{ __('Probation Start') }}</th>
                <th>{{ __('Probation End') }}</th>
                <th>{{ __('Days Remaining') }}</th>
                <th>{{ __('Status') }}</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>

    {{-- Upcoming Probation Endings --}}
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">{{ __('Upcoming Probation Endings') }}</h5>
          <small class="text-muted">{{ __('Next 30 days') }}</small>
        </div>
        <div class="card-body">
          <table class="table" id="upcomingProbationTable">
            <thead>
              <tr>
                <th>{{ __('Employee') }}</th>
                <th>{{ __('Department') }}</th>
                <th>{{ __('End Date') }}</th>
                <th>{{ __('Days Left') }}</th>
                <th>{{ __('Actions') }}</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Page Data for JavaScript --}}
@php
$monthNames = [
  __('Jan'), __('Feb'), __('Mar'), __('Apr'), __('May'), __('Jun'),
  __('Jul'), __('Aug'), __('Sep'), __('Oct'), __('Nov'), __('Dec')
];
@endphp
<script>
const pageData = {
  urls: {
    analysisData: @json(route('employees.reports.probation-analysis.data')),
    currentProbationData: @json(route('employees.reports.current-probation.data')),
    upcomingProbationData: @json(route('employees.reports.upcoming-probation.data')),
    employeeShow: @json(route('employees.show', ':id')),
  },
  labels: {
    confirmed: @json(__('Confirmed')),
    failed: @json(__('Failed')),
    extended: @json(__('Extended')),
    ongoing: @json(__('Ongoing')),
    loading: @json(__('Loading...')),
    noData: @json(__('No data available')),
    error: @json(__('Error loading data')),
    days: @json(__('days')),
    employees: @json(__('employees')),
    months: @json($monthNames),
  },
  currentYear: {{ date('Y') }},
};
</script>
@endsection
