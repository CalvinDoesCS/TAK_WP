@extends('layouts/layoutMaster')

@section('title', __('Department Leave Statistics'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
  ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/apex-charts/apexcharts.js'
  ])
@endsection

@section('page-script')
  @vite(['resources/assets/js/app/leave-department-report.js'])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb Component --}}
    <x-breadcrumb
      :title="__('Department Leave Statistics')"
      :breadcrumbs="[
        ['name' => __('Leave Management'), 'url' => route('hrcore.leaves.index')],
        ['name' => __('Reports'), 'url' => '']
      ]"
    />

    {{-- Filters Card --}}
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3">
          {{-- Year Filter --}}
          <div class="col-md-3">
            <label for="yearFilter" class="form-label">{{ __('Year') }}</label>
            <select id="yearFilter" name="yearFilter" class="form-select">
              @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>{{ $i }}</option>
              @endfor
            </select>
          </div>

          {{-- Department Filter --}}
          <div class="col-md-4">
            <label for="departmentFilter" class="form-label">{{ __('Department') }}</label>
            <select id="departmentFilter" name="departmentFilter" class="form-select">
              <option value="" selected>{{ __('All Departments') }}</option>
              @foreach($departments ?? [] as $dept)
                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-5 d-flex align-items-end">
            <button type="button" id="applyFilters" class="btn btn-primary me-2">
              <i class="bx bx-filter me-1"></i>{{ __('Apply Filters') }}
            </button>
            <button type="button" id="resetFilters" class="btn btn-label-secondary">
              <i class="bx bx-reset me-1"></i>{{ __('Reset') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    {{-- Chart Card --}}
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">{{ __('Department Comparison Chart') }}</h5>
      </div>
      <div class="card-body">
        <div id="departmentComparisonChart"></div>
      </div>
    </div>

    {{-- Department Statistics Table --}}
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">{{ __('Department Statistics') }}</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="departmentTable" class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('#') }}</th>
                <th>{{ __('Department') }}</th>
                <th>{{ __('Total Employees') }}</th>
                <th>{{ __('Total Leaves Taken') }}</th>
                <th>{{ __('Average per Employee') }}</th>
                <th>{{ __('Utilization Rate') }}</th>
                <th>{{ __('Pending Requests') }}</th>
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
        datatable: @json(route('hrcore.leave-reports.department.data')),
        chartData: @json(route('hrcore.leave-reports.department.chart'))
      },
      labels: {
        search: @json(__('Search')),
        processing: @json(__('Processing...')),
        lengthMenu: @json(__('Show _MENU_ entries')),
        info: @json(__('Showing _START_ to _END_ of _TOTAL_ entries')),
        infoEmpty: @json(__('Showing 0 to 0 of 0 entries')),
        emptyTable: @json(__('No department records found')),
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
