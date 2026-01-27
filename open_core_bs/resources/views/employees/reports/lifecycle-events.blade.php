@extends('layouts/layoutMaster')

@section('title', __('Employee Lifecycle Events'))

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
])
@endsection

@section('page-script')
@vite(['resources/assets/js/employees/lifecycle-events.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Breadcrumb --}}
  <x-breadcrumb
    :title="__('Employee Lifecycle Events')"
    :breadcrumbs="$breadcrumbs"
  />

  {{-- Statistics Cards --}}
  <div class="row g-4 mb-4">
    {{-- Total Events --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('Total Events') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="totalEvents">0</h3>
              </div>
              <small class="text-muted">{{ __('All time') }}</small>
            </div>
            <span class="badge bg-label-primary rounded p-2">
              <i class="bx bx-list-ul bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    {{-- Recent Events --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('Recent Events') }}</span>
              <div class="d-flex align-items-center my-2">
                <h3 class="mb-0 me-2" id="recentEvents">0</h3>
              </div>
              <small class="text-muted">{{ __('Last 30 days') }}</small>
            </div>
            <span class="badge bg-label-success rounded p-2">
              <i class="bx bx-calendar-event bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    {{-- Most Common Event --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('Most Common') }}</span>
              <div class="d-flex align-items-center my-2">
                <h6 class="mb-0 me-2" id="mostCommonEvent">-</h6>
              </div>
              <small class="text-muted" id="mostCommonCount">0 {{ __('events') }}</small>
            </div>
            <span class="badge bg-label-info rounded p-2">
              <i class="bx bx-trending-up bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>

    {{-- Top Category --}}
    <div class="col-xl-3 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-muted">{{ __('Top Category') }}</span>
              <div class="d-flex align-items-center my-2">
                <h6 class="mb-0 me-2" id="topCategory">-</h6>
              </div>
              <small class="text-muted" id="topCategoryCount">0 {{ __('events') }}</small>
            </div>
            <span class="badge bg-label-warning rounded p-2">
              <i class="bx bx-category bx-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Advanced Filters --}}
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0">{{ __('Advanced Filters') }}</h5>
    </div>
    <div class="card-body">
      <div class="row g-3">
        {{-- Date Range --}}
        <div class="col-md-3">
          <label for="dateRange" class="form-label">{{ __('Date Range') }}</label>
          <input
            type="text"
            id="dateRange"
            name="dateRange"
            class="form-control"
            placeholder="{{ __('Select date range') }}"
          >
        </div>

        {{-- Event Category --}}
        <div class="col-md-2">
          <label for="categoryFilter" class="form-label">{{ __('Category') }}</label>
          <select id="categoryFilter" name="categoryFilter" class="form-select select2">
            <option value="">{{ __('All Categories') }}</option>
            @foreach($categories as $category)
              <option value="{{ $category['value'] }}">{{ $category['label'] }}</option>
            @endforeach
          </select>
        </div>

        {{-- Event Type --}}
        <div class="col-md-3">
          <label for="eventTypeFilter" class="form-label">{{ __('Event Type') }}</label>
          <select id="eventTypeFilter" name="eventTypeFilter" class="form-select select2">
            <option value="">{{ __('All Event Types') }}</option>
            @foreach($eventTypes as $type)
              <option value="{{ $type['value'] }}" data-category="{{ $type['category'] }}">{{ $type['label'] }}</option>
            @endforeach
          </select>
        </div>

        {{-- Department --}}
        <div class="col-md-2">
          <label for="departmentFilter" class="form-label">{{ __('Department') }}</label>
          <select id="departmentFilter" name="departmentFilter" class="form-select select2">
            <option value="">{{ __('All Departments') }}</option>
            @foreach($departments as $dept)
              <option value="{{ $dept->id }}">{{ $dept->name }}</option>
            @endforeach
          </select>
        </div>

        {{-- Action Buttons --}}
        <div class="col-md-2 d-flex align-items-end gap-2">
          <button type="button" id="applyFilters" class="btn btn-primary flex-fill">
            <i class="bx bx-filter-alt me-1"></i>{{ __('Filter') }}
          </button>
          <button type="button" id="resetFilters" class="btn btn-label-secondary">
            <i class="bx bx-reset"></i>
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Events DataTable --}}
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">{{ __('Lifecycle Events Log') }}</h5>
    </div>
    <div class="card-body">
      <table class="table" id="lifecycleEventsTable">
        <thead>
          <tr>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Employee') }}</th>
            <th>{{ __('Event Type') }}</th>
            <th>{{ __('Description') }}</th>
            <th>{{ __('Triggered By') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

{{-- Page Data for JavaScript --}}
<script>
const pageData = {
  urls: {
    lifecycleEventsData: @json(route('employees.reports.lifecycle-events.data')),
    lifecycleEventStatistics: @json(route('employees.reports.lifecycle-event-statistics')),
  },
  labels: {
    loading: @json(__('Loading...')),
    noData: @json(__('No data available')),
    error: @json(__('Error loading data')),
    events: @json(__('events')),
    selectDateRange: @json(__('Select date range')),
  },
  eventTypes: @json($eventTypes),
  categories: @json($categories),
};
</script>
@endsection
