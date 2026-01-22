@extends('layouts/layoutMaster')

@section('title', __('Employee Demographics Report'))

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/apex-charts/apexcharts.js'
])
@endsection

@section('page-script')
@vite(['resources/assets/js/employees/reports/demographics.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Breadcrumb --}}
  <x-breadcrumb
    :title="__('Employee Demographics Report')"
    :breadcrumbs="[
      ['name' => __('Employees'), 'url' => route('employees.index')],
      ['name' => __('Reports'), 'url' => '']
    ]"
  />

  {{-- Overview Statistics Cards --}}
  <div class="row g-4 mb-4">
    {{-- Total Active Employees --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('Total Employees') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="totalEmployees">0</h3>
              </div>
              <small class="text-muted">{{ __('Active employees') }}</small>
            </div>
            <span class="badge bg-label-primary rounded p-2">
              <i class="bx bx-group bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    {{-- Average Age --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('Average Age') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="averageAge">0</h3>
                <span class="text-muted small">{{ __('years') }}</span>
              </div>
              <small class="text-muted">{{ __('Across all employees') }}</small>
            </div>
            <span class="badge bg-label-success rounded p-2">
              <i class="bx bx-cake bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    {{-- Average Tenure --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('Average Tenure') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="averageTenure">0</h3>
                <span class="text-muted small">{{ __('months') }}</span>
              </div>
              <small class="text-muted">{{ __('Time with company') }}</small>
            </div>
            <span class="badge bg-label-info rounded p-2">
              <i class="bx bx-time-five bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    {{-- Profile Completion Rate --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('Profile Completion') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="profileCompletionRate">0</h3>
                <span class="text-muted small">%</span>
              </div>
              <small class="text-muted">{{ __('Complete profiles') }}</small>
            </div>
            <span class="badge bg-label-warning rounded p-2">
              <i class="bx bx-user-check bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Charts Row 1 --}}
  <div class="row g-4 mb-4">
    {{-- Age Distribution --}}
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">{{ __('Age Distribution') }}</h5>
            <small class="text-muted">{{ __('Employee count by age group') }}</small>
          </div>
          <i class="bx bx-bar-chart-alt text-primary"></i>
        </div>
        <div class="card-body">
          <div id="ageDistributionChart"></div>
        </div>
      </div>
    </div>

    {{-- Gender Distribution --}}
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">{{ __('Gender Distribution') }}</h5>
            <small class="text-muted">{{ __('By employee count') }}</small>
          </div>
          <i class="bx bx-pie-chart-alt text-success"></i>
        </div>
        <div class="card-body">
          <div id="genderChart"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Charts Row 2 --}}
  <div class="row g-4 mb-4">
    {{-- Tenure Distribution --}}
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">{{ __('Tenure Distribution') }}</h5>
            <small class="text-muted">{{ __('Time with company by group') }}</small>
          </div>
          <i class="bx bx-bar-chart text-warning"></i>
        </div>
        <div class="card-body">
          <div id="tenureChart"></div>
        </div>
      </div>
    </div>

    {{-- Probation Status Distribution --}}
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">{{ __('Probation Status') }}</h5>
            <small class="text-muted">{{ __('Employee probation breakdown') }}</small>
          </div>
          <i class="bx bx-doughnut-chart text-info"></i>
        </div>
        <div class="card-body">
          <div id="probationStatusChart"></div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Page Data for JavaScript --}}
<script>
const pageData = {
  urls: {
    demographicsData: @json(route('employees.reports.demographics.data')),
  },
  labels: {
    loading: @json(__('Loading...')),
    noData: @json(__('No data available')),
    error: @json(__('Error loading data')),
    employees: @json(__('Employees')),
    male: @json(__('Male')),
    female: @json(__('Female')),
    other: @json(__('Other')),
    years: @json(__('years')),
    months: @json(__('months')),
  },
};
</script>
@endsection
