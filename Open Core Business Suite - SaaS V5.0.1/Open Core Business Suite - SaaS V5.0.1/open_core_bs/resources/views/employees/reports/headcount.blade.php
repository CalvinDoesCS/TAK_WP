@extends('layouts/layoutMaster')

@section('title', __('Employee Headcount Report'))

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
@vite(['resources/assets/js/employees/reports/headcount.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Breadcrumb --}}
  <x-breadcrumb
    :title="__('Employee Headcount Report')"
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
              <span class="text-muted">{{ __('Total Active Employees') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="totalActiveEmployees">0</h3>
              </div>
              <small class="text-muted" id="growthIndicator">
                <i class="bx bx-loader-alt bx-spin"></i> {{ __('Loading...') }}
              </small>
            </div>
            <span class="badge bg-label-primary rounded p-2">
              <i class="bx bx-group bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    {{-- Departments Count --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('Total Departments') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="totalDepartments">0</h3>
              </div>
              <small class="text-muted">{{ __('With active employees') }}</small>
            </div>
            <span class="badge bg-label-success rounded p-2">
              <i class="bx bx-building bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    {{-- Designations Count --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('Total Designations') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="totalDesignations">0</h3>
              </div>
              <small class="text-muted">{{ __('With active employees') }}</small>
            </div>
            <span class="badge bg-label-info rounded p-2">
              <i class="bx bx-briefcase bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    {{-- Average Employees per Department --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('Avg per Department') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="avgPerDepartment">0</h3>
              </div>
              <small class="text-muted">{{ __('Employees') }}</small>
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
    {{-- Headcount Trend --}}
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">{{ __('Headcount Trend') }}</h5>
            <small class="text-muted">{{ __('Employee count over the last 12 months') }}</small>
          </div>
          <i class="bx bx-trending-up text-primary"></i>
        </div>
        <div class="card-body">
          <div id="headcountTrendChart"></div>
        </div>
      </div>
    </div>

    {{-- Employment Status Distribution --}}
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">{{ __('Employment Status') }}</h5>
            <small class="text-muted">{{ __('By probation status') }}</small>
          </div>
          <i class="bx bx-pie-chart-alt text-success"></i>
        </div>
        <div class="card-body">
          <div id="employmentStatusChart"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Charts Row 2 --}}
  <div class="row g-4 mb-4">
    {{-- Department Distribution --}}
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">{{ __('Department Distribution') }}</h5>
            <small class="text-muted">{{ __('Employee count by department') }}</small>
          </div>
          <i class="bx bx-bar-chart-alt-2 text-warning"></i>
        </div>
        <div class="card-body">
          <div id="departmentChart"></div>
        </div>
      </div>
    </div>

    {{-- Designation Distribution --}}
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">{{ __('Designation Distribution') }}</h5>
            <small class="text-muted">{{ __('Top 10 designations by employee count') }}</small>
          </div>
          <i class="bx bx-bar-chart text-info"></i>
        </div>
        <div class="card-body">
          <div id="designationChart"></div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Page Data for JavaScript --}}
<script>
const pageData = {
  urls: {
    headcountData: @json(route('employees.reports.headcount.data')),
  },
  labels: {
    loading: @json(__('Loading...')),
    noData: @json(__('No data available')),
    error: @json(__('Error loading data')),
    employees: @json(__('Employees')),
    headcount: @json(__('Headcount')),
    department: @json(__('Department')),
    designation: @json(__('Designation')),
    location: @json(__('Location')),
    employmentStatus: @json(__('Employment Status')),
    growthThisMonth: @json(__('Growth this month')),
  },
  locationManagementEnabled: false,
};
</script>
@endsection
